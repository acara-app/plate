<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateUserTimezoneAction;
use App\Models\User;
use App\Rules\ValidTimezone;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;

final readonly class UserTimezoneController
{
    public function __construct(
        #[CurrentUser] private ?User $user,
        private UpdateUserTimezoneAction $action,
    ) {}

    public function update(Request $request): void
    {
        /** @var array<string, string> $validated */
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'max:255', new ValidTimezone],
        ]);

        $request->session()->put('timezone', $validated['timezone']);

        if ($this->user instanceof User) {
            $this->action->handle($this->user, $validated['timezone']);
        }
    }
}
