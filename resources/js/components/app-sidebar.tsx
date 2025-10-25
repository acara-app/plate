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
import { dashboard, privacy, terms } from '@/routes';
import mealPlans from '@/routes/meal-plans';
import biometrics from '@/routes/onboarding/biometrics';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
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
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
