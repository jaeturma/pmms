import { Link } from '@inertiajs/react';
import {
    Contact,
    Flag,
    Landmark,
    LayoutGrid,
    Medal,
    School,
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
import { index as delegationsIndex } from '@/routes/delegations';
import { index as districtsIndex } from '@/routes/districts';
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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
