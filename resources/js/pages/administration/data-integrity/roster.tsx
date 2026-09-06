import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Candidate = {
    id: number;
    name: string;
    school: string;
    level: string;
    sex: string;
};
type Props = {
    roster: {
        id: number;
        athlete_id: number;
        delegation: string;
        sport: string;
        level: string;
        gender: string;
        available: boolean;
    };
    schools: { id: number; name: string }[];
    history: {
        id: number;
        who: string;
        at: string;
        context: {
            reason: string;
            before: { athlete_id: number };
            after: { athlete_id: number };
        };
    }[];
};

export default function RosterRepair({ roster, schools, history }: Props) {
    const base = `/administration/data-integrity/rosters/${roster.id}`;
    const [search, setSearch] = useState('');
    const [school, setSchool] = useState('');
    const [sportOnly, setSportOnly] = useState(false);
    const [candidates, setCandidates] = useState<Candidate[]>([]);
    const [searched, setSearched] = useState(false);
    const [searching, setSearching] = useState(false);
    const [searchError, setSearchError] = useState('');
    const [creating, setCreating] = useState(false);
    const link = useForm({
        athlete_id: '',
        expected_athlete_id: roster.athlete_id,
        reason: '',
    });
    const create = useForm({
        first_name: '',
        middle_name: '',
        last_name: '',
        name_extension: 'None',
        sex: '',
        birthdate: '',
        lrn: '',
        grade_level: '',
        age_division: roster.level,
        school_id: '',
        reason: '',
        expected_athlete_id: roster.athlete_id,
        confirm_create: false,
    });
    const selected = candidates.find(
        (candidate) => candidate.id === Number(link.data.athlete_id),
    );

    async function searchAthletes() {
        setSearching(true);
        setSearchError('');
        setSearched(false);
        link.setData('athlete_id', '');

        try {
            const params = new URLSearchParams({
                search,
                sport_only: sportOnly ? '1' : '0',
                ...(school ? { school_id: school } : {}),
            });
            const response = await fetch(`${base}/candidates?${params}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error(
                    'Search failed. Enter at least two characters and try again.',
                );
            }

            const data = await response.json();
            setCandidates(data.athletes);
            setSearched(true);
        } catch (error) {
            setSearchError(
                error instanceof Error ? error.message : 'Search failed.',
            );
        } finally {
            setSearching(false);
        }
    }

    return (
        <>
            <Head title={`Repair roster #${roster.id}`} />
            <div className="mx-auto w-full max-w-4xl space-y-5 p-4">
                <PageHeader
                    title={`Link Athlete — Roster #${roster.id}`}
                    description="Choose the correct existing athlete. No records are merged automatically."
                />
                <Link
                    href="/administration/data-integrity"
                    className="text-sm underline"
                >
                    Back to Data Integrity
                </Link>
                <div className="rounded-lg border bg-muted/30 p-4">
                    <p>
                        <strong>
                            {roster.sport} · {roster.delegation}
                        </strong>
                    </p>
                    <p>
                        {roster.level} · {roster.gender}
                    </p>
                    <p>
                        Current Athlete ID: {roster.athlete_id} —{' '}
                        {roster.available
                            ? 'Review inconsistent link'
                            : 'Athlete record unavailable'}
                    </p>
                </div>
                <form
                    className="space-y-3 rounded-lg border p-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        void searchAthletes();
                    }}
                >
                    <h2 className="font-semibold">
                        1. Search existing athletes
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Delegation is fixed to {roster.delegation}. Search by
                        first name, last name, or LRN. Review archived
                        identities in Athlete registration before creating a
                        replacement.
                    </p>
                    <Input
                        aria-label="Athlete name or LRN"
                        placeholder="Name or LRN"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        required
                        minLength={2}
                    />
                    <label className="block text-sm">
                        School{' '}
                        <select
                            className="ml-2 rounded border bg-background p-2"
                            value={school}
                            onChange={(e) => setSchool(e.target.value)}
                        >
                            <option value="">All schools</option>
                            {schools.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </select>
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={sportOnly}
                            onChange={(e) => setSportOnly(e.target.checked)}
                        />
                        Only athletes already rostered for {roster.sport}
                    </label>
                    <Button type="submit" disabled={searching}>
                        {searching ? 'Searching…' : 'Search athletes'}
                    </Button>
                    {searchError && <p role="alert">{searchError}</p>}
                    {searched && candidates.length === 0 && (
                        <p>
                            No available matching athletes. Check the search and
                            filters before creating a record.
                        </p>
                    )}
                    <div className="max-h-72 space-y-2 overflow-auto">
                        {candidates.map((candidate) => (
                            <label
                                key={candidate.id}
                                className="flex items-start gap-2 rounded border p-3"
                            >
                                <input
                                    type="radio"
                                    name="athlete"
                                    value={candidate.id}
                                    checked={
                                        Number(link.data.athlete_id) ===
                                        candidate.id
                                    }
                                    onChange={() =>
                                        link.setData(
                                            'athlete_id',
                                            String(candidate.id),
                                        )
                                    }
                                />
                                <span>
                                    {candidate.name} (#{candidate.id})
                                    <small className="block text-muted-foreground">
                                        {candidate.school} · {candidate.level} ·{' '}
                                        {candidate.sex}
                                    </small>
                                </span>
                            </label>
                        ))}
                    </div>
                </form>
                <form
                    className="space-y-3 rounded-lg border p-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        link.patch(base);
                    }}
                >
                    <h2 className="font-semibold">
                        2. Preview and confirm the link
                    </h2>
                    <p>
                        {selected
                            ? `Athlete #${roster.athlete_id} → ${selected.name} (#${selected.id}). Sport and delegation stay ${roster.sport} / ${roster.delegation}.`
                            : 'Select an athlete above to preview the change.'}
                    </p>
                    <Input
                        aria-label="Repair reason"
                        placeholder="Reason for repair (required)"
                        minLength={5}
                        required
                        value={link.data.reason}
                        onChange={(e) => link.setData('reason', e.target.value)}
                    />
                    {Object.values(link.errors).map((error) => (
                        <p role="alert" key={error}>
                            {error}
                        </p>
                    ))}
                    <Button
                        type="submit"
                        disabled={!selected || link.processing}
                    >
                        {link.processing ? 'Linking…' : 'Confirm Link Athlete'}
                    </Button>
                </form>
                <div className="space-y-3 rounded-lg border p-4">
                    <h2 className="font-semibold">No athlete record exists?</h2>
                    <Button
                        type="button"
                        variant="outline"
                        disabled={!searched}
                        onClick={() => setCreating(!creating)}
                    >
                        Create Athlete and Link
                    </Button>
                    {!searched && (
                        <p className="text-sm text-muted-foreground">
                            Search existing athletes first.
                        </p>
                    )}
                    {creating && (
                        <form
                            className="space-y-3"
                            onSubmit={(e) => {
                                e.preventDefault();
                                create.post(`${base}/athlete`);
                            }}
                        >
                            <p className="text-sm">
                                Creates one canonical athlete in{' '}
                                {roster.delegation} and repairs this roster row.
                                No Event Entry is created.
                            </p>
                            <div className="grid gap-3 sm:grid-cols-2">
                                {(
                                    [
                                        'first_name',
                                        'middle_name',
                                        'last_name',
                                        'birthdate',
                                        'lrn',
                                        'grade_level',
                                    ] as const
                                ).map((key) => (
                                    <label key={key} className="text-sm">
                                        {key.replaceAll('_', ' ')}
                                        <Input
                                            type={
                                                key === 'birthdate'
                                                    ? 'date'
                                                    : key === 'grade_level'
                                                      ? 'number'
                                                      : 'text'
                                            }
                                            required
                                            value={create.data[key]}
                                            onChange={(e) =>
                                                create.setData(
                                                    key,
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </label>
                                ))}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Use “N/A” if there is no middle name. LRN must
                                be 12 digits.
                            </p>
                            <label className="block text-sm">
                                Name extension{' '}
                                <select
                                    className="rounded border bg-background p-2"
                                    value={create.data.name_extension}
                                    onChange={(e) =>
                                        create.setData(
                                            'name_extension',
                                            e.target.value,
                                        )
                                    }
                                >
                                    {['None', 'Jr.', 'Sr.', 'II', 'III'].map(
                                        (value) => (
                                            <option key={value}>{value}</option>
                                        ),
                                    )}
                                </select>
                            </label>
                            <label className="block text-sm">
                                Sex{' '}
                                <select
                                    className="rounded border bg-background p-2"
                                    required
                                    value={create.data.sex}
                                    onChange={(e) =>
                                        create.setData('sex', e.target.value)
                                    }
                                >
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </label>
                            <label className="block text-sm">
                                Age division{' '}
                                <select
                                    className="rounded border bg-background p-2"
                                    value={create.data.age_division}
                                    onChange={(e) =>
                                        create.setData(
                                            'age_division',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="elementary">
                                        Elementary
                                    </option>
                                    <option value="secondary">Secondary</option>
                                    <option value="elementary_and_secondary">
                                        Elementary and Secondary
                                    </option>
                                </select>
                            </label>
                            <label className="block text-sm">
                                School{' '}
                                <select
                                    className="rounded border bg-background p-2"
                                    value={create.data.school_id}
                                    onChange={(e) =>
                                        create.setData(
                                            'school_id',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="">Not linked</option>
                                    {schools.map((item) => (
                                        <option key={item.id} value={item.id}>
                                            {item.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <Input
                                aria-label="Creation and repair reason"
                                required
                                minLength={5}
                                placeholder="Reason for creating and linking"
                                value={create.data.reason}
                                onChange={(e) =>
                                    create.setData('reason', e.target.value)
                                }
                            />
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    required
                                    checked={create.data.confirm_create}
                                    onChange={(e) =>
                                        create.setData(
                                            'confirm_create',
                                            e.target.checked,
                                        )
                                    }
                                />
                                I searched for existing records and confirm
                                creation of this athlete.
                            </label>
                            {Object.values(create.errors).map((error) => (
                                <p role="alert" key={error}>
                                    {error}
                                </p>
                            ))}
                            <Button
                                type="submit"
                                disabled={
                                    create.processing ||
                                    !create.data.confirm_create
                                }
                            >
                                {create.processing
                                    ? 'Creating and linking…'
                                    : 'Confirm Create Athlete and Link'}
                            </Button>
                        </form>
                    )}
                </div>
                <section className="space-y-2">
                    <h2 className="font-semibold">Repair history</h2>
                    {history.length === 0 ? (
                        <p>No repairs recorded.</p>
                    ) : (
                        history.map((log) => (
                            <div
                                className="rounded border p-3 text-sm"
                                key={log.id}
                            >
                                <p>
                                    {log.who} · {log.at}
                                </p>
                                <p>
                                    Athlete #{log.context.before.athlete_id} → #
                                    {log.context.after.athlete_id}
                                </p>
                                <p>{log.context.reason}</p>
                            </div>
                        ))
                    )}
                </section>
            </div>
        </>
    );
}

RosterRepair.layout = {
    breadcrumbs: [
        { title: 'Data Integrity', href: '/administration/data-integrity' },
    ],
};
