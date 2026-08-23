import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    BarChart3,
    BedDouble,
    Boxes,
    Bus,
    CalendarDays,
    ClipboardList,
    Contact,
    Crown,
    FileCheck,
    Flag,
    Gavel,
    Landmark,
    LayoutGrid,
    LifeBuoy,
    ListChecks,
    MapPin,
    Medal,
    Megaphone,
    Milestone,
    Siren,
    School,
    ScrollText,
    Settings,
    Stethoscope,
    Swords,
    Trophy,
    TriangleAlert,
    UserCog,
    Users,
    UsersRound,
    Utensils,
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
import { index as billetingIndex } from '@/routes/billeting';
import { index as delegationsIndex } from '@/routes/delegations';
import { index as districtsIndex } from '@/routes/districts';
import { edit as divisionEdit } from '@/routes/division';
import { index as drrmPlansIndex } from '@/routes/drrm-plans';
import { index as eligibilityIndex } from '@/routes/eligibility';
import { index as emergencyIncidentsIndex } from '@/routes/emergency-incidents';
import { index as entriesIndex } from '@/routes/entries';
import { index as equipmentIndex } from '@/routes/equipment';
import { index as eventsIndex } from '@/routes/events';
import { index as foodIndex } from '@/routes/food';
import { index as incidentsIndex } from '@/routes/incidents';
import { index as managementIndex } from '@/routes/management';
import { index as managementTeamsIndex } from '@/routes/management-teams';
import { index as matchesIndex } from '@/routes/matches';
import { index as medicalIndex } from '@/routes/medical';
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
import { index as transportIndex } from '@/routes/transport';
import { index as venuesIndex } from '@/routes/venues';
import type { NavItem, NavSection } from '@/types';

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
    // Same disclosed sidebar-visibility gap as Equipment above — Food/
    // Billeting/Transport Team members and (for Billeting/Transport) a
    // DelegationOfficer's own read-only access don't get a sidebar link,
    // only direct-URL access, since the sidebar can't see per-committee
    // ManagementTeamMember rows without a dedicated shared prop.
    {
        title: 'Food',
        href: foodIndex(),
        icon: Utensils,
    },
    {
        title: 'Billeting',
        href: billetingIndex(),
        icon: BedDouble,
    },
    {
        title: 'Transport',
        href: transportIndex(),
        icon: Bus,
    },
    {
        title: 'Medical',
        href: medicalIndex(),
        icon: Stethoscope,
    },
    {
        title: 'DRRM Plans',
        href: drrmPlansIndex(),
        icon: LifeBuoy,
    },
    {
        title: 'Emergency Incidents',
        href: emergencyIncidentsIndex(),
        icon: Siren,
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
        title: 'Users and Roles',
        href: '/system/users',
        icon: Users,
    },
    {
        title: 'Account provisions',
        href: '/account-provisions',
        icon: UserCog,
    },
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

// A Tournament Manager builds and runs their own sport's schedule/matches/
// results (Phase 13) — a wider job than a Technical Official's live-
// scoring-only scope, so it gets its own minimal nav rather than reusing
// `technicalOfficialNavItems` or the full `mainNavItems` registry console.
const tournamentManagerNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Entries',
        href: entriesIndex(),
        icon: ListChecks,
    },
    {
        title: 'Schedule',
        href: scheduleIndex(),
        icon: CalendarDays,
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
];

export function AppSidebar() {
    const { auth, division } = usePage().props;

    const role = auth.user?.role;
    const teamTypes: string[] = auth.user?.team_types ?? [];
    const canManageAccounts = auth.user?.can_manage_accounts ?? false;
    const canManageAnnouncements = auth.user?.can_manage_announcements ?? false;
    const canManagePersonnel = auth.user?.can_manage_personnel ?? false;
    const canFileProtest = auth.user?.can_file_protest ?? false;
    const canViewManagementReports =
        auth.user?.can_view_management_reports ?? false;
    const canReviewCoaches = auth.user?.can_review_coaches ?? false;
    const canRequestCoachEnrollment =
        auth.user?.can_request_coach_enrollment ?? false;
    const canViewTournamentAthletes =
        auth.user?.can_view_tournament_athletes ?? false;
    const canViewSystemLogs = auth.user?.can_view_system_logs ?? false;

    // A Technical Official only runs live scoring for their assigned
    // sport(s) — the full admin registry (Districts/Schools/Delegations/
    // Entries/Eligibility/etc.) isn't their job, so they get a minimal nav
    // instead of `mainNavItems`, rather than a full console they'd mostly
    // see 403s on.
    const roleNavItems =
        role === 'technical_official'
            ? technicalOfficialNavItems
            : role === 'tournament_manager'
              ? tournamentManagerNavItems
              : role === 'delegation_officer'
                ? mainNavItems.filter((item) =>
                      [
                          'Dashboard',
                          'Schedule',
                          'Results',
                          'Medal tally',
                          'Delegations',
                          'Athletes',
                          'Personnel',
                          'Entries',
                          'Eligibility',
                      ].includes(item.title),
                  )
                : role === 'coach'
                  ? mainNavItems.filter((item) =>
                        [
                            'Dashboard',
                            'Schedule',
                            'Results',
                            'Medal tally',
                            'Athletes',
                            'Entries',
                        ].includes(item.title),
                    )
                  : role === 'viewer'
                    ? mainNavItems.filter((item) =>
                          [
                              'Dashboard',
                              'Schedule',
                              'Results',
                              'Medal tally',
                          ].includes(item.title),
                      )
                    : mainNavItems;

    const labeledItems = roleNavItems.map((item) =>
        item.title === 'Districts'
            ? { ...item, title: pluralizeAreaLabel(division.areaLabel) }
            : item,
    );
    const byTitle = (titles: string[], pool: NavItem[] = labeledItems) =>
        titles
            .map((title) => pool.find((item) => item.title === title))
            .filter((item): item is NavItem => item !== undefined);

    const navItems: NavItem[] = byTitle(['Dashboard']);
    const navSections: NavSection[] = [];

    if (role === 'admin') {
        navSections.push(
            {
                title: 'Meet setup',
                icon: Settings,
                items: byTitle([
                    pluralizeAreaLabel(division.areaLabel),
                    'School districts',
                    'Schools',
                    'Sports',
                    'Events',
                    'Meets',
                    'Venues',
                    'Tournament Assignments',
                    'Management Teams',
                ]),
            },
            {
                title: 'Competition',
                icon: Trophy,
                items: byTitle([
                    'Schedule',
                    'Matches',
                    'Results',
                    'Medal tally',
                    'Protests',
                ]),
            },
            {
                title: 'Registration',
                icon: UsersRound,
                items: byTitle([
                    'Delegations',
                    'Athletes',
                    'Personnel',
                    'Entries',
                    'Eligibility',
                ]),
            },
            {
                title: 'Meet operations',
                icon: LifeBuoy,
                items: managerNavItems.filter(
                    (item) =>
                        !['Management', 'Announcements'].includes(item.title),
                ),
            },
            {
                title: 'Monitoring',
                icon: BarChart3,
                items: managerNavItems.filter((item) =>
                    ['Management', 'Announcements'].includes(item.title),
                ),
            },
        );
        navSections.push({
            title: 'System',
            icon: Settings,
            items: adminNavItems,
        });
    } else if (role === 'organizer') {
        navSections.push(
            {
                title: 'Meet setup',
                icon: Settings,
                items: byTitle(['Events', 'Venues']),
            },
            {
                title: 'Competition',
                icon: Trophy,
                items: byTitle([
                    'Schedule',
                    'Matches',
                    'Results',
                    'Medal tally',
                    ...(canViewTournamentAthletes ? ['Entries'] : []),
                ]),
            },
            {
                title: 'Registration',
                icon: UsersRound,
                items: byTitle([
                    'Delegations',
                    'Athletes',
                    ...(canManagePersonnel ? ['Personnel'] : []),
                ]),
            },
            {
                title: 'Meet operations',
                icon: LifeBuoy,
                items: managerNavItems.filter(
                    (item) =>
                        ['Announcements', 'Billeting', 'Transport'].includes(
                            item.title,
                        ) ||
                        (item.title === 'Equipment' &&
                            teamTypes.includes('supply')) ||
                        (item.title === 'Medical' &&
                            teamTypes.includes('medical')) ||
                        (['DRRM Plans', 'Emergency Incidents'].includes(
                            item.title,
                        ) &&
                            teamTypes.includes('drrm')),
                ),
            },
        );

        if (canManageAccounts) {
            navSections[0].items.push(
                ...byTitle(['Schools', 'Tournament Assignments']),
            );
            navSections[1].items.push(...byTitle(['Protests']));
        }

        if (teamTypes.includes('division_screening_and_accreditation')) {
            navSections.push({
                title: 'DSAC',
                icon: FileCheck,
                items: byTitle(['Eligibility']),
            });
        }
    } else if (role === 'technical_official' || role === 'tournament_manager') {
        navSections.push({
            title: 'Competition',
            icon: Trophy,
            items: labeledItems.filter((item) => item.title !== 'Dashboard'),
        });
    } else if (role === 'delegation_officer') {
        navSections.push(
            {
                title: 'Registration',
                icon: UsersRound,
                items: byTitle([
                    'Delegations',
                    'Athletes',
                    'Personnel',
                    'Entries',
                    'Eligibility',
                ]),
            },
            {
                title: 'Competition',
                icon: Trophy,
                items: byTitle([
                    'Schedule',
                    'Results',
                    'Medal tally',
                    ...(canFileProtest ? ['Protests'] : []),
                ]),
            },
        );
    } else if (role === 'coach') {
        navSections.push(
            {
                title: 'Team',
                icon: UsersRound,
                items: byTitle(['Athletes', 'Entries']),
            },
            {
                title: 'Competition',
                icon: Trophy,
                items: byTitle(['Schedule', 'Results', 'Medal tally']),
            },
        );
    } else {
        const relevantOperations = managerNavItems.filter(
            (item) =>
                (item.title === 'Medical' && teamTypes.includes('medical')) ||
                (item.title === 'Food' && teamTypes.includes('food')) ||
                (item.title === 'Billeting' &&
                    teamTypes.includes('billeting')) ||
                (item.title === 'Transport' &&
                    teamTypes.includes('transport')) ||
                (item.title === 'Equipment' && teamTypes.includes('supply')) ||
                (['DRRM Plans', 'Emergency Incidents'].includes(item.title) &&
                    teamTypes.includes('drrm')),
        );
        navSections.push({
            title: 'Competition',
            icon: Trophy,
            items: byTitle(['Schedule', 'Results', 'Medal tally']),
        });
        if (auth.user?.can_manage_school_master_data) {
            navSections.push({
                title: 'Registry',
                icon: School,
                items: byTitle(['Schools'], mainNavItems),
            });
        }
        if (teamTypes.includes('division_screening_and_accreditation')) {
            navSections.push({
                title: 'DSAC',
                icon: FileCheck,
                items: byTitle(['Athletes', 'Eligibility'], mainNavItems),
            });
        }
        if (relevantOperations.length)
            navSections.push({
                title: 'Assigned operations',
                icon: LifeBuoy,
                items: relevantOperations,
            });
    }

    if (canManageAccounts && role !== 'admin') {
        navSections.push({
            title: 'System',
            icon: Settings,
            items: adminNavItems.filter((item) =>
                [
                    'Users and Roles',
                    ...(canViewSystemLogs ? ['Audit log'] : []),
                ].includes(item.title),
            ),
        });
    } else if (canViewSystemLogs && role !== 'admin') {
        navSections.push({
            title: 'System',
            icon: Settings,
            items: adminNavItems.filter((item) => item.title === 'Audit log'),
        });
    }

    if (canViewManagementReports && role !== 'admin' && role !== 'organizer') {
        navSections.push({
            title: 'Monitoring',
            icon: BarChart3,
            items: byTitle(['Management'], managerNavItems),
        });
    }

    if (canManageAnnouncements && role !== 'admin' && role !== 'organizer') {
        navSections.push({
            title: 'Information',
            icon: Megaphone,
            items: byTitle(['Announcements'], managerNavItems),
        });
    }

    if (canReviewCoaches || canRequestCoachEnrollment) {
        const registration = navSections.find(
            (section) => section.title === 'Registration',
        );
        const coachItem: NavItem = {
            title: 'Coach',
            href: '/coach/assignment-requests',
            icon: UserCog,
        };
        const recentActivityItem: NavItem = {
            title: 'Recent Activity',
            href: auditLogsIndex(),
            icon: ScrollText,
        };
        if (registration) {
            registration.items.push(coachItem);
            if (role === 'coach') registration.items.push(recentActivityItem);
        } else
            navSections.push({
                title: 'Registration',
                icon: UsersRound,
                items:
                    role === 'coach'
                        ? [coachItem, recentActivityItem]
                        : [coachItem],
            });
    }

    if (canViewTournamentAthletes) {
        const registration = navSections.find(
            (section) => section.title === 'Registration',
        );
        const athleteItems = byTitle(['Athletes', 'Eligibility'], mainNavItems);
        if (registration) {
            athleteItems.forEach((item) => {
                if (
                    !registration.items.some(
                        (current) => current.title === item.title,
                    )
                )
                    registration.items.push(item);
            });
        } else
            navSections.push({
                title: 'Registration',
                icon: UsersRound,
                items: athleteItems,
            });
    }

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
                <NavMain
                    items={navItems}
                    sections={navSections.filter(
                        (section) => section.items.length > 0,
                    )}
                />
            </SidebarContent>

            <SidebarFooter className="p-3">
                <SidebarMeetCard />
            </SidebarFooter>
        </Sidebar>
    );
}
