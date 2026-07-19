# PMMS Dashboard Framework

Foundation for the management dashboard (WP-01-12). Later modules add widgets by extending the controller's data and the page's grid — no structural rework needed.

## Backend

`App\Http\Controllers\DashboardController@index` (route `dashboard`, auth + verified) renders the `dashboard` Inertia page with:

- `stats` — array of `{ key, label, value }` widgets. Current: total users, uploaded files, today's audit activity.
- `recentActivity` — latest 10 audit log entries (`{ id, action, user, created_at_human }`).
- `operations` — meet-day operations block (WP-03-09), `null` unless a meet is
  **active**. Read-side only, no new data models.

To add a widget: append a row to `stats` (backend) and, if it needs an icon, map its `key` in the page's `statIcons`.

## Meet operations block (WP-03-09)

Role-aware widgets for the active meet, each linking into its owning module:

| Widget | Who sees it | Data |
|---|---|---|
| Today's schedule | all roles | the active meet's slots for today (time, event, venue), linked to the schedule page and daily sheet |
| Medal tally top five | all roles | top 5 school rows from `MedalTallyService` for the active meet |
| Operational queues (StatCards) | managers only (`queues: null` otherwise) | results awaiting validation, open protests (filed + under review), open incidents, accreditation progress (accredited / registered participants) — linked to results, protests, incidents |
| Your delegation's protests | delegation officers only | their delegations' latest 5 protests for the active meet with status |

Viewers therefore get schedule + tally summaries only, matching the module
visibility rules. The grids collapse to a single column at phone widths
(`sm:`/`lg:` breakpoints); tables scroll inside their cards.

## Frontend

`resources/js/pages/dashboard.tsx` — `PageHeader` + responsive `StatCard` grid + Recent Activity table (`ui/table`) with an `EmptyState` fallback. All data arrives typed via Inertia props; the page holds no fetching logic.

## Tests

`tests/Feature/DashboardTest.php` — guest redirect, authenticated access, and Inertia prop assertions for stats and recent activity.
`tests/Feature/OperationsDashboardTest.php` — operations block absent without an
active meet, today-only/active-meet-only slot filtering, queue counts, and the
role split (viewer summaries, manager queues, officer protests).
