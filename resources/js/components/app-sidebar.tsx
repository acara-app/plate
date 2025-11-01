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
import { dashboard, privacy, terms } from '@/routes';
import glucoseReading from '@/routes/glucose';
import mealPlans from '@/routes/meal-plans';
import biometrics from '@/routes/onboarding/biometrics';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    ActivityIcon,
    CalendarHeartIcon,
    FileText,
    HeartIcon,
    LeafIcon,
    ShieldCheck,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Home',
        href: dashboard(),
        icon: HeartIcon,
    },
    {
        title: 'Weekly Meal Plans',
        href: mealPlans.weekly(),
        icon: CalendarHeartIcon,
    },
    {
        title: 'Glucose Tracking',
        href: glucoseReading.dashboard(),
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
    const { auth } = usePage<SharedData>().props;
    const isSubscribed = auth.subscribed;

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
                {!isSubscribed && <UpgradeButton />}
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
