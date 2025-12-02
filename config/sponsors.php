<?php

declare(strict_types=1);

return [

    /**
     * --------------------------------------------------------------------------
     * The list of admin emails
     * --------------------------------------------------------------------------
     * Here you may define the list of admin emails that are "fixed" and
     * should all be considered always as admins regardless of the user's
     * admin status. The user emails should be comma-separated.
     */
    'admin_emails' => collect(explode(',', (string) env('ADMIN_EMAILS', '')))->map(
        fn (string $email): string => mb_trim($email)
    )->filter()->values()->all(),
];
