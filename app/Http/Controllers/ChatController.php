<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;

final class ChatController
{
    public function create(): \Inertia\Response
    {
        return Inertia::render('chat/create-chat');
    }
}
