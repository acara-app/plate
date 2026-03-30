<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class UpdateHouseholdContext implements Tool
{
    public function name(): string
    {
        return 'update_household_context';
    }

    public function description(): string
    {
        return 'Get or update the user\'s household/family context. This is free-text describing family members, their ages, dietary needs, allergies, and preferences. Use "get" to retrieve current household info, or "update" to save new household context. When updating, write a clean summary that preserves existing info and incorporates new details.';
    }

    public function handle(Request $request): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return (string) json_encode(['error' => 'User not authenticated']);
        }

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<string, mixed> $data */
        $data = $request->toArray();

        /** @var string $action */
        $action = $data['action'] ?? 'get';

        return match ($action) {
            'get' => (string) json_encode([
                'success' => true,
                'household_context' => $profile->household_context,
                'has_household_info' => $profile->household_context !== null && $profile->household_context !== '',
            ]),
            'update' => $this->updateContext($profile, $data),
            default => (string) json_encode(['error' => 'Unknown action: '.$action]),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()->required()
                ->enum(['get', 'update'])
                ->description('Action to perform: "get" to retrieve current household context, "update" to save new context.'),
            'household_context' => $schema->string()->required()->nullable()
                ->description('Free-text description of household/family members, their ages, dietary needs, allergies, and preferences. Maximum 2000 characters. Required when action is "update".'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateContext(UserProfile $profile, array $data): string
    {
        if (! isset($data['household_context']) || ! is_string($data['household_context'])) {
            return (string) json_encode(['error' => 'household_context is required for update action.']);
        }

        $context = mb_substr($data['household_context'], 0, 2000);

        $profile->update(['household_context' => $context]);
        $profile->refresh();

        return (string) json_encode([
            'success' => true,
            'message' => 'Household context updated successfully.',
            'household_context' => $profile->household_context,
        ]);
    }
}
