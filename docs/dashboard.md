# PMMS Dashboard Framework

Foundation for the management dashboard (WP-01-12). Later modules add widgets by extending the controller's data and the page's grid — no structural rework needed.

## Backend

`App\Http\Controllers\DashboardController@index` (route `dashboard`, auth + verified) renders the `dashboard` Inertia page with:

- `stats` — array of `{ key, label, value }` widgets. Current: total users, uploaded files, today's audit activity.
- `recentActivity` — latest 10 audit log entries (`{ id, action, user, created_at_human }`).

To add a widget: append a row to `stats` (backend) and, if it needs an icon, map its `key` in the page's `statIcons`.

## Frontend

`resources/js/pages/dashboard.tsx` — `PageHeader` + responsive `StatCard` grid + Recent Activity table (`ui/table`) with an `EmptyState` fallback. All data arrives typed via Inertia props; the page holds no fetching logic.

## Tests

`tests/Feature/DashboardTest.php` — guest redirect, authenticated access, and Inertia prop assertions for stats and recent activity.
