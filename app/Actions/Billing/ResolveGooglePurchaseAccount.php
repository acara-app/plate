<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Actions\CreateUser;
use App\Actions\RecordSignUpAgreements;
use App\Exceptions\AccountLinkException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ResolveGooglePurchaseAccount
{
    public function __construct(
        private CreateUser $createUser,
        private RecordSignUpAgreements $recordAgreements,
    ) {}

    public function handle(string $email, string $googleId, ?string $name, bool $acceptedAgreements): ?User
    {
        $email = Str::lower($email);

        return DB::transaction(function () use ($email, $googleId, $name, $acceptedAgreements): ?User {
            $accounts = User::query()->whereRaw('lower(email) = ?', [$email])
                ->orWhere('google_id', $googleId)->lockForUpdate()->get();

            $user = $accounts->first();

            if ($user instanceof User) {
                throw_if(
                    $accounts->count() !== 1 || Str::lower($user->email) !== $email || ($user->google_id !== null && $user->google_id !== $googleId),
                    AccountLinkException::class,
                    'The Google identity linked to this account does not match. Use your email link to activate.',
                );

                throw_if(
                    $user->google_id === null && ! $user->hasVerifiedEmail(),
                    AccountLinkException::class,
                    'The email on your existing Acara Plate account has not been verified. Use your email link to activate.',
                );

                $user->update(['google_id' => $googleId, 'email_verified_at' => $user->email_verified_at ?? now()]);

                return $user;
            }

            if (! $acceptedAgreements) {
                return null;
            }

            $user = $this->createUser->handle([
                'email' => $email,
                'name' => $name,
                'email_verified_at' => now(),
                'google_id' => $googleId,
            ], null);
            $this->recordAgreements->handle($user);

            return $user;
        });
    }
}
