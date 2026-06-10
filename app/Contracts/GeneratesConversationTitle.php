<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Ai\Agents\ConversationTitleGeneratorAgent;
use Illuminate\Container\Attributes\Bind;

#[Bind(ConversationTitleGeneratorAgent::class)]
interface GeneratesConversationTitle
{
    public function generate(string $message, string $language, string $languageCode): string;
}
