import { usePoll } from '@inertiajs/react';

import { Progress } from '@/components/ui/progress';
import { JobTracking } from '@/types';

interface PageProps {
    jobTracking: JobTracking | null;
    [key: string]: unknown;
}

export function JobProcessingStatus({ jobTracking }: PageProps) {
    const isProcessing =
        jobTracking &&
        (jobTracking.status === 'pending' ||
            jobTracking.status === 'processing');

    // Poll every 2 seconds if job is processing
    usePoll(
        2000,
        {},
        {
            autoStart: !!isProcessing,
            keepAlive: false,
        },
    );

    if (!isProcessing) {
        return null;
    }

    return (
        <div className="space-y-4 rounded-lg bg-white p-6 shadow-lg dark:bg-gray-800">
            <div className="space-y-2">
                <div className="flex items-center justify-between">
                    <span className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Processing...
                    </span>
                    <span className="text-sm text-gray-500 dark:text-gray-400">
                        {jobTracking.progress}%
                    </span>
                </div>
                <Progress value={jobTracking.progress} className="h-2" />
            </div>
            {jobTracking.message && (
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    {jobTracking.message}
                </p>
            )}
        </div>
    );
}
