<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\ApplyJsonPatch;
use App\Actions\RenderVisualText;
use App\Actions\ValidateVisualSpec;
use App\Data\VisualDataset;
use App\Exceptions\InvalidVisualSpecException;
use InvalidArgumentException;
use JsonException;
use stdClass;
use Throwable;

/**
 * @phpstan-type TPatch array{op: string, path: string, value: mixed}
 * @phpstan-type TComposedEvent array{type: 'text', text: string}|array{type: 'visual_patch', visual_id: string, patches: list<TPatch>}|array{type: 'visual_end', visual_id: string, accepted: bool, fallback_text: string, reason: string|null}
 */
final class ReplyComposer
{
    public const int EVENT_BUDGET_BYTES = 7_000;

    private const string FENCE_OPEN = '```spec';

    private const string FENCE_CLOSE = '```';

    private const array CHARTS = ['BarChart', 'LineChart'];

    private string $line = '';

    private bool $lineIsProse = false;

    private bool $closedWithoutFence = false;

    private ?string $visualId = null;

    /**
     * @var list<string>
     */
    private array $visualLines = [];

    private int $visualCount = 0;

    public function __construct(
        private readonly ValidateVisualSpec $validator,
        private readonly RenderVisualText $renderer,
        private readonly ApplyJsonPatch $patcher,
        private readonly VisualCatalog $catalog,
        private readonly TurnDataRegistry $datasets,
        private readonly int $catalogVersion,
        private readonly string $locale,
        private readonly string $timezone,
        private readonly string $visualIdPrefix,
    ) {}

    public static function contextMarker(string $fallbackText): string
    {
        return mb_trim($fallbackText) === '' ? '' : "[visual]\n{$fallbackText}\n[/visual]\n";
    }

    /**
     * @return list<TComposedEvent>
     */
    public function feed(string $delta): array
    {
        $events = [];

        foreach (preg_split('/(?<=\n)/', $delta, flags: PREG_SPLIT_NO_EMPTY) ?: [] as $piece) {
            array_push($events, ...$this->consume($piece));
        }

        return $events;
    }

    /**
     * @return list<TComposedEvent>
     */
    public function finish(): array
    {
        $line = $this->line;
        $closedWithoutFence = $this->closedWithoutFence;
        $this->line = '';
        $this->lineIsProse = false;

        $events = match (true) {
            $this->visualId !== null => $this->visualLine($line),
            $line === '', mb_trim($line) === self::FENCE_OPEN, $closedWithoutFence && mb_trim($line) === self::FENCE_CLOSE => [],
            default => [$this->text($line)],
        };

        $this->closedWithoutFence = false;

        return $events;
    }

    public function contextText(string $text): string
    {
        $context = '';

        foreach ([...$this->feed($text), ...$this->finish()] as $event) {
            if ($event['type'] === 'text') {
                $context .= $event['text'];
            } elseif ($event['type'] === 'visual_end' && $event['accepted']) {
                $context .= self::contextMarker($event['fallback_text']);
            }
        }

        return $context;
    }

    /**
     * @return list<TComposedEvent>
     */
    public function cancel(): array
    {
        $visualId = $this->visualId;
        $this->line = '';
        $this->visualId = null;
        $this->visualLines = [];

        return $visualId === null ? [] : [$this->rejected($visualId, 'cancelled')];
    }

    /**
     * @return list<TComposedEvent>
     */
    private function consume(string $piece): array
    {
        $completesLine = str_ends_with($piece, "\n");

        if ($this->visualId !== null) {
            $this->line .= $piece;

            if (! $completesLine) {
                return [];
            }

            $line = $this->line;
            $this->line = '';

            return $this->visualLine($line, completesVisual: false);
        }

        if ($this->lineIsProse) {
            $this->lineIsProse = ! $completesLine;

            return [$this->text($piece)];
        }

        $this->line .= $piece;

        if ($completesLine) {
            $line = $this->line;
            $trimmed = mb_trim($line);
            $isStrayFence = $this->closedWithoutFence && $trimmed === self::FENCE_CLOSE;
            $this->line = '';
            $this->closedWithoutFence = false;

            return match (true) {
                $isStrayFence => [],
                $trimmed === self::FENCE_OPEN => [$this->openVisual()],
                default => [$this->text($line)],
            };
        }

        if ($this->couldOpenVisual($this->line)) {
            return [];
        }

        $line = $this->line;
        $this->line = '';

        $this->lineIsProse = true;

        return [$this->text($line)];
    }

    /**
     * @return list<TComposedEvent>
     */
    private function visualLine(string $line, bool $completesVisual = true): array
    {
        $trimmed = mb_trim($line);

        if ($trimmed === self::FENCE_CLOSE) {
            return $this->closeVisual();
        }

        if ($trimmed !== '' && ! str_starts_with($trimmed, '{')) {
            $events = $this->closeVisual();
            $this->closedWithoutFence = true;

            return [...$events, $this->text($line)];
        }

        $this->visualLines[] = $line;

        return $completesVisual ? $this->closeVisual() : [];
    }

    private function couldOpenVisual(string $line): bool
    {
        $trimmed = mb_ltrim($line);

        return str_starts_with(self::FENCE_OPEN, $trimmed) || mb_rtrim($trimmed) === self::FENCE_OPEN;
    }

    /**
     * @return array{type: 'visual_patch', visual_id: string, patches: list<TPatch>}
     */
    private function openVisual(): array
    {
        $this->visualCount++;
        $this->visualId = "{$this->visualIdPrefix}-{$this->visualCount}";

        return ['type' => 'visual_patch', 'visual_id' => $this->visualId, 'patches' => []];
    }

    /**
     * @return list<TComposedEvent>
     */
    private function closeVisual(): array
    {
        $visualId = (string) $this->visualId;
        $lines = $this->visualLines;
        $this->visualId = null;
        $this->visualLines = [];

        if ($this->visualCount > $this->catalog->limits()['visualsPerReply']) {
            return [$this->rejected($visualId, 'too_many_visuals')];
        }

        try {
            $spec = $this->validator->handle($this->withDatasetFacts($this->document($lines)), $this->catalogVersion, $this->datasetRows());
            $patches = $this->releasePatches($visualId, $spec);
            $end = [
                'type' => 'visual_end',
                'visual_id' => $visualId,
                'accepted' => true,
                'fallback_text' => $this->renderer->handle((array) json_decode((string) json_encode($spec), true), $this->locale, $this->timezone),
                'reason' => null,
            ];

            throw_if($this->eventBytes($end) > self::EVENT_BUDGET_BYTES, InvalidVisualSpecException::class, 'too_large');
        } catch (InvalidVisualSpecException $invalidVisualSpecException) {
            return [$this->rejected($visualId, $invalidVisualSpecException->reason)];
        } catch (Throwable $throwable) {
            report($throwable);

            return [$this->rejected($visualId, 'unexpected_error')];
        }

        return [...$patches, $end];
    }

    /**
     * @param  list<string>  $lines
     */
    private function document(array $lines): stdClass
    {
        $document = new stdClass;

        try {
            foreach ($lines as $line) {
                if (mb_trim($line) === '') {
                    continue;
                }

                $operation = json_decode($line, flags: JSON_THROW_ON_ERROR);

                throw_unless($operation instanceof stdClass, InvalidArgumentException::class, 'A spec line must be a JSON Patch operation.');

                $document = $this->patcher->handle($document, $operation);
            }
        } catch (JsonException|InvalidArgumentException) {
            throw new InvalidVisualSpecException('malformed_patch');
        }

        throw_unless($document instanceof stdClass, InvalidVisualSpecException::class, 'malformed_patch');

        return $document;
    }

    private function datasetRows(): stdClass
    {
        $rows = new stdClass;

        foreach ($this->datasets->datasets() as $handle => $dataset) {
            $rows->{$handle} = json_decode((string) json_encode($dataset->rows));
        }

        return $rows;
    }

    private function withDatasetFacts(stdClass $document): stdClass
    {
        $elements = $document->elements ?? null;

        foreach ($elements instanceof stdClass ? get_object_vars($elements) : [] as $element) {
            $props = $element instanceof stdClass && in_array($element->type ?? null, self::CHARTS, true) ? $element->props ?? null : null;
            $dataset = $props instanceof stdClass ? $this->boundDataset($props->data ?? null) : null;

            if ($props instanceof stdClass && $dataset instanceof VisualDataset) {
                $dayBoundary = __("visuals.day_boundary.{$dataset->dayBoundary}", locale: $this->locale);
                $props->xKey = 'day';
                $props->yKey = 'value';
                $props->unit = $dataset->unit;
                $props->dayBoundary = is_string($dayBoundary) ? $dayBoundary : null;
            }
        }

        return $document;
    }

    private function boundDataset(mixed $binding): ?VisualDataset
    {
        $pointer = $binding instanceof stdClass && count(get_object_vars($binding)) === 1 ? $binding->{'$state'} ?? null : null;

        return is_string($pointer) && str_starts_with($pointer, '/data/') ? $this->datasets->datasets()[mb_substr($pointer, 6)] ?? null : null;
    }

    /**
     * @return list<array{type: 'visual_patch', visual_id: string, patches: list<TPatch>}>
     */
    private function releasePatches(string $visualId, stdClass $spec): array
    {
        $patches = [
            ['op' => 'add', 'path' => '/root', 'value' => $spec->root],
            ['op' => 'add', 'path' => '/elements', 'value' => new stdClass],
        ];

        $state = $spec->state ?? null;

        if ($state instanceof stdClass && property_exists($state, 'ui')) {
            array_push($patches, ...$this->split($visualId, '/state/ui', $state->ui));
        }

        if ($state instanceof stdClass && property_exists($state, 'data')) {
            array_push($patches, ...$this->split($visualId, '/state/data', $state->data));
        }

        $elements = $spec->elements ?? new stdClass;

        foreach (get_object_vars($elements instanceof stdClass ? $elements : new stdClass) as $key => $element) {
            array_push($patches, ...$this->split($visualId, '/elements/'.$this->pointerSegment((string) $key), $element));
        }

        throw_if(count($patches) > $this->catalog->limits()['patchesPerVisual'], InvalidVisualSpecException::class, 'too_large');

        return $this->group($visualId, $patches);
    }

    /**
     * @return list<TPatch>
     */
    private function split(string $visualId, string $path, mixed $value): array
    {
        $patch = ['op' => 'add', 'path' => $path, 'value' => $value];

        if ($this->eventBytes(['visual_id' => $visualId, 'patches' => [$patch]]) <= self::EVENT_BUDGET_BYTES) {
            return [$patch];
        }

        if (is_array($value)) {
            $patches = [['op' => 'add', 'path' => $path, 'value' => []]];

            foreach (array_values($value) as $index => $item) {
                array_push($patches, ...$this->split($visualId, "{$path}/{$index}", $item));
            }

            return $patches;
        }

        if ($value instanceof stdClass) {
            $patches = [['op' => 'add', 'path' => $path, 'value' => new stdClass]];

            foreach (get_object_vars($value) as $key => $item) {
                array_push($patches, ...$this->split($visualId, $path.'/'.$this->pointerSegment((string) $key), $item));
            }

            return $patches;
        }

        throw new InvalidVisualSpecException('too_large');
    }

    /**
     * @param  list<TPatch>  $patches
     * @return list<array{type: 'visual_patch', visual_id: string, patches: list<TPatch>}>
     */
    private function group(string $visualId, array $patches): array
    {
        $events = [];
        $current = [];

        foreach ($patches as $patch) {
            if ($current !== [] && $this->eventBytes(['visual_id' => $visualId, 'patches' => [...$current, $patch]]) > self::EVENT_BUDGET_BYTES) {
                $events[] = ['type' => 'visual_patch', 'visual_id' => $visualId, 'patches' => $current];
                $current = [];
            }

            $current[] = $patch;
        }

        if ($current !== []) {
            $events[] = ['type' => 'visual_patch', 'visual_id' => $visualId, 'patches' => $current];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function eventBytes(array $event): int
    {
        return mb_strlen((string) json_encode((string) json_encode($event)));
    }

    private function pointerSegment(string $key): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $key);
    }

    /**
     * @return array{type: 'visual_end', visual_id: string, accepted: bool, fallback_text: string, reason: string|null}
     */
    private function rejected(string $visualId, string $reason): array
    {
        return ['type' => 'visual_end', 'visual_id' => $visualId, 'accepted' => false, 'fallback_text' => '', 'reason' => $reason];
    }

    /**
     * @return array{type: 'text', text: string}
     */
    private function text(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }
}
