import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { LifecycleBanner } from '@/components/billing/lifecycle-banner';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

interface AppSidebarLayoutProps {
    breadcrumbs?: BreadcrumbItem[];
    fixedHeight?: boolean;
}

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
    fixedHeight = false,
}: PropsWithChildren<AppSidebarLayoutProps>) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className={cn(
                    'overflow-x-hidden',
                    fixedHeight && 'h-svh overflow-hidden',
                )}
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <LifecycleBanner className="mx-4 mt-4" />
                {children}
            </AppContent>
        </AppShell>
    );
}
