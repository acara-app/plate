<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\GeneratesConversationTitle;
use App\Models\Conversation;
use App\Utilities\LanguageUtil;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\MessageRole;
use Throwable;

final readonly class GenerateConversationTitleAction
{
    public function __construct(private GeneratesConversationTitle $agent) {}

    public function handle(Conversation $conversation): void
    {
        if ($conversation->title !== Conversation::DEFAULT_TITLE) {
            return;
        }

        $firstMessage = $conversation->messages()
            ->where('role', MessageRole::User->value)
            ->orderBy('id')
            ->value('content');

        if (blank($firstMessage)) {
            return;
        }

        ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($conversation->user?->locale);

        try {
            $title = mb_trim($this->agent->generate($firstMessage, $language, $languageCode));
        } catch (Throwable) {
            return;
        }

        if ($title === '') {
            return;
        }

        $conversation->update(['title' => Str::limit($title, 120, '')]);
    }
}
