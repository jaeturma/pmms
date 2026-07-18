# PMMS Shared Component Library

Reusable frontend building blocks. UI primitives live in `resources/js/components/ui/` (shadcn/ui-style, excluded from ESLint/Prettier); app-level shared components live in `resources/js/components/`.

## UI Primitives (`components/ui/`)

alert, avatar, badge, breadcrumb, button, card, checkbox, collapsible, dialog, dropdown-menu, icon, input-otp, input, label, navigation-menu, placeholder-pattern, select, separator, sheet, sidebar, skeleton, sonner (toasts), spinner, **table** (added WP-01-09), toggle-group, toggle, tooltip.

## Shared App Components (`components/`)

| Component | Purpose | Usage |
|---|---|---|
| `PageHeader` | Page title + optional description and action buttons | `<PageHeader title="Athletes" description="…" actions={<Button>Add</Button>} />` |
| `EmptyState` | Placeholder for empty lists/sections with optional icon and action | `<EmptyState icon={Users} title="No athletes yet" action={<Button>Add</Button>} />` |
| `ConfirmDialog` | Confirmation dialog for destructive or important actions | `<ConfirmDialog trigger={<Button>Delete</Button>} title="Delete athlete?" destructive onConfirm={…} />` (or controlled via `open`/`onOpenChange`) |
| `StatCard` | Dashboard metric card (label, value, optional icon/description) | `<StatCard label="Users" value={42} icon={Users} />` |
| `SearchBar` | Server-side search form for registry pages; submits `search` as a query param via Inertia (added WP-02-10) | `<SearchBar initial={filters.search} placeholder="Search schools" url={index().url} extraParams={{ event_id: '3' }} />` |
| `PaginationControls` | Previous/Next pager for Laravel paginator props; exports the `Paginated<T>` type; hidden when there is one page (added WP-02-10) | `<PaginationControls page={schools} url={index().url} label="schools" params={filterParams} />` |
| `ReportActions` | Print + Download CSV buttons for report pages; hidden when printing (added WP-02-12) | `<ReportActions downloadUrl={download(id).url} />` |
| `Heading` | Section heading (starter kit; `variant="small"` for sub-sections) | `<Heading title="Profile" description="…" />` |
| `AppLogo` / `AppLogoIcon` | PMMS brand mark | Used by sidebar, header, auth layouts, welcome |
| `Breadcrumbs`, `InputError`, `TextLink`, `PasswordInput`, `AlertError` | Starter-kit shared components, reused as-is | — |

## Conventions

- Compose from `ui/` primitives; don't restyle primitives ad hoc — extend via `className`.
- Use semantic theme tokens (`bg-background`, `text-muted-foreground`, `bg-primary`, …) so light/dark themes work automatically.
- Keep components typed (no `any` in props), small, and stateless where possible.
- New shared components require a row in this document.
- Registry list pages pair `SearchBar` + `PaginationControls` with the backend `SearchesAndPaginates` controller trait (`app/Http/Controllers/Concerns/`): LIKE search across plain or `relation.column` columns, 15 rows per page, `withQueryString()` so search and page params survive navigation.
