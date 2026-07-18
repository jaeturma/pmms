import { Link, usePage } from '@inertiajs/react';
import {
    Contact,
    FileCheck,
    Flag,
    Landmark,
    LayoutGrid,
    ListChecks,
    Medal,
    School,
    ScrollText,
    Trophy,
    UserCog,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import { dashboard } from '@/routes';
import { index as athletesIndex } from '@/routes/athletes';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as delegationsIndex } from '@/routes/delegations';
import { index as districtsIndex } from '@/routes/districts';
import { index as eligibilityIndex } from '@/routes/eligibility';
import { index as entriesIndex } from '@/routes/entries';
import { index as eventsIndex } from '@/routes/events';
import { index as meetsIndex } from '@/routes/meets';
import { index as personnelIndex } from '@/routes/personnel';
import { index as schoolsIndex } from '@/routes/schools';
import { index as sportsIndex } from '@/routes/sports';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Districts',
        href: districtsIndex(),
        icon: Landmark,
    },
    {
        title: 'Schools',
        href: schoolsIndex(),
        icon: School,
    },
    {
        title: 'Sports',
        href: sportsIndex(),
        icon: Trophy,
    },
    {
        title: 'Events',
        href: eventsIndex(),
        icon: Medal,
    },
    {
        title: 'Meets',
        href: meetsIndex(),
        icon: Flag,
    },
    {
        title: 'Delegations',
        href: delegationsIndex(),
        icon: UsersRound,
    },
    {
        title: 'Athletes',
        href: athletesIndex(),
        icon: Contact,
    },
    {
        title: 'Personnel',
        href: personnelIndex(),
        icon: UserCog,
    },
    {
        title: 'Entries',
        href: entriesIndex(),
        icon: ListChecks,
    },
    {
        title: 'Eligibility',
        href: eligibilityIndex(),
        icon: FileCheck,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Audit log',
        href: auditLogsIndex(),
        icon: ScrollText,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;

    const navItems =
        auth.user?.role === 'admin'
            ? [...mainNavItems, ...adminNavItems]
            : mainNavItems;

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
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
