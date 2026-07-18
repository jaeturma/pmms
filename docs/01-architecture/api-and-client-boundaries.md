# PMMS API and Client Boundaries

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [react-inertia-architecture.md](react-inertia-architecture.md) · [flutter-architecture.md](flutter-architecture.md) · [device-and-service-identity-model.md](device-and-service-identity-model.md) · [runtime-security-architecture.md](runtime-security-architecture.md)

This document defines the API surfaces PMMS exposes beyond the Inertia-served administrative application, and the principles every API follows. **No endpoint list, route definition, or controller is created here.**

---

## 1. API Categories

| Category | Consumers | Purpose |
|---|---|---|
| **Internal Mobile API** | Flutter application | Authenticated mobile operations: field workflows, offline sync, role-scoped data |
| **Device API** | Scanners, kiosks, score stations, venue devices | Narrow, device-identity-authenticated operations (per [device-and-service-identity-model.md](device-and-service-identity-model.md)) |
| **Public API** | Public consumers, future integrations | Read-only, rate-limited access to approved public projections (BC-29) |
| **Administrative Integration API** | Trusted organization/system integrations (none currently approved — Section 4 of [internal-integration-architecture.md](internal-integration-architecture.md)) | Structured data exchange with an external, DepEd-approved system |
| **Webhook API** | External systems needing PMMS-originated callbacks (none currently approved) | Outbound notification of approved events to an external endpoint |
| **Synchronization API** | Flutter sync engine, offline venue servers | Offline change upload and reference-data/authorization-snapshot download |

## 2. API Principles

- **Versioned contracts** — every API category carries an explicit version, allowing PMMS to evolve a contract without breaking an already-deployed mobile app or device firmware.
- **Authenticated where required** — Public API read-only endpoints for already-published data may be unauthenticated; every other category requires an authenticated identity (User Account, Device Identity, or Service Identity per [identity-model.md](identity-model.md)).
- **Scoped authorization** — every authenticated request is evaluated through the full [authorization-decision-model.md](authorization-decision-model.md) sequence, not a simplified API-specific check.
- **Idempotency support** — mutating endpoints accept an idempotency key, particularly for the Synchronization API where retried offline uploads are expected (see [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md)).
- **Rate limiting** — every category is rate-limited, with the Public API most aggressively limited given its unauthenticated exposure (see [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)).
- **Pagination** — no endpoint returns an unbounded collection.
- **Consistent errors** — a single error-shape convention across all APIs (see [observability-and-error-handling.md](observability-and-error-handling.md)).
- **Correlation IDs** — every request carries or receives a correlation ID that flows through logs, queued jobs, and any downstream integration call.
- **No direct database exposure** — no API is a thin wrapper over a database table; every endpoint goes through an Application-layer Command or Query (per [laravel-architecture.md](laravel-architecture.md)).
- **No internal model leakage** — API responses use dedicated response shapes, never a serialized Eloquent model exposing internal-only fields.
- **No sensitive information in URLs** — identifiers only; no tokens, no personal data, no classification-restricted content in a query string or path that might be logged by an intermediary.
- **Request size limits** — particularly relevant for the Synchronization API's batch upload payloads and the upload-initiation step of [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md).
- **Upload separation** — file uploads are handled through a dedicated flow (Section, see [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md)), never inlined as base64 in a JSON payload for anything beyond trivially small content.
- **Clear deprecation policy** — a versioned contract implies a defined sunset process for prior versions once mobile/device clients have migrated.
- **Audit logging for high-risk mutations** — any API-originated command that touches a Critical/Elevated-audit-level permission (per [permission-catalog.md](permission-catalog.md)) is logged with the same rigor as an Inertia-originated one.

## 3. Category Detail

### Internal Mobile API
- Consumers: the Flutter application only (not a general-purpose public API).
- Authentication: User Account session/token, combined with Device Identity trust (per [device-and-service-identity-model.md](device-and-service-identity-model.md)) for actions requiring device binding.
- Scope: limited to the mobile-relevant bounded contexts named in [flutter-architecture.md, Section 3](flutter-architecture.md#3-mobile-relevant-bounded-contexts).

### Device API
- Consumers: scanners, kiosks, score stations, venue devices — non-human operators of narrowly scoped functionality.
- Authentication: Device Identity credential, always combined with an operating User Account's authentication where a human is present (per [offline-authorization-model.md, Section 4–5](offline-authorization-model.md#4-device-binding)).
- Scope: the single operational purpose the device is registered for (e.g., `access-scan.validate` only for a gate scanner) — least privilege enforced at the API-contract level, not just the authorization-decision level.

### Public API
- Consumers: the public portal's own frontend (server-side rendered or fetched at request time from BC-29 projections) and, potentially in the future, external public consumers.
- Authentication: none for read-only published-projection endpoints.
- Scope: strictly BC-29's approved projections — this API is architecturally incapable of exposing anything BC-29 itself does not already hold (per [phase-0.2-domain-architecture.md, Section 16](phase-0.2-domain-architecture.md#16-public-data-boundary)).
- Rate limiting: most aggressive of all categories, and isolated (per [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)) so public traffic cannot degrade administrative/scoring workloads.

### Administrative Integration API
- Consumers: none currently approved. This category exists as a defined pattern for **when** a future integration (e.g., a DepEd organization registry feed) is approved, following the anti-corruption-adapter pattern from [internal-integration-architecture.md](internal-integration-architecture.md).
- Authentication: a dedicated Service/API-Client Identity, narrowly scoped per [device-and-service-identity-model.md, Section 5–6](device-and-service-identity-model.md#5-service-identity-categories).

### Webhook API
- Consumers: none currently approved. Defined as a pattern for outbound event notification to an approved external system, should one be approved in a future phase.
- Delivery: at-least-once with retry, signed payloads, and a documented failure/dead-letter behavior (mirroring the queue failure-handling principles in [event-and-queue-architecture.md](event-and-queue-architecture.md)).

### Synchronization API
- Consumers: the Flutter sync engine and any future offline venue server.
- Authentication: User Account + Device Identity, with a cached authorization snapshot validity window per [offline-authorization-model.md](offline-authorization-model.md).
- Scope: bidirectional — downloads reference data and authorization snapshots; uploads queued offline-captured records (Provisional, per [flutter-architecture.md, Section 4](flutter-architecture.md#4-record-states-local)) for server-side revalidation.

## 4. Avoiding Duplicate APIs

Per working rule and [react-inertia-architecture.md, Section 4](react-inertia-architecture.md#4-inertiajs-boundary), a dedicated API endpoint is created only when a genuine, distinct client (Flutter, a device, an external system) needs it — the administrative React/Inertia application does **not** get a parallel REST/API surface duplicating what Inertia already serves. This keeps the API surface area proportionate to actual client diversity rather than growing reflexively alongside every Inertia page.

## 5. Open Questions

- Specific API versioning scheme (URL-based, header-based) — implementation-phase decision, not architecturally blocking.
- Whether a GraphQL-style query surface is ever warranted for any client — no evidenced need currently; default is REST-style versioned JSON contracts per the categories above.
- Timing and shape of any future Administrative Integration API or Webhook API — deferred until a specific integration is approved (see [internal-integration-architecture.md, Section 4](internal-integration-architecture.md#4-external-integration-status)).
