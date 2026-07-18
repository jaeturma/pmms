# PMMS Caching and Session Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md, Section 36](phase-0.3-access-and-assignment-architecture.md#36-authentication-architecture-boundaries) · [runtime-security-architecture.md](runtime-security-architecture.md) · [reporting-search-and-read-model-runtime.md](reporting-search-and-read-model-runtime.md)

This document defines caching and session-storage boundaries. **No cache configuration, session driver, or code is created here.** The repository currently defaults to `CACHE_STORE=database` and `SESSION_DRIVER=database` (per `.env.example`) — this document defines the target Redis-backed direction without changing that configuration during this phase.

---

## 1. Caching Architecture

### Candidate Cache Targets

Public announcements, published schedules, published results, medal tally projections, Organization Directory references, Sports Catalog references, feature configuration, permission-decision fragments **where safe** (Section on rules below), public athlete-profile projections, dashboard summaries.

### Rules

- **Never cache sensitive records without explicit classification review.** A Restricted or Highly Restricted record (per [phase-0.3-access-and-assignment-architecture.md, Section 21](phase-0.3-access-and-assignment-architecture.md#21-data-classification-model)) is cached, if at all, only with the same access-control check applied on every cache read — caching never becomes a bypass of authorization.
- **Cache keys must avoid personal information** — a key is built from opaque identifiers (meet ID, sport ID, cache-purpose tag), never a participant's name or other PII, consistent with working rule 28.
- **Every cache has an owner** — the bounded context whose data is being cached is responsible for its invalidation strategy; there is no ownerless, ambient "the cache" shared indiscriminately across contexts.
- **Every cache has an invalidation strategy** — time-based expiry alone is insufficient for anything that must reflect a correction promptly (e.g., a published result correction must invalidate the cached public projection, not wait out a TTL).
- **Cache failure must not corrupt authoritative workflows** — if Redis is unavailable, the application falls back to querying the authoritative store directly (degraded performance, not degraded correctness); no write path depends on cache availability to function correctly.
- **High-integrity actions validate against authoritative state**, never a cached value — e.g., checking whether entries are locked before generating a draw reads the current database state, not a potentially-stale cache entry.
- **Public caches are invalidated after correction or unpublication** — a result correction (per [high-integrity-domain-rules.md](high-integrity-domain-rules.md)) or a protest-triggered hold must promptly invalidate any cached public projection, never leave stale/incorrect data visible for a full TTL window.
- **Stampede protection is considered for high-volume public data** — e.g., using locked/staggered cache regeneration for the public medal-tally board so a cache-expiry moment during peak traffic doesn't trigger a thundering-herd of simultaneous rebuild queries.
- **Cache versioning is considered for meet-specific projections** — a cache key incorporates the meet ID and, where relevant, a projection version/timestamp, so a new meet cycle never accidentally serves a prior meet's cached data.

### Permission-Decision Fragment Caching (Caution)

Caching a *fragment* of an authorization decision (e.g., "this user's active assignment list") is permissible **only** with a cache lifetime short enough to satisfy [scope-model.md, Section 5](scope-model.md#5-scope-expiry)'s requirement that scope expiry take effect immediately for sensitive actions — in practice, this means such caches are either extremely short-lived or invalidated synchronously on any assignment change (revocation, suspension, expiry), never left to a generic default TTL.

## 2. Session Architecture

### Candidate Session Options

Database sessions (current default), Redis sessions (target direction for Production, given Redis's confirmed role in the approved technology stack), environment-specific selection, administrative-session isolation, mobile token-based authentication, device credentials separate from user sessions.

### Requirements

- Secure cookies (HTTPOnly, Secure, SameSite — already Laravel defaults, retained).
- CSRF protection (already present via Laravel/Inertia's standard mechanism).
- Session rotation on privilege-relevant events (login, logout, role/assignment change).
- Session invalidation after privilege changes — consistent with [access-review-and-revocation.md, Section 10](access-review-and-revocation.md#10-session-invalidation): a mid-session assignment revocation invalidates the specific authority, and where warranted, the session itself.
- Session revocation — an administrator (or the user themselves, "log out other devices") can revoke a specific session.
- Concurrent-session policy readiness — the architecture does not assume single-session-per-user, but also does not preclude a future policy restricting it for high-integrity roles (see [runtime-open-decisions.md](runtime-open-decisions.md)).
- Sensitive-action reauthentication readiness — a high-integrity action (e.g., a Result Certifier's certification) may in a future phase require a fresh authentication challenge, not merely an old, still-valid session.
- Device and location metadata considerations — for anomaly detection (feeding Security Operations, BC-25) without becoming an intrusive default.
- **No sensitive business data stored directly in session payloads** — a session carries identity/authentication state, never eligibility case content, medical data, or similar.
- **Clear separation of web session, mobile token, API client, and QR credential** — these are four distinct authentication mechanisms (per [identity-model.md](identity-model.md)), never conflated: a web session cookie is not valid as a mobile API token; a QR accreditation credential is never a login credential (per [phase-0.3-access-and-assignment-architecture.md, Section 36](phase-0.3-access-and-assignment-architecture.md#36-authentication-architecture-boundaries)).

### Environment-Specific Direction

- **Local/Development**: database sessions/cache are acceptable — simpler local setup, no Redis dependency required for basic development.
- **Staging/Pilot/Production**: Redis-backed sessions and cache are the target direction, consistent with Redis's confirmed role in the approved technology stack and the performance/scale needs of a live multi-venue meet.

**No session storage is selected/changed during this phase** — this section states the target direction for a later configuration change, not an action taken now.

## 3. Open Questions

- Concurrent-session policy for high-integrity roles (Section 2) — security-policy decision, not yet made.
- Sensitive-action reauthentication trigger list (which specific actions require a fresh challenge) — depends on the blocked Phase 0.1/0.3 role-authority decisions (OD-07/08/09/12) being resolved first, since the actions in question are exactly those high-integrity certifications.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
