<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MobileSyncDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MobileSyncController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $pendingDevice = $user->mobileSyncDevices()
            ->active()
            ->pending()
            ->first();

        return Inertia::render('mobile-sync/edit', [
            'devices' => $user->mobileSyncDevices()
                ->active()
                ->paired()
                ->get()
                ->map(fn (MobileSyncDevice $device): array => [
                    'id' => $device->id,
                    'device_name' => $device->device_name,
                    'paired_at' => $device->paired_at?->toIso8601String(),
                    'last_synced_at' => $device->last_synced_at?->toIso8601String(),
                ]),
            'pending_token' => $pendingDevice?->linking_token,
            'token_expires_at' => $pendingDevice?->token_expires_at?->toIso8601String(),
            'instance_url' => config()->string('app.url'),
        ]);
    }

    public function generateToken(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);

        $user->mobileSyncDevices()
            ->active()
            ->pending()
            ->update(['is_active' => false]);

        $device = $user->mobileSyncDevices()->create([
            'is_active' => true,
        ]);

        $device->generateToken();

        return to_route('mobile-sync.edit');
    }

    public function disconnect(Request $request, MobileSyncDevice $mobileSyncDevice): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 401);
        abort_if($mobileSyncDevice->user_id !== $user->id, 403);

        $mobileSyncDevice->update(['is_active' => false]);

        $user->tokens()
            ->where('name', 'mobile-sync:'.$mobileSyncDevice->id)
            ->delete();

        return to_route('mobile-sync.edit');
    }
}
