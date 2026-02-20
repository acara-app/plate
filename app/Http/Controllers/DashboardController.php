<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(
        #[CurrentUser] private User $user,
    ) {}

    public function show(): Response
    {
        $recentConversations = $this->user->conversations()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Conversation $conversation): array => [
                'id' => $conversation->id,
                'title' => $conversation->title ?: 'New Conversation',
                'updated_at' => $conversation->updated_at->diffForHumans(),
            ]);

        $profile = $this->user->profile;

        return Inertia::render('dashboard', [
            'recentConversations' => $recentConversations,
            'hasGlucoseData' => $profile && $this->user->healthEntries()->whereNotNull('glucose_value')->exists(),
            'hasHealthConditions' => $profile && $profile->healthConditions()->exists(),
        ]);
    }
}
