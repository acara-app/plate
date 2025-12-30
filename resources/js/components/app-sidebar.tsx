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
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: dashboard(),
        icon: HeartIcon,
    },
    {
        title: 'Meal Plans',
        href: mealPlans.index(),
        icon: CalendarHeartIcon,
    },
    {
        title: 'Diabetes Insights',
        href: diabetesLog.insights(),
        icon: TrendingUp,
    },
    {
        title: 'Diabetes Log',
        href: DashboardDiabetesLogController().url,
        icon: ActivityIcon,
    },
    {
        title: 'Update Your Info',
        href: biometrics.show(),
        icon: LeafIcon,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Terms of Service',
        href: terms.url(),
        icon: FileText,
    },
    {
        title: 'Privacy Policy',
        href: privacy.url(),
        icon: ShieldCheck,
    },
];

export function AppSidebar() {
    const { currentUser } = useSharedProps();

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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                {!currentUser?.is_verified && <UpgradeButton />}
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
