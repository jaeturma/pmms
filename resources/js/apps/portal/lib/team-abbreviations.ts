/**
 * Short Team labels for the public Medal Tally on mobile only — desktop
 * and tablet always show the full Team name. Keys are the exact stored
 * Delegation / municipality names; anything not listed falls back to its
 * full name unchanged (never a broken or blank label).
 *
 * This is a display-label map only. It does not rename any stored
 * Delegation, model, or relationship.
 */
const TEAM_ABBREVIATIONS: Record<string, string> = {
    Compostela: 'Com',
    Pantukan: 'Pan',
    Maragusan: 'Mar',
    Nabunturan: 'Nab',
    Monkayo: 'Mnk',
    Montevista: 'Mon',
    Mawab: 'Maw',
    Maco: 'Mac',
    Mabini: 'Mab',
    Laak: 'Laa',
    'New Bataan': 'NwB',
};

/** The mobile short label for a Team, or the full name if none is mapped. */
export function teamAbbreviation(name: string): string {
    return TEAM_ABBREVIATIONS[name.trim()] ?? name;
}
