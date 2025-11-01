import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat',
        href: '/chat/create',
    },
];

export default function CreateChat() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col items-center justify-center rounded-xl border border-sidebar-border/70 p-8 dark:border-sidebar-border">
                <div className="text-center">
                    <h1 className="text-4xl font-bold tracking-tight text-foreground">
                        Coming Soon
                    </h1>
                    <p className="mt-4 text-lg text-muted-foreground">
                        Chat feature is currently under development
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
