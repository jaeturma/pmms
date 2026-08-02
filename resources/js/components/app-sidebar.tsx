import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    BarChart3,
    Boxes,
    CalendarDays,
    ClipboardList,
    Contact,
    Crown,
    FileCheck,
    Flag,
    Gavel,
    Landmark,
    LayoutGrid,
    ListChecks,
    MapPin,
    Medal,
    Megaphone,
    Milestone,
    School,
    ScrollText,
    Settings,
    Swords,
    Trophy,
    TriangleAlert,
    UserCog,
    Users,
    UsersRound,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { SidebarMeetCard } from '@/components/sidebar-meet-card';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { pluralizeAreaLabel } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as announcementsIndex } from '@/routes/announcements';
import { index as athletesIndex } from '@/routes/athletes';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { index as delegationsIndex } from '@/routes/delegations';
import { index as districtsIndex } from '@/routes/districts';
import { edit as divisionEdit } from '@/routes/division';
import { index as eligibilityIndex } from '@/routes/eligibility';
import { index as entriesIndex } from '@/routes/entries';
import { index as equipmentIndex } from '@/routes/equipment';
import { index as eventsIndex } from '@/routes/events';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as managementIndex } from '@/routes/management';
import { index as managementTeamsIndex } from '@/routes/management-teams';
import { index as matchesIndex } from '@/routes/matches';
import { index as meetSportAssignmentsIndex } from '@/routes/meet-sport-assignments';
import { index as meetsIndex } from '@/routes/meets';
import { index as personnelIndex } from '@/routes/personnel';
import { index as protestsIndex } from '@/routes/protests';
import { index as resultsIndex } from '@/routes/results';
import { index as scheduleIndex } from '@/routes/schedule';
import { index as schoolDistrictsIndex } from '@/routes/school-districts';
import { index as schoolsIndex } from '@/routes/schools';
import { index as sportsIndex } from '@/routes/sports';
import { edit as systemSettingsEdit } from '@/routes/system-settings';
import { index as tallyIndex } from '@/routes/tally';
import { index as venuesIndex } from '@/routes/venues';
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
        title: 'School districts',
        href: schoolDistrictsIndex(),
        icon: Milestone,
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
        title: 'Venues',
        href: venuesIndex(),
        icon: MapPin,
    },
    {
        title: 'Schedule',
        href: scheduleIndex(),
        icon: CalendarDays,
    },
    {
        title: 'Tournament Assignments',
        href: meetSportAssignmentsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Management Teams',
        href: managementTeamsIndex(),
        icon: Users,
    },
    {
        title: 'Matches',
        href: matchesIndex(),
        icon: Swords,
    },
    {
        title: 'Results',
        href: resultsIndex(),
        icon: Award,
    },
    {
        title: 'Medal tally',
        href: tallyIndex(),
        icon: Crown,
    },
    {
        title: 'Protests',
        href: protestsIndex(),
        icon: Gavel,
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

const managerNavItems: NavItem[] = [
    {
        title: 'Management',
        href: managementIndex(),
        icon: BarChart3,
    },
    // Placed in the admin/organizer tier rather than mainNavItems (unlike
    // Management Teams, which is view-open to everyone): SupplyPolicy
    // also grants access to a meet's Supply Team members who aren't
    // admin/organizer, but the sidebar only knows the coarse UserRole,
    // not per-committee ManagementTeamMember rows — those members can
    // still reach /equipment directly, they just don't get a sidebar
    // link for it. A dedicated shared-prop visibility flag would close
    // that gap but is genuinely out of this WP's UI scope.
    {
        title: 'Equipment',
        href: equipmentIndex(),
        icon: Boxes,
    },
    {
        title: 'Incidents',
        href: incidentsIndex(),
        icon: TriangleAlert,
    },
    {
        title: 'Announcements',
        href: announcementsIndex(),
        icon: Megaphone,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Audit log',
        href: auditLogsIndex(),
        icon: ScrollText,
    },
    {
        title: 'Division settings',
        href: divisionEdit(),
        icon: Landmark,
    },
    {
        title: 'System settings',
        href: systemSettingsEdit(),
        icon: Settings,
    },
];

const technicalOfficialNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Matches',
        href: matchesIndex(),
        icon: Swords,
    },
];

export function AppSidebar() {
    const { auth, division } = usePage().props;

    const role = auth.user?.role;

    // A Technical Official only runs live scoring for their assigned
    // sport(s) — the full admin registry (Districts/Schools/Delegations/
    // Entries/Eligibility/etc.) isn't their job, so they get a minimal nav
    // instead of `mainNavItems`, rather than a full console they'd mostly
    // see 403s on.
    const navItems =
        role === 'technical_official'
            ? technicalOfficialNavItems
            : [
                  ...mainNavItems.map((item) =>
                      item.title === 'Districts'
                          ? {
                                ...item,
                                title: pluralizeAreaLabel(division.areaLabel),
                            }
                          : item,
                  ),
                  ...(role === 'admin' || role === 'organizer'
                      ? managerNavItems
                      : []),
                  ...(role === 'admin' ? adminNavItems : []),
              ];

    return (
        <Sidebar collapsible="icon">
            <SidebarHeader className="p-3">
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

            <SidebarFooter className="p-3">
                <SidebarMeetCard />
            </SidebarFooter>
        </Sidebar>
    );
}
