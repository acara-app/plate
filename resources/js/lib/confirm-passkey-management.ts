import { edit } from '@/routes/passkeys';
import { confirmation } from '@/routes/password';
import { router } from '@inertiajs/react';

export async function confirmPasskeyManagement(): Promise<boolean> {
    const response = await fetch(confirmation.url(), {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (response.status === 401 || response.status === 419) {
        router.visit(edit());
        return false;
    }

    if (!response.ok) {
        throw new Error('Unable to check identity confirmation.');
    }

    const status: { confirmed: boolean } = await response.json();
    if (!status.confirmed) {
        router.visit(edit());
        return false;
    }

    return true;
}
