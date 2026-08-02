<?php

namespace App\Enums;

/**
 * Per the mandate §15-27 and
 * docs/architecture/pmms-approved-organizational-model.md §6 — the
 * "administrative shell" (who is on the team, what is its mandate) for
 * each committee/oversight function. `ManagementTeam` deliberately does
 * not own the specialized operational data these teams produce (medical
 * records, equipment, meals, etc.) — those are separate, later work
 * packages (WP-REALIGN-10 through -12) that reference a team by id, the
 * same "Committee Operations owns membership, specialized contexts
 * reference it" split the source bounded-context catalog itself
 * recommended (`docs/01-architecture/bounded-context-catalog.md` BC-05).
 */
enum ManagementTeamType: string
{
    case TopManagement = 'top_management';
    case MeetManagement = 'meet_management';
    case ResultsCommittee = 'results_committee';
    case DivisionSecretariat = 'division_secretariat';
    case ICT = 'ict';
    case Supply = 'supply';
    case Food = 'food';
    case Billeting = 'billeting';
    case Transport = 'transport';
    case Medical = 'medical';
    case DRRM = 'drrm';

    public function label(): string
    {
        return match ($this) {
            self::TopManagement => 'Top Management',
            self::MeetManagement => 'Meet Management',
            self::ResultsCommittee => 'Results Committee',
            self::DivisionSecretariat => 'Division Secretariat (DSAC)',
            self::ICT => 'ICT Team',
            self::Supply => 'Supply Team',
            self::Food => 'Food Team',
            self::Billeting => 'Billeting Team',
            self::Transport => 'Transport Team',
            self::Medical => 'Medical Team',
            self::DRRM => 'DRRM Team',
        };
    }
}
