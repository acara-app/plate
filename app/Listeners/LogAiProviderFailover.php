<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Events\AgentFailedOver;

final class LogAiProviderFailover
{
    public function handle(AgentFailedOver $event): void
    {
        Log::error('ai_provider_failed_over', [
            'agent' => $event->agent::class,
            'provider' => $event->provider->name(),
            'model' => $event->model,
            'exception' => $event->exception::class,
            'reason' => $this->reason($event),
        ]);
    }

    private function reason(AgentFailedOver $event): string
    {
        $previous = $event->exception->getPrevious();

        if (! $previous instanceof RequestException) {
            return $event->exception->getMessage();
        }

        $message = $previous->response->json('error.message');

        return Str::limit(mb_trim(is_string($message) ? $message : $previous->response->body()), 300);
    }
}
