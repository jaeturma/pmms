export type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    additional_roles?: string[];
    tournament_assignment_roles?: string[];
    team_types?: string[];
    can_review_coaches?: boolean;
    can_manage_accounts?: boolean;
    can_manage_announcements?: boolean;
    can_manage_personnel?: boolean;
    can_file_protest?: boolean;
    can_view_management_reports?: boolean;
    can_view_tournament_athletes?: boolean;
    assigned_sports?: string[];
    is_tournament_scoped?: boolean;
    can_view_system_logs?: boolean;
    can_manage_school_master_data?: boolean;
    can_request_coach_enrollment?: boolean;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
