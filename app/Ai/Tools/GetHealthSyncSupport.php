<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Actions\GetHealthSyncSupportContextAction;
use App\Ai\Attributes\AiToolSensitivity;
use App\Enums\DataSensitivity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

#[AiToolSensitivity(DataSensitivity::General)]
final readonly class GetHealthSyncSupport implements Tool
{
    public function __construct(
        private GetHealthSyncSupportContextAction $context,
    ) {}

    public function name(): string
    {
        return 'get_health_sync_support';
    }

    public function description(): string
    {
        return 'Retrieve authoritative Acara Health Sync product support details. Use for questions about automatic health data sync, Apple Health, HealthKit, the iPhone app, Android sync status, pairing, Mobile Sync, setup, App Store links, privacy, troubleshooting, or whether Acara Plate has a sync solution.';
    }

    public function handle(Request $request): string
    {
        /** @var string $topic */
        $topic = $request['topic'] ?? 'all';

        return json_encode([
            'success' => true,
            'topic' => $topic,
            'context' => $this->context->handle($topic),
        ]) ?: '{"success":false,"error":"Unable to encode Health Sync support context"}';
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()
                ->enum(['overview', 'setup', 'platform_support', 'troubleshooting', 'privacy', 'all'])
                ->description('Which Health Sync support topic to retrieve. Use "all" for broad questions or when the user asks whether Acara has a solution.')
                ->required()
                ->nullable(),
        ];
    }
}
