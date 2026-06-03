<?php

declare(strict_types=1);

namespace App\Support\Streaming;

final class VercelChunkStreamer
{
    private bool $streamStarted = false;

    /**
     * @var array<string, bool>
     */
    private array $toolCalls = [];

    /**
     * @var array<array-key, mixed>|null
     */
    private ?array $deferredEnd = null;

    /**
     * @param  array<array-key, mixed>|null  $vercel
     */
    public function line(?array $vercel): ?string
    {
        if ($vercel === null || $vercel === []) {
            return null;
        }

        $type = $vercel['type'] ?? null;

        if ($type === 'start') {
            if ($this->streamStarted) {
                return null;
            }

            $this->streamStarted = true;
        }

        if ($type === 'tool-input-available') {
            $toolCallId = $vercel['toolCallId'] ?? null;

            if (is_string($toolCallId)) {
                $this->toolCalls[$toolCallId] = true;
            }
        }

        if ($type === 'tool-output-available') {
            $toolCallId = $vercel['toolCallId'] ?? null;

            if (! is_string($toolCallId) || ! isset($this->toolCalls[$toolCallId])) {
                return null;
            }
        }

        if ($type === 'finish') {
            $this->deferredEnd = $vercel;

            return null;
        }

        return $this->encode($vercel);
    }

    public function trailer(): string
    {
        $out = '';

        if ($this->deferredEnd !== null) {
            $out .= $this->encode($this->deferredEnd);
        }

        return $out."data: [DONE]\n\n";
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function encode(array $data): string
    {
        return 'data: '.json_encode($data)."\n\n";
    }
}
