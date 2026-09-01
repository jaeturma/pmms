import { Head, router } from '@inertiajs/react';
import { Printer, Search } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { PageHeader } from '@/components/page-header';
import { PaginationControls } from '@/components/pagination-controls';
import type { Paginated } from '@/components/pagination-controls';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Row = {
    id: number;
    person: string;
    code: string | null;
    role: string;
    sport: string | null;
    meal: string;
    starts_at: string | null;
    ends_at: string | null;
    display_status: string;
    status: string;
};
type ReportRow = {
    date: string;
    meal: string;
    role: string;
    sport: string;
    expected: number;
    consumed: number;
    not_claimed: number;
};
type PersonnelRow = {
    id: number;
    name: string;
    code: string | null;
    role: string;
    sport: string | null;
    twg_group: string | null;
};

export default function Distribution({
    entitlements,
    summary,
    filters,
    schedules,
    can_override,
    report,
    personnel,
    personnelFilters,
    sportOptions,
    twgGroupOptions,
}: {
    entitlements: Paginated<Row>;
    summary: { expected: number; consumed: number; remaining: number };
    filters: { search: string; meal_schedule_id: number | null };
    schedules: Array<{ id: number; label: string }>;
    can_override: boolean;
    report: ReportRow[];
    personnel: Paginated<PersonnelRow>;
    personnelFilters: {
        search: string;
        sport_id: number | null;
        twg_group_id: number | null;
        has_group_filter: boolean;
    };
    sportOptions: Array<{ id: number; label: string }>;
    twgGroupOptions: Array<{ id: number; label: string }>;
}) {
    const [search, setSearch] = useState(filters.search);
    const [personnelSearch, setPersonnelSearch] = useState(
        personnelFilters.search,
    );
    const params = {
        ...(filters.search ? { search: filters.search } : {}),
        ...(filters.meal_schedule_id
            ? { meal_schedule_id: filters.meal_schedule_id }
            : {}),
    };
    const percentage = summary.expected
        ? Math.round((summary.consumed / summary.expected) * 1000) / 10
        : 0;
    const personnelParams = {
        ...(personnelFilters.search
            ? { personnel_search: personnelFilters.search }
            : {}),
        ...(personnelFilters.sport_id
            ? { sport_id: String(personnelFilters.sport_id) }
            : {}),
        ...(personnelFilters.twg_group_id
            ? { twg_group_id: String(personnelFilters.twg_group_id) }
            : {}),
    };
    const batchPrintUrl = `/food/meal-stubs/print?${new URLSearchParams(personnelParams).toString()}`;
    const canLoadBatch = Boolean(
        personnelFilters.sport_id || personnelFilters.twg_group_id,
    );
    const consume = (row: Row, override = false) => {
        const reason = override
            ? window.prompt('Reason for serving-time override:')
            : null;
        if (override && !reason) return;
        router.post(
            `/food/distribution/${row.id}/consume`,
            { override, reason },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Meal Distribution" />
            <div className="flex flex-col gap-5 p-4">
                <PageHeader
                    title="Meal Distribution"
                    description="Today's eligible personnel and meal consumption"
                />
                <div className="grid gap-3 sm:grid-cols-4">
                    {[
                        ['Expected', summary.expected],
                        ['Consumed', summary.consumed],
                        ['Remaining', summary.remaining],
                        ['Served', `${percentage}%`],
                    ].map(([label, value]) => (
                        <div key={label} className="rounded-xl border p-4">
                            <p className="text-sm text-muted-foreground">
                                {label}
                            </p>
                            <p className="text-2xl font-bold">{value}</p>
                        </div>
                    ))}
                </div>
                <section id="personnel-stubs" className="space-y-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Meal Stub Personnel
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Select a Sport or TWG Group to load eligible
                                personnel, then print their meal stubs as one A4
                                page per person.
                            </p>
                        </div>
                        {canLoadBatch ? (
                            <Button asChild>
                                <a href={batchPrintUrl} target="_blank" rel="noreferrer">
                                    <Printer />
                                    Print Filtered Batch ({personnel.total})
                                </a>
                            </Button>
                        ) : (
                            <Button disabled title="Select a sport or TWG group first">
                                <Printer />
                                Select Sport or TWG to Print
                            </Button>
                        )}
                    </div>
                    <div className="grid gap-2 lg:grid-cols-[minmax(15rem,1fr)_15rem_15rem_auto]">
                        <form
                            className="flex gap-2"
                            onSubmit={(event) => {
                                event.preventDefault();
                                router.get('/food/distribution', {
                                    ...personnelParams,
                                    personnel_search:
                                        personnelSearch || undefined,
                                });
                            }}
                        >
                            <Input
                                value={personnelSearch}
                                onChange={(event) =>
                                    setPersonnelSearch(event.target.value)
                                }
                                placeholder="Search personnel"
                            />
                            <Button type="submit" variant="outline">
                                <Search />
                            </Button>
                        </form>
                        <Select
                            value={String(
                                personnelFilters.sport_id ?? 'all',
                            )}
                            onValueChange={(value) =>
                                router.get('/food/distribution', {
                                    ...personnelParams,
                                    sport_id:
                                        value === 'all' ? undefined : value,
                                    personnel_page: undefined,
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="All sports" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All sports</SelectItem>
                                {sportOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={String(
                                personnelFilters.twg_group_id ?? 'all',
                            )}
                            onValueChange={(value) =>
                                router.get('/food/distribution', {
                                    ...personnelParams,
                                    twg_group_id:
                                        value === 'all' ? undefined : value,
                                    personnel_page: undefined,
                                })
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="All TWG groups" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All TWG groups
                                </SelectItem>
                                {twgGroupOptions.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => router.get('/food/distribution')}
                        >
                            Clear
                        </Button>
                    </div>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-3 py-2 font-medium">
                                        Personnel
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Role
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        Sport
                                    </th>
                                    <th className="px-3 py-2 font-medium">
                                        TWG Group
                                    </th>
                                    <th className="px-3 py-2 text-right font-medium">
                                        Print
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {personnel.data.map((person) => (
                                    <tr key={person.id} className="border-t">
                                        <td className="px-3 py-2">
                                            <p className="font-semibold uppercase">
                                                {person.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {person.code}
                                            </p>
                                        </td>
                                        <td className="px-3 py-2">
                                            {person.role}
                                        </td>
                                        <td className="px-3 py-2">
                                            {person.sport ?? '—'}
                                        </td>
                                        <td className="px-3 py-2">
                                            {person.twg_group ?? '—'}
                                        </td>
                                        <td className="px-3 py-2 text-right">
                                            <Button size="sm" variant="outline" asChild>
                                                <a
                                                    href={`/food/meal-stubs/print?personnel_id=${person.id}`}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <Printer />
                                                    Print 1 Page
                                                </a>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {!personnelFilters.has_group_filter && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            Select a Sport or TWG Group to load
                                            personnel.
                                        </td>
                                    </tr>
                                )}
                                {personnelFilters.has_group_filter &&
                                    personnel.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-3 py-8 text-center text-muted-foreground"
                                        >
                                            No eligible personnel match the
                                            selected filters.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    <PaginationControls
                        page={personnel}
                        url="/food/distribution"
                        label="personnel"
                        params={personnelParams}
                        pageName="personnel_page"
                    />
                </section>
                <div className="flex flex-col gap-3 sm:flex-row">
                    <form
                        className="flex flex-1 gap-2"
                        onSubmit={(event) => {
                            event.preventDefault();
                            router.get(
                                '/food/distribution',
                                { ...params, search },
                                { preserveState: true },
                            );
                        }}
                    >
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search personnel name or ID/code"
                        />
                        <Button>
                            <Search />
                            Search
                        </Button>
                    </form>
                    <Select
                        value={String(filters.meal_schedule_id ?? 'all')}
                        onValueChange={(value) =>
                            router.get('/food/distribution', {
                                search: filters.search || undefined,
                                meal_schedule_id:
                                    value === 'all' ? undefined : value,
                            })
                        }
                    >
                        <SelectTrigger className="sm:w-64">
                            <SelectValue placeholder="All meals" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All meals</SelectItem>
                            {schedules.map((item) => (
                                <SelectItem
                                    key={item.id}
                                    value={String(item.id)}
                                >
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div id="meal-stubs" className="space-y-3">
                    {entitlements.data.map((row) => (
                        <section
                            key={row.id}
                            className="flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p className="font-semibold">{row.person}</p>
                                <p className="text-sm text-muted-foreground">
                                    {row.role}
                                    {row.sport ? ` · ${row.sport}` : ''}
                                    {row.code ? ` · ${row.code}` : ''}
                                </p>
                                <p className="mt-1 text-sm">
                                    {row.meal} · {row.starts_at?.slice(0, 5)}–
                                    {row.ends_at?.slice(0, 5)}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <Badge
                                    variant={
                                        row.status === 'consumed'
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                >
                                    {row.display_status.toUpperCase()}
                                </Badge>
                                {row.status !== 'consumed' &&
                                    row.display_status === 'available' && (
                                    <ConfirmDialog
                                        trigger={
                                            <Button>Mark as Consumed</Button>
                                        }
                                        title={`Serve ${row.meal} to ${row.person}?`}
                                        description="Confirm the person's identity before consuming this entitlement."
                                        confirmLabel="Mark as Consumed"
                                        onConfirm={() => consume(row)}
                                    />
                                )}
                                {can_override &&
                                    row.status !== 'consumed' &&
                                    row.display_status !== 'available' && (
                                        <Button
                                            variant="outline"
                                            onClick={() => consume(row, true)}
                                        >
                                            Override
                                        </Button>
                                    )}
                            </div>
                        </section>
                    ))}
                </div>
                <PaginationControls
                    page={entitlements}
                    url="/food/distribution"
                    label="personnel"
                    params={params}
                />
                <section id="reports" className="space-y-3">
                    <div>
                        <h2 className="text-lg font-semibold">
                            Food Consumption Report
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Breakdown by date, meal, eligible role/TWG, and
                            sport. Each person is counted once per meal.
                        </p>
                    </div>
                    <div className="overflow-x-auto rounded-xl border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    {[
                                        'Date',
                                        'Meal',
                                        'Role / TWG',
                                        'Sport',
                                        'Expected',
                                        'Consumed',
                                        'Not Claimed',
                                    ].map((heading) => (
                                        <th
                                            key={heading}
                                            className="px-3 py-2 font-medium"
                                        >
                                            {heading}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {report.map((row) => (
                                    <tr
                                        key={`${row.date}-${row.meal}-${row.role}-${row.sport}`}
                                        className="border-t"
                                    >
                                        <td className="px-3 py-2">
                                            {row.date}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.meal}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.role}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.sport}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.expected}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.consumed}
                                        </td>
                                        <td className="px-3 py-2">
                                            {row.not_claimed}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </>
    );
}
