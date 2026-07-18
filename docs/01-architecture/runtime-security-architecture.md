# PMMS Runtime Security Architecture

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.3-access-and-assignment-architecture.md](phase-0.3-access-and-assignment-architecture.md) · [authorization-decision-model.md](authorization-decision-model.md) · [security-rules.md](../../.ai/security-rules.md)

This document defines where and how Phase 0.3's authorization model is enforced at runtime, how authentication integrates with the broader identity model, and the general runtime security controls PMMS requires. **No Gate, Policy class, middleware, or security configuration is created here.**

---

## 1. Authentication Integration Boundary

**Authentication proves identity. Authorization determines allowed action.** These remain distinct per [phase-0.3-access-and-assignment-architecture.md, Section 36](phase-0.3-access-and-assignment-architecture.md#36-authentication-architecture-boundaries) — this section documents how the *result* of authentication (a proven User Account identity) integrates with everything downstream:

| Integration Point | Behavior |
|---|---|
| User Account context | A successful authentication resolves to exactly one User Account (per [identity-model.md](identity-model.md)) — never an ambiguous or shared identity |
| Person identity | The authenticated User Account's linked Person (if any) is available for downstream Participant Profile lookups, never re-derived ambiguously |
| Organization membership | Established separately from authentication itself — authenticating does not imply any specific Organization-scoped authority |
| Role and assignment activation | Authorization (Section 2 below) is evaluated fresh on every request using the authenticated identity — authentication does not "unlock" a cached permission set for the session's lifetime |
| Device trust | Evaluated as an independent input alongside user authentication, per [device-and-service-identity-model.md, Section 3](device-and-service-identity-model.md#3-device-trust-principles) — never substituted for it |
| MFA readiness | The existing Fortify-based scaffolding's 2FA support is the foundation; risk-based enforcement (mandatory MFA for high-integrity roles) is a policy layered on top, per [access-open-decisions.md, AD-21](access-open-decisions.md#ad-21--authentication-mechanism-selection-mfassorecovery) |
| Session lifecycle | Per [caching-and-session-architecture.md, Section 2](caching-and-session-architecture.md#2-session-architecture) |
| Mobile tokens | A distinct authentication mechanism from the web session cookie (Section, [api-and-client-boundaries.md](api-and-client-boundaries.md)) |
| Service accounts | Authenticate via their own Service Identity credential, never a human account's credential (per [device-and-service-identity-model.md, Section 6](device-and-service-identity-model.md#6-service-identity-principles)) |
| Account recovery | Owned by Identity and Access (BC-02), per [identity-model.md, Section 5](identity-model.md#5-account-recovery-ownership) |
| Risk-based controls | A future-phase capability (device/location anomaly signals feeding elevated challenge) — not implemented now, architecturally anticipated |
| Audit events | Every authentication attempt (success and failure) is an audit-relevant event, per [permission-catalog.md](permission-catalog.md)'s audit-level conventions |

## 2. Authorization Enforcement Points

The full 16-step decision sequence from [authorization-decision-model.md](authorization-decision-model.md) is evaluated at every one of the following entry points — **never partially applied**:

| Enforcement Point | Notes |
|---|---|
| HTTP entry points | Inertia controllers and API controllers alike |
| Application use cases | The Command/Query itself re-validates — a controller's own check is not trusted as sufficient in isolation (defense in depth) |
| Domain-sensitive transitions | State-transition guards within the Domain layer (e.g., "cannot certify a result whose scores aren't validated") |
| Query filtering | A list/search query filters results to what the requester is authorized to see, not merely blocking access to a single unauthorized record fetched by ID |
| File downloads | Every MinIO-backed download re-checks authorization at request time, per [object-storage-and-document-runtime.md, Section 3](object-storage-and-document-runtime.md#3-rules) |
| Broadcast channels | Reverb channel authorization callbacks, per [realtime-architecture.md, Section 3](realtime-architecture.md#3-rules) |
| Queue execution | A queued job re-validates the initiating actor's authority is still current before executing a sensitive action (per [event-and-queue-architecture.md, Section 2](event-and-queue-architecture.md#2-job-rules)) |
| Scheduled jobs | Execute under a Service Identity with narrowly scoped authority, never "as" an arbitrary human user |
| API endpoints | Every category in [api-and-client-boundaries.md](api-and-client-boundaries.md) |
| Synchronization endpoints | Per [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md) |
| Device actions | Device trust + operator authentication, per [device-and-service-identity-model.md](device-and-service-identity-model.md) |
| Report generation | Respects the requester's authorization and the underlying data's classification, per [reporting-search-and-read-model-runtime.md, Section 5](reporting-search-and-read-model-runtime.md#5-privacy-filtering-at-runtime) |
| Export creation | Same as report generation, plus audit logging of the export itself |
| Public publication | The specific, distinct Publication-tier authorization from [phase-0.3-access-and-assignment-architecture.md, Section 19](phase-0.3-access-and-assignment-architecture.md#19-approval-authority-levels) |

### Rules

- **Frontend checks do not replace backend authorization** — restated from [react-inertia-architecture.md, Section 5](react-inertia-architecture.md#5-rules) and [flutter-architecture.md, Section 5](flutter-architecture.md#5-rules); this is the single most load-bearing rule in this entire document.
- **Controllers delegate to centralized authorization decisions** — no controller hand-rolls its own ad hoc permission check; every check routes through the same decision-model implementation.
- **Background jobs must not assume the initiating user's access remains valid forever** — re-validated at execution time (Section, [event-and-queue-architecture.md](event-and-queue-architecture.md)).
- **Service identities have scoped execution authority** — never broad/administrative by default.
- **High-risk actions require actor and reason capture** — enforced at the enforcement point itself, not left to a downstream audit-log best-effort attempt.
- **Cross-context operations require ownership-aware authorization** — an operation touching two contexts checks authorization against each context's own rules, not a single blended check that might be more permissive than either context alone would allow.

## 3. Runtime Security Controls

| Control | PMMS Application |
|---|---|
| TLS | All traffic (web, API, device, mobile sync) encrypted in transit — non-negotiable for any environment beyond Local |
| Secure headers | Standard hardening headers (CSP, X-Frame-Options, etc.) applied platform-wide |
| CSRF | Enforced for all state-changing Inertia/web requests (Laravel default, retained) |
| CORS | Restricted to approved origins — the administrative app's own origin, and explicitly configured origins for any approved API consumer |
| Rate limiting | Per [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md), most aggressive on the Public API |
| Request validation | Every Command/Query validates input before it reaches the Domain layer (per [laravel-architecture.md, Section 3](laravel-architecture.md#3-command-and-query-architecture)) |
| File upload validation | Per [object-storage-and-document-runtime.md, Section 2](object-storage-and-document-runtime.md#2-document-flow) |
| Signed URLs | Time-boxed, per [object-storage-and-document-runtime.md, Section 3](object-storage-and-document-runtime.md#3-rules) |
| Queue payload protection | Identifiers only, no sensitive payloads (per [event-and-queue-architecture.md, Section 2](event-and-queue-architecture.md#2-job-rules)) |
| Broadcast authorization | Server-side channel authorization callbacks (per [realtime-architecture.md, Section 3](realtime-architecture.md#3-rules)) |
| Secret management | Per [environment-and-configuration-model.md](environment-and-configuration-model.md) |
| Session security | Per [caching-and-session-architecture.md, Section 2](caching-and-session-architecture.md#2-session-architecture) |
| API token security | Scoped, revocable, per [api-and-client-boundaries.md](api-and-client-boundaries.md) |
| Device credentials | Per [device-and-service-identity-model.md](device-and-service-identity-model.md) |
| Service accounts | Per [device-and-service-identity-model.md, Section 5–6](device-and-service-identity-model.md#5-service-identity-categories) |
| Webhooks | Signed payloads, none currently approved (per [internal-integration-architecture.md, Section 4](internal-integration-architecture.md#4-external-integration-status)) |
| Audit logs | Immutable, per [high-integrity-domain-rules.md](high-integrity-domain-rules.md) |
| Data encryption | At-rest for Highly Restricted data (medical, auth secrets), in-transit universally |
| Database access | Least-privilege application database credentials; no shared superuser credential used by the application at runtime |
| Redis access | Network-isolated, credentialed, never exposed publicly |
| MinIO access | Never directly exposed without the authorization path in [object-storage-and-document-runtime.md](object-storage-and-document-runtime.md) |
| Internal network isolation | Database, Redis, and MinIO are not reachable from outside the application's own network boundary |
| Administrative endpoint protection | Horizon dashboard, any future admin-only tooling, restricted to Platform/Security Administrator roles with elevated audit |
| Dependency management | Standard supply-chain hygiene (dependency updates, vulnerability scanning) — a later DevOps-phase operational practice, anticipated here |
| Error disclosure | Per [observability-and-error-handling.md](observability-and-error-handling.md) — minimal information in user-facing errors |
| AI integration | Per [phase-0.3-access-and-assignment-architecture.md, Section 29](phase-0.3-access-and-assignment-architecture.md#29-ai-authorization-boundary) and the main Phase 0.4 document's AI integration section — an AI feature never holds broader database credentials than the request it serves warrants |

## 4. Device Integration and AI Integration Cross-Reference

Detailed device-integration runtime (registration, credential issuance, heartbeat, revocation) and AI-service integration boundary (allowed modes, prohibited autonomous actions) are documented directly in [phase-0.4-application-integration-runtime-architecture.md, Sections 33–34](phase-0.4-application-integration-runtime-architecture.md#33-device-integration-runtime), building on [device-and-service-identity-model.md](device-and-service-identity-model.md) (Phase 0.3) rather than duplicated into a separate file here.

## 5. Open Questions

See [runtime-open-decisions.md](runtime-open-decisions.md) for security-architecture items requiring further validation (secret rotation cadence, malware scanning inclusion, risk-based authentication scope).
