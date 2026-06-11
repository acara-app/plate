import DashboardHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/DashboardHealthEntryController';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { UpgradeButton } from '@/components/upgrade-button';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import { dashboard, privacy, terms } from '@/routes';
import chat from '@/routes/chat';
import integrations from '@/routes/integrations';
import mealPlans from '@/routes/meal-plans';
import mobileSync from '@/routes/mobile-sync';
import biometrics from '@/routes/onboarding/biometrics';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    ActivityIcon,
    CalendarHeartIcon,
    FileText,
    MessageCircle,
    MessageSquarePlus,
    PanelLeftClose,
    Plug,
    ShieldCheck,
    Smartphone,
    UserPen,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import AppLogo from './app-logo';
import { Separator } from './ui/separator';

const getMainNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('sidebar.nav.new_chat'),
        href: dashboard(),
        icon: MessageSquarePlus,
    },
    {
        title: t('sidebar.nav.chats'),
        href: chat.index(),
        icon: MessageCircle,
    },
];

const getHealthNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('sidebar.nav.meal_plans'),
        href: mealPlans.index(),
        icon: CalendarHeartIcon,
    },
    {
        title: t('sidebar.nav.health_entries'),
        href: DashboardHealthEntryController().url,
        icon: ActivityIcon,
    },
];

const getProfileNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('sidebar.nav.update_info'),
        href: biometrics.show(),
        icon: UserPen,
    },
    {
        title: t('sidebar.nav.mobile_sync'),
        href: mobileSync.edit(),
        icon: Smartphone,
    },
    {
        title: t('sidebar.nav.integrations'),
        href: integrations.edit(),
        icon: Plug,
    },
];

const getFooterNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('sidebar.nav.terms'),
        href: terms.url(),
        icon: FileText,
    },
    {
        title: t('sidebar.nav.privacy'),
        href: privacy.url(),
        icon: ShieldCheck,
    },
];

export function AppSidebar() {
    const { currentUser, enablePremiumUpgrades } = useSharedProps();
    const { t } = useTranslation('common');
    const { state, isMobile, toggleSidebar } = useSidebar();
    const mainNavItems = getMainNavItems(t);
    const healthNavItems = getHealthNavItems(t);
    const profileNavItems = getProfileNavItems(t);
    const footerNavItems = getFooterNavItems(t);

    const isCollapsed = !isMobile && state === 'collapsed';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        {isCollapsed ? (
                            <SidebarMenuButton
                                size="lg"
                                onClick={toggleSidebar}
                                tooltip={t('sidebar.open')}
                                aria-label={t('sidebar.open')}
                            >
                                <AppLogo
                                    showText={false}
                                    className="transition-all duration-200 hover:scale-105 hover:bg-emerald-100 hover:ring-emerald-300 hover:shadow-md dark:hover:bg-emerald-900/60 dark:hover:ring-emerald-700"
                                />
                            </SidebarMenuButton>
                        ) : (
                            <div className="flex w-full items-center justify-between gap-2">
                                <SidebarMenuButton
                                    size="lg"
                                    asChild
                                    className="justify-start"
                                >
                                    <Link href={dashboard()} prefetch>
                                        <AppLogo showText={false} />
                                    </Link>
                                </SidebarMenuButton>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={toggleSidebar}
                                            aria-label={t('sidebar.close')}
                                            className={cn(
                                                'size-7 shrink-0',
                                            )}
                                        >
                                            <PanelLeftClose className="size-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent side="right">
                                        {t('sidebar.close')}
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        )}
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <Separator />
                <NavMain
                    items={healthNavItems}
                    label={t('sidebar.nav.health')}
                />
                <Separator />
                <NavMain
                    items={profileNavItems}
                    label={t('sidebar.nav.settings')}
                />
            </SidebarContent>

            <SidebarFooter>
                <SidebarMenu>
                    {!currentUser?.is_verified && enablePremiumUpgrades && (
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                asChild
                                className="rounded-lg border border-purple-300 bg-purple-50 p-3 hover:bg-purple-100 hover:text-purple-900 dark:border-purple-700 dark:bg-purple-950/50 dark:hover:bg-purple-900/50 dark:hover:text-purple-100"
                                tooltip={{ children: 'Upgrade' }}
                            >
                                <UpgradeButton />
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    )}
                </SidebarMenu>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
