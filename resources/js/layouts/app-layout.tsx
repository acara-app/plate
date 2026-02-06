import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    fixedHeight?: boolean;
}

export default ({
    children,
    breadcrumbs,
    fixedHeight,
    ...props
}: AppLayoutProps) => (
    <AppLayoutTemplate
        breadcrumbs={breadcrumbs}
        fixedHeight={fixedHeight}
        {...props}
    >
        {children}
    </AppLayoutTemplate>
);
