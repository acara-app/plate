<?php

declare(strict_types=1);

namespace App\Data\Messaging;

use App\Enums\ChatPlatform;
use App\Models\UserChatPlatformLink;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapOutputName(SnakeCaseMapper::class)]
final class ChatPlatformIntegrationData extends Data
{
    public function __construct(
        public ChatPlatform $platform,
        public string $label,
        public string $botUsername,
        public string $deepLinkUrl,
        public string $linkingCommand,
        public bool $isConnected,
        public ?string $linkingToken,
        public ?CarbonInterface $tokenExpiresAt,
        public ?CarbonInterface $connectedAt,
    ) {}

    public static function fromLink(ChatPlatform $platform, ?UserChatPlatformLink $link): self
    {
        $adapter = $platform->adapter();
        $token = $link?->isTokenValid() === true ? $link->linking_token : null;

        return new self(
            platform: $platform,
            label: $platform->label(),
            botUsername: $adapter->botUsername(),
            deepLinkUrl: $adapter->deepLinkUrl(),
            linkingCommand: $adapter->linkingCommandFor($token ?? 'YOUR_TOKEN'),
            isConnected: $link?->isLinked() === true,
            linkingToken: $token,
            tokenExpiresAt: $link?->token_expires_at,
            connectedAt: $link?->linked_at,
        );
    }
}
