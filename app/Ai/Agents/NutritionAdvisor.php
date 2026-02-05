<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Models\History;
use App\Models\User;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

final class NutritionAdvisor implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public User $user) {}


    public function instructions(): Stringable|string
    {
        return 'You are a nutrition advisor.';
    }


    public function messages(): iterable
    {
        return History::query()->where('user_id', $this->user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->map(fn($message): Message => new Message($message->role, $message->content))->all();
    }

    /**
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }
}
