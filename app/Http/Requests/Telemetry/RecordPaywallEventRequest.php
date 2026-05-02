<?php

declare(strict_types=1);

namespace App\Http\Requests\Telemetry;

use App\Enums\Telemetry\PaywallEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordPaywallEventRequest extends FormRequest
{
    /** @var array<int, string> */
    public const array CLIENT_EMITTABLE_EVENTS = [
        'paywall_shown',
        'paywall_dismissed',
        'upgrade_clicked',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::in(self::CLIENT_EMITTABLE_EVENTS)],
            'payload' => ['nullable', 'array'],
            'payload.trigger' => ['nullable', 'string', Rule::in(['cap', 'feature'])],
            'payload.feature' => ['nullable', 'string'],
            'payload.limit_type' => ['nullable', 'string', Rule::in(['rolling', 'weekly', 'monthly'])],
            'payload.tier_target' => ['nullable', 'string', Rule::in(['basic', 'plus'])],
            'payload.surface' => ['nullable', 'string'],
            'payload.time_open_ms' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function event(): PaywallEvent
    {
        /** @var string $event */
        $event = $this->validated('event');

        return PaywallEvent::from($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->validated('payload', []);

        return $payload;
    }
}
