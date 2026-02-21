import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editIntegrations } from '@/routes/integrations';
import { edit as editPassword } from '@/routes/password';
import { show } from '@/routes/two-factor';
import { edit as editNotifications } from '@/routes/user-notifications';
import { edit } from '@/routes/user-profile';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { Bell, Lock, Palette, Puzzle, ShieldCheck, User } from 'lucide-react';
import { type PropsWithChildren, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

const getSidebarNavItems = (t: (key: string) => string): NavItem[] => [
    {
        title: t('settings_layout.nav.profile'),
        href: edit(),
        icon: User,
    },
    {
        title: t('settings_layout.nav.integrations'),
        href: editIntegrations(),
        icon: Puzzle,
    },
    {
        title: t('settings_layout.nav.password'),
        href: editPassword(),
        icon: Lock,
    },
    {
        title: t('settings_layout.nav.two_factor'),
        href: show(),
        icon: ShieldCheck,
    },
    {
        title: t('settings_layout.nav.notifications'),
        href: editNotifications(),
        icon: Bell,
    },
    {
        title: t('settings_layout.nav.appearance'),
        href: editAppearance(),
        icon: Palette,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { t } = useTranslation('common');
    const scrollRef = useRef<HTMLDivElement>(null);

    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;
    const sidebarNavItems = getSidebarNavItems(t);

    const isActive = (item: NavItem) =>
        currentPath ===
        (typeof item.href === 'string' ? item.href : item.href.url);

    // Auto-scroll the active tab into view on mobile
    useEffect(() => {
        if (scrollRef.current) {
            const activeEl = scrollRef.current.querySelector(
                '[data-active="true"]',
            );
            if (activeEl) {
                activeEl.scrollIntoView({
                    behavior: 'smooth',
                    inline: 'center',
                    block: 'nearest',
                });
            }
        }
    }, [currentPath]);

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('settings_layout.title')}
                description={t('settings_layout.description')}
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                {/* Mobile: Horizontal scrollable pill tab bar */}
                <div className="relative lg:hidden">
                    <div
                        ref={scrollRef}
                        className="no-scrollbar flex gap-2 overflow-x-auto pb-4"
                    >
                        {sidebarNavItems.map((item, index) => {
                            const active = isActive(item);
                            return (
                                <Link
                                    key={`${typeof item.href === 'string' ? item.href : item.href.url}-${index}`}
                                    href={item.href}
                                    data-active={active}
                                    className={cn(
                                        'inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3.5 py-2 text-sm font-medium transition-all duration-200',
                                        active
                                            ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                                            : 'border-border bg-background text-muted-foreground hover:border-foreground/20 hover:bg-accent hover:text-foreground',
                                    )}
                                >
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            );
                        })}
                    </div>
                    {/* Right fade gradient for scroll hint */}
                    <div className="pointer-events-none absolute top-0 right-0 h-full w-8 bg-linear-to-l from-background to-transparent" />
                </div>

                {/* Desktop: Vertical sidebar nav */}
                <aside className="hidden w-48 lg:block">
                    <nav className="flex flex-col gap-1">
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${typeof item.href === 'string' ? item.href : item.href.url}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': isActive(item),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
