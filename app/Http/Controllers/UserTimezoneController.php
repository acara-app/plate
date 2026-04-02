<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\UpdateUserTimezoneAction;
use App\Http\Requests\UpdateTimezoneRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

final readonly class UserTimezoneController
{
    public function __construct(
        #[CurrentUser] private ?User $user,
        private UpdateUserTimezoneAction $action,
    ) {}

    public function update(UpdateTimezoneRequest $request): void
    {
        /** @var array<string, string> $validated */
        $validated = $request->validated();

        $request->session()->put('timezone', $validated['timezone']);

        if ($this->user instanceof User) {
            $this->action->handle($this->user, $validated['timezone']);
        }
    }
}
