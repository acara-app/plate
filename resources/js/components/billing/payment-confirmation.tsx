import { Button } from '@/components/ui/button';
import billing from '@/routes/billing';
import { Link, usePoll } from '@inertiajs/react';
import { useEffect, useEffectEvent, useRef, useState } from 'react';

const paymentStatusMessages = {
    checking:
        'We’re confirming your payment. Your scans will unlock when payment is verified.',
    timeout:
        'Payment confirmation is taking longer than expected. Check again or open billing settings to view your subscription.',
    error: 'We couldn’t check your payment status. Check your connection and try again, or open billing settings.',
};

export function PaymentConfirmation() {
    const [status, setStatus] = useState<'checking' | 'timeout' | 'error'>(
        'checking',
    );
    const cancelRequest = useRef<(() => void) | null>(null);
    const { start, stop } = usePoll(
        3000,
        () => ({
            preserveErrors: true,
            onCancelToken({ cancel }) {
                cancelRequest.current = cancel;
            },
            onFinish() {
                cancelRequest.current = null;
            },
            onHttpException() {
                setStatus('error');

                return false;
            },
            onNetworkError() {
                setStatus('error');

                return false;
            },
        }),
        { autoStart: false, mode: 'rest' },
    );

    const controlPolling = useEffectEvent((running: boolean) => {
        if (running) {
            start();

            return;
        }

        stop();
        cancelRequest.current?.();
        cancelRequest.current = null;
    });

    useEffect(() => {
        if (status !== 'checking') {
            return;
        }

        controlPolling(true);
        const timeout = window.setTimeout(() => setStatus('timeout'), 60000);

        return () => {
            window.clearTimeout(timeout);
            controlPolling(false);
        };
    }, [status]);

    return (
        <div role="status" className="space-y-3 rounded-lg border p-4">
            <p>{paymentStatusMessages[status]}</p>
            {status !== 'checking' && (
                <Button variant="outline" onClick={() => setStatus('checking')}>
                    Check again
                </Button>
            )}
            <Link href={billing.index().url} className="block underline">
                Open billing settings
            </Link>
        </div>
    );
}
