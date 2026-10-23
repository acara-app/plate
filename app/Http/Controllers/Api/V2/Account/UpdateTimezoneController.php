<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Account;

use App\Actions\UpdateUserTimezoneAction;
use App\Http\Requests\UpdateTimezoneRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

final readonly class UpdateTimezoneController
{
    public function __construct(private UpdateUserTimezoneAction $updateUserTimezone) {}

    public function __invoke(UpdateTimezoneRequest $request, #[CurrentUser] User $user): Response
    {
        $this->updateUserTimezone->handle($user, $request->string('timezone')->toString());

        return response()->noContent();
    }
}
