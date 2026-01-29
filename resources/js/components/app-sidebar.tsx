import DashboardDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/DashboardDiabetesLogController';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { UpgradeButton } from '@/components/upgrade-button';
import useSharedProps from '@/hooks/use-shared-props';
import { dashboard, privacy, terms } from '@/routes';
import diabetesLog from '@/routes/diabetes-log';
import mealPlans from '@/routes/meal-plans';
import biometrics from '@/routes/onboarding/biometrics';
import profileDietaryPreferences from '@/routes/profile/dietary-preferences';
import profileHealthConditions from '@/routes/profile/health-conditions';
import profileMedications from '@/routes/profile/medications';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
    ActivityIcon,
    CalendarHeartIcon,
    FileText,
    HeartIcon,
    LeafIcon,
    ShieldCheck,
    TrendingUp,
    UserPen,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import AppLogo from './app-logo';
import { Separator } from './ui/separator';

const getMainNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('sidebar.nav.home'),
        href: dashboard(),
        icon: HeartIcon,
    },
    {
        title: t('sidebar.nav.meal_plans'),
        href: mealPlans.index(),
        icon: CalendarHeartIcon,
    },
];

const getProfileNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('sidebar.nav.update_info'),
        href: biometrics.show(),
        icon: UserPen,
    },
    {
        title: t('sidebar.nav.dietary_preferences'),
        href: profileDietaryPreferences.show.url(),
        icon: LeafIcon,
    },
    {
        title: t('sidebar.nav.health_conditions'),
        href: profileHealthConditions.show.url(),
        icon: ShieldCheck,
    },
    {
        title: t('sidebar.nav.medications'),
        href: profileMedications.show.url(),
        icon: ActivityIcon,
    },
    {
        title: t('sidebar.nav.diabetes_insights'),
        href: diabetesLog.insights(),
        icon: TrendingUp,
    },
    {
        title: t('sidebar.nav.diabetes_log'),
        href: DashboardDiabetesLogController().url,
        icon: ActivityIcon,
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
    const mainNavItems = getMainNavItems(t);
    const profileNavItems = getProfileNavItems(t);
    const footerNavItems = getFooterNavItems(t);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={mainNavItems}
                    label={t('sidebar.nav.planning')}
                />
                <Separator />
                <NavMain
                    items={profileNavItems}
                    label={t('sidebar.nav.context')}
                />
            </SidebarContent>

            <SidebarFooter>
                {!currentUser?.is_verified && enablePremiumUpgrades && (
                    <UpgradeButton />
                )}
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
