<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::Sensitive)]
final readonly class ShowWeeklySteps implements Tool
{
    public const string NAME = 'show_weekly_steps';

    public function __construct(private GetHealthSummary $summary) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Show an inline chart of the signed-in user’s daily step counts over seven UTC dates and return the source readings for a short takeaway. Choose this when comparing daily steps would help the user. For general questions about steps, reply in prose.';
    }

    public function handle(Request $request): string
    {
        $arguments = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return $this->summary->handle(new Request([
            'type' => 'stepCount',
            'days' => 7,
            'date' => $arguments['date'] ?? now('UTC')->toDateString(),
        ]));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->required()->nullable()
                ->description('Last day of the seven-day range, YYYY-MM-DD. Use null for the current UTC date.'),
        ];
    }
}
