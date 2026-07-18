# PMMS React and Inertia Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) · [application-architecture.md](application-architecture.md) · [api-and-client-boundaries.md](api-and-client-boundaries.md)

This document defines the frontend structure for PMMS's React/Inertia administrative application. **No components, routes, hooks, or pages are created here.**

---

## 1. Recommended Directory Structure

```text
resources/js/
├── app/            (app bootstrap, providers, root layout wiring)
├── components/     (generic, non-domain UI building blocks)
├── design-system/  (shadcn/ui-based primitives, theme tokens, accessibility)
├── features/       (feature-oriented modules, grouped by bounded context)
├── hooks/          (generic, non-domain React hooks)
├── layouts/         (page-level layout shells)
├── lib/            (framework glue: Inertia helpers, formatting, non-business utilities)
├── pages/          (Inertia page components)
├── services/       (HTTP/API clients, upload helpers, real-time clients)
├── stores/         (client-side state, scoped to a feature where possible)
└── types/          (TypeScript types)
```

The repository already contains `resources/js/actions`, `resources/js/routes`, and `resources/js/wayfinder` (Laravel Wayfinder-generated type-safe route/action helpers) and `resources/js/components/ui` (shadcn primitives) — these fit naturally under `lib/` (generated action/route helpers) and `design-system/` (shadcn primitives) respectively in the structure above, and are **preserved as-is**, not restructured, by this document.

## 2. Feature-Oriented Grouping

`features/` is organized by bounded context, mirroring [laravel-architecture.md, Section 2](laravel-architecture.md#2-laravel-module-structure) — e.g., `features/eligibility/`, `features/tournament-management/`, `features/scoring/`, `features/medal-tally/`. This keeps frontend and backend module boundaries conceptually aligned without requiring a rigid 1:1 file mapping.

## 3. Responsibilities by Layer

### Inertia Pages (`pages/`)
- Receive server-controlled page props (the authoritative source of what data reaches the client).
- Compose layouts and feature components.
- **Do not contain domain logic.**
- **Do not decide authorization independently** — a page renders what the server chose to send; it does not itself compute "can this user see the certify button," it reflects a server-provided capability flag (see Section 6).
- Handle user interaction and state presentation.

### Feature Modules (`features/<context>/`)
- Context-specific components: forms, tables, dialogs.
- Client-side state scoped to the feature.
- UI validation (fast feedback — never a substitute for server validation).
- Typed action contracts (using Wayfinder-generated types where available).

### Shared Design System (`design-system/`)
- Reusable UI primitives (built on shadcn/ui).
- Accessibility patterns.
- Theme tokens.
- Table patterns, chart patterns (see the `dataviz` design skill for chart-specific guidance when implementation begins).
- Status indicators, loading/error/empty states — used consistently across every feature so a "pending sync" or "conflict" state (per [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md)) looks the same everywhere it appears.

### Client Services (`services/`)
- HTTP/API clients (for the narrow set of dedicated API endpoints — see [api-and-client-boundaries.md](api-and-client-boundaries.md); most administrative interaction uses Inertia directly, not a separate API client).
- Upload helpers (coordinating with [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md)'s upload flow).
- Real-time clients (Reverb/Echo integration — see [realtime-architecture.md](realtime-architecture.md)).
- Synchronization helpers (where the administrative app itself needs to reconcile real-time-pushed state with the last-fetched page props).
- Telemetry hooks (client-side observability signals — see [observability-and-error-handling.md](observability-and-error-handling.md)).

## 4. InertiaJS Boundary

### Use Inertia For
Administrative portal, committee operations, registration workflows, tournament management, scoring interfaces where reliable online connectivity exists, operational dashboards, configuration and reporting.

### Use Dedicated API Endpoints For
Flutter mobile clients, offline synchronization, scanner devices, public integrations, webhooks, external systems, high-frequency live scoring devices, kiosks, dedicated scoreboard applications.

**Avoid building a duplicate API for every Inertia page without a real client need.** The default is Inertia for anything the React administrative app itself renders; a dedicated API endpoint is added only when a genuinely different client (Flutter, a device, an external system) needs the same data or capability. See [api-and-client-boundaries.md](api-and-client-boundaries.md) for the full API category breakdown.

## 5. Rules

- **The server remains authoritative.** Every mutation is validated and authorized server-side per [authorization-decision-model.md](authorization-decision-model.md), regardless of what the client-side form allowed the user to attempt.
- **Frontend permission checks are usability controls only** — hiding a "Certify Result" button from a user without the Result Certifier assignment improves the experience; it is never the security boundary (see [security-rules.md](../../.ai/security-rules.md), "No frontend-only authorization").
- All mutations require server authorization — there is no client-side-only write path anywhere in the application.
- TypeScript types should not blindly duplicate unstable backend models — types are generated or explicitly maintained against the Application layer's Command/Query contracts (via Wayfinder or an equivalent typed-contract mechanism), not hand-copied from Eloquent model shapes that may drift.
- Page props are intentional — a page receives exactly the data its current view needs, not an entire aggregate "just in case."
- Large payloads use paginated or lazy-loading patterns (e.g., a delegation roster with hundreds of athletes is paginated, not sent whole).
- Sensitive data is not hydrated unnecessarily — a page listing athletes for schedule purposes does not receive full eligibility case evidence just because the underlying query could join to it.
- Real-time updates (via Reverb) must reconcile with authoritative server state, never silently overwrite it — an incoming broadcast updates the UI optimistically, but a subsequent normal page visit/refetch is the source of truth (see [realtime-architecture.md, "Reconnection Behavior"](realtime-architecture.md)).
- Complex forms (e.g., multi-step eligibility review) preserve validation and concurrency errors — a rejected submission due to a stale resource state (someone else already acted) surfaces clearly, not as a generic failure.

## 6. Authorization Signal to the Frontend

Consistent with [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md), the frontend receives **capability flags** computed server-side (e.g., `canCertifyResult: boolean` on a result's page props) rather than re-implementing the 16-step decision sequence client-side. This keeps the single source of authorization truth in the Application layer while still letting the UI present an appropriate, uncluttered experience per role.

## 7. Open Questions

- Whether a client-side state-management library (beyond React's built-in state and Inertia's page-prop model) is needed for any specific high-complexity feature (e.g., live scoring) — deferred until a concrete feature demonstrates the need, consistent with avoiding premature complexity.
- Real-time reconciliation strategy specifics (optimistic UI vs. refetch-on-broadcast) — see [realtime-architecture.md](realtime-architecture.md) and [runtime-open-decisions.md](runtime-open-decisions.md).
