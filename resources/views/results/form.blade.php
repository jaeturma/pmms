<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $reference }} — Official Result Form</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 28px; font-size: 12px; }
        header { text-align: center; margin-bottom: 22px; }
        h1 { font-size: 20px; margin: 8px 0; letter-spacing: .08em; }
        .meta, .results { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .meta th, .meta td, .results th, .results td { border: 1px solid #333; padding: 7px; text-align: left; }
        .meta th { width: 18%; background: #f3f4f6; }
        .results th { background: #f3f4f6; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; margin-top: 65px; text-align: center; }
        .line { border-top: 1px solid #111; padding-top: 6px; }
        .trace { margin-top: 40px; font-size: 10px; color: #444; }
        .no-print { margin-bottom: 16px; }
        @media print { .no-print { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print Result Form</button>
    <header>
        <div>Department of Education</div>
        <div>DdOPAA Provincial Meet 2026</div>
        <h1>OFFICIAL RESULT FORM</h1>
        <div>Result Reference No.: <strong>{{ $reference }}</strong></div>
        <div>Version {{ $result->version }} · Generated {{ $generatedAt->format('F j, Y g:i A') }}</div>
    </header>

    <table class="meta">
        <tr><th>Sport</th><td>{{ $result->event->sport->name }}</td><th>Category</th><td>{{ $result->event->sportCategory?->name ?? '—' }}</td></tr>
        <tr><th>Event</th><td>{{ $result->event->name }}</td><th>Division</th><td>{{ $result->event->age_division->label() }} / {{ $result->event->gender->label() }}</td></tr>
        <tr><th>Date / Time</th><td>{{ $schedule?->scheduled_date?->format('F j, Y') ?? '—' }} {{ $schedule?->starts_at ?? '' }}</td><th>Venue / Area</th><td>{{ $schedule?->venue?->name ?? '—' }}</td></tr>
    </table>

    <table class="results">
        <thead><tr><th>Rank / Placement</th><th>Participant</th><th>Delegation / School</th><th>Performance / Result</th><th>Remarks</th></tr></thead>
        <tbody>
        @foreach ($result->placements->sortBy('rank') as $placement)
            <tr>
                <td>{{ $placement->rank }}</td>
                <td>{{ $placement->entry->athlete->fullName() }}</td>
                <td>{{ $placement->entry->athlete->school->district->name }} / {{ $placement->entry->athlete->school->name }}</td>
                <td>{{ $placement->mark ?: '—' }}</td>
                <td>{{ $placement->is_tie ? 'Tie' : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div><div class="line">Prepared / Recorded by<br><strong>Tournament Secretary</strong></div></div>
        <div><div class="line">Certified / Confirmed by<br><strong>Tournament Manager</strong></div></div>
        <div><div class="line">Technical Certification<br><strong>Authorized Technical Official</strong></div></div>
    </div>

    <div class="trace">This printed form supports the structured PMMS result identified above. Printing does not validate or make the result official.</div>
</body>
</html>
