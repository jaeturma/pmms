/**
 * Portal-local type layer. The rest of the app declares these shapes
 * inline per-page (`resources/js/pages/public/*.tsx`) rather than from
 * a shared module — there is nothing to import here, so these are
 * defined fresh from the real `PortalController` prop shapes.
 */

export type PortalMeetSummary = {
    id: number;
    name: string;
    school_year: string;
    starts_at: string;
    starts_at_iso: string;
    ends_at: string;
    venue: string | null;
    status_label: string;
};

export type PortalAnnouncement = {
    id: number;
    title: string;
    body: string;
    meet: string | null;
    published_at: string | null;
};

export type PortalMunicipality = {
    id: number;
    name: string;
    nickname: string | null;
    logo_url: string | null;
};

export type PortalLeader = {
    district: string;
    points: number;
};

export type PortalUpcomingEvent = {
    id: number;
    date: string;
    starts_at: string;
    event: string;
    venue: string;
};

export type PortalResultPlacement = {
    rank: number;
    athlete: string;
    school: string;
    delegation: string;
    mark: string | null;
    is_tie: boolean;
};

export type PortalLatestResult = {
    event: string;
    official_as_of: string | null;
    placements: PortalResultPlacement[];
};

export type PortalClosingSummary = {
    champion: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

export type PortalLiveMatch = {
    match_id: number;
    event: string;
    round_label: string | null;
    side_a_label: string | null;
    side_b_label: string | null;
    score_a: number | null;
    score_b: number | null;
    status_label: string;
};

export type PortalSport = {
    slug: string;
    name: string;
};

export type PortalGame = {
    id: number;
    round_label: string | null;
    status: string;
    status_label: string;
    category: string;
    venue: string | null;
    scheduled_date: string | null;
    starts_at: string | null;
    side_a: string | null;
    side_b: string | null;
    score_a: number | null;
    score_b: number | null;
    mark: string | null;
};

export type PortalPlayByPlayEntry = {
    id: number;
    description: string;
    score_a: number;
    score_b: number;
    created_at: string | null;
};

export type PortalAthleteParticipant = {
    name: string;
    sports_photo_url: string | null;
};

export type PortalLiveSession = {
    id: number;
    match_id: number;
    status: string;
    status_label: string;
    side_a_label: string | null;
    side_b_label: string | null;
    side_a_logo_url: string | null;
    side_b_logo_url: string | null;
    side_a_athlete: PortalAthleteParticipant | null;
    side_b_athlete: PortalAthleteParticipant | null;
    score_a: number | null;
    score_b: number | null;
    period_label: string | null;
    status_note: string | null;
    board_type: string;
    sport_state: Record<string, unknown> | null;
    playByPlay: PortalPlayByPlayEntry[];
    started_at: string | null;
    elapsed_seconds: number;
    clock_running: boolean;
};

export type PortalLiveNow = {
    match_id: number;
    round_label: string | null;
    category: string;
    venue: string | null;
    scheduled_date: string | null;
    starts_at: string | null;
    session: PortalLiveSession;
};

export type PortalVenue = {
    id: number;
    name: string;
    address: string | null;
    directions_url: string;
};

/** Mirrors the guest-only `publicNav` prop shared by every page via
 * `HandleInertiaRequests::share()` — real backend-shared state, not a
 * UI type, safe to depend on from the portal's own layer. */
export type PortalNavShared = {
    meetId: number;
    meetName: string;
    venue: string | null;
    schoolYear: string;
    liveCount: number;
} | null;

export type PortalDay = {
    value: string;
    label: string;
};

export type PortalScheduleSlot = {
    id: number;
    starts_at: string;
    ends_at: string;
    event: string;
    note: string | null;
};

export type PortalScheduleVenueGroup = {
    venue: string;
    slots: PortalScheduleSlot[];
};

export type PortalVenueGuideEntry = {
    name: string;
    address: string | null;
};

export type PortalResultRow = {
    id: number;
    event: string;
    age_division: 'elementary' | 'secondary';
    official_as_of: string | null;
    placements: PortalResultPlacement[];
};

export type PortalSportOption = {
    id: number;
    label: string;
};

export type PortalAgeDivisionOption = {
    id: string;
    label: string;
};

export type PortalStandingRow = {
    district: string;
    district_id?: number;
    logo_url?: string | null;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
    points: number;
};

export type PortalSchoolStandingRow = {
    school: string;
    municipality: string;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

export type PortalMedalTotals = {
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

export type PortalSportMedals = {
    sport: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

export type PortalTopMedalistRow = {
    position: number;
    athlete: string;
    grade_level: number;
    sport: string;
    school: string;
    municipality: string;
    district: string;
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

export type PortalAthleticsSlot = {
    id: number;
    starts_at: string;
    ends_at: string;
    event: string;
    venue: string;
    status: 'completed' | 'ongoing' | 'upcoming';
    top_placements: Array<{
        rank: number;
        athlete: string;
        school: string;
        mark: string | null;
    }>;
    official_as_of: string | null;
};

export type PortalContestedSport = {
    id: number;
    name: string;
    event_count: number;
};

export type PortalSchool = {
    id: number;
    name: string;
};

export type PortalSearchPlacement = {
    event: string;
    sport_id: number;
    rank: number;
    athlete: string;
    school: string;
    delegation: string;
    mark: string | null;
    is_tie: boolean;
};

export type PortalPaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PortalPaginatedAnnouncement = {
    data: PortalAnnouncement[];
    links: PortalPaginatorLink[];
};

export type PortalMunicipalityMedals = {
    gold: number;
    silver: number;
    bronze: number;
    total: number;
};

export type PortalMunicipalityTeam = {
    id: number;
    slug: string;
    name: string;
    nickname: string | null;
    congressional_district: string | null;
    logo_url: string | null;
    athlete_count: number;
    sport_count: number;
    medals: PortalMunicipalityMedals;
};

export type PortalMunicipalityProfile = {
    id: number;
    slug: string;
    name: string;
    nickname: string | null;
    congressional_district: string | null;
    logo_url: string | null;
    athlete_count: number;
    sport_count: number;
};

export type PortalMunicipalityMedalBreakdown = {
    elementary: PortalMunicipalityMedals;
    secondary: PortalMunicipalityMedals;
    paragames: PortalMunicipalityMedals;
    total: PortalMunicipalityMedals;
};

export type PortalMedalWinner = {
    medal: 'gold' | 'silver' | 'bronze' | 'other';
    participant_type: 'athlete' | 'team';
    athlete_name: string | null;
    team_name: string | null;
    roster: string[];
    sport: string;
    event: string;
    gender: string;
    level: string;
    school: string | null;
};

export type PortalTeamAthlete = {
    name: string;
    event: string;
    level: 'elementary' | 'secondary';
    category: string;
    school: string;
};

export type PortalTeamCoach = {
    name: string;
    role: string;
    school: string;
};

export type PortalSportPersonnel = {
    sport: string;
    is_paragames: boolean;
    athletes: PortalTeamAthlete[];
    coaches: PortalTeamCoach[];
};

export type PortalMatchSummary = {
    id: number;
    event: string;
    sport: string;
    category: string;
    round_label: string | null;
    status: string;
    status_label: string;
    venue: string | null;
    scheduled_date: string | null;
    starts_at: string | null;
    scheduled_start_at: string | null;
};
