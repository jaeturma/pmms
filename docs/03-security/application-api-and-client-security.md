# PMMS Application, API, and Client Security

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md) · [../01-architecture/react-inertia-architecture.md](../01-architecture/react-inertia-architecture.md) · [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) · [../01-architecture/event-and-queue-architecture.md](../01-architecture/event-and-queue-architecture.md)

This document defines application-layer, API, webhook, real-time, and frontend (React/Inertia) security requirements. **No middleware, validation rule, controller, or frontend code is created here.**

---

## 1. Application Security

| Risk | Control Direction |
|---|---|
| Input validation | Every Command/Query validates input before it reaches the Domain layer, per [../01-architecture/laravel-architecture.md, Section 3](../01-architecture/laravel-architecture.md#3-command-and-query-architecture) — never trust client-supplied data as pre-validated |
| Output encoding | Framework-managed (Blade/Inertia JSON serialization); no raw unescaped output of user-supplied content |
| CSRF | Laravel's default token mechanism, enforced for all state-changing requests |
| XSS | Output encoding plus a Content-Security-Policy candidate control; no `dangerouslySetInnerHTML`-equivalent rendering of unsanitized user content in React |
| SQL injection | Eloquent/query-builder parameterization by default; no raw SQL string concatenation of user input |
| Command injection | No shell-command construction from user input; where unavoidable, strict allowlisting |
| Path traversal | File paths are never constructed from raw user input; object keys are generated server-side, per [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| SSRF | Any server-initiated outbound request (webhook delivery, AI service calls, future integrations) validates and restricts target hosts — never fetches an arbitrary user-supplied URL |
| Open redirects | Redirect targets are validated against an allowlist, never a raw user-supplied URL |
| Mass assignment | Explicit fillable/guarded field lists on every model — never blanket mass assignment of request input |
| Insecure deserialization | No untrusted deserialization of PHP objects; session serialization remains `json`, not `php` (per [../01-architecture/caching-and-session-architecture.md](../01-architecture/caching-and-session-architecture.md) and the current `session.php` default) |
| File-upload abuse | Per [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| Template injection | No user-controlled template/view-path construction |
| Authorization bypass | Per [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) — every enforcement point re-checks, never trusts an upstream check alone |
| State-transition abuse | Domain-layer guards reject invalid state transitions (e.g., certifying a result whose scores are unvalidated), per [../02-data/temporal-history-and-versioning-model.md, Section 1](../02-data/temporal-history-and-versioning-model.md#1-state-and-status-persistence) |
| Race conditions | Optimistic locking and database transactions for high-integrity aggregates, per [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md) |
| Business-logic abuse | Domain-layer invariants (e.g., entry-lock timing, eligibility prerequisites) enforced server-side, never assumed from client-submitted state |
| Sensitive-error disclosure | Per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) — minimal information in user-facing errors, full detail only in restricted-access logs |
| Dependency misuse | Per [secure-development-lifecycle.md, Section 4](secure-development-lifecycle.md#4-dependency-and-supply-chain-security) |
| Insecure defaults | Every new feature starts deny-by-default and no-public-exposure-by-default |

## 2. API Security

| Control | Direction |
|---|---|
| Authentication | Every API category in [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md) authenticates via its own appropriate mechanism (session for Inertia, token for mobile/device, distinct credential for integration) |
| Scoped authorization | Every API token/credential carries the narrowest scope its client needs — never a blanket "full account" token |
| Request validation | Restated from Section 1 |
| Rate limiting | Per [../01-architecture/resilience-performance-and-scaling.md](../01-architecture/resilience-performance-and-scaling.md), most aggressive on the Public API; specific thresholds are an implementation-phase tuning decision |
| Abuse detection | Repeated failures, unusual volume, or pattern anomalies are candidate security-event triggers (Section 37 of the main document) |
| Pagination | Every list endpoint is paginated — no unbounded result set |
| Resource limits | Request/response size limits prevent resource-exhaustion abuse |
| Idempotency | State-changing API operations support idempotency keys where retries are expected (mobile sync, imports), per [../02-data/offline-sync-and-conflict-data-model.md, Section 4](../02-data/offline-sync-and-conflict-data-model.md#4-idempotency-data) |
| Replay protection | Time-boxed tokens/signatures and idempotency keys jointly reduce replay risk |
| Correlation IDs | Every API request/response pair carries a correlation ID for tracing, per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) |
| CORS | Restricted to approved origins — the administrative app's own origin plus any explicitly approved consumer, never a wildcard for authenticated endpoints |
| Versioning | API versioning strategy is anticipated but not finalized — an implementation-phase decision |
| Deprecation | A deprecated API version/endpoint has a defined sunset process, not an abrupt removal |
| Error minimization | API error responses avoid leaking internal implementation detail, stack traces, or existence-confirmation for unauthorized resources |
| Sensitive-data exclusion from URLs | No token, credential, or sensitive identifier ever appears in a URL query string (which is logged/cached more broadly than a request body) |
| Object-level authorization | Every object-returning endpoint checks authorization against the specific object requested, not merely the endpoint category (the OWASP "BOLA" risk) |
| Function-level authorization | Every action-performing endpoint checks the specific permission required, not merely that the requester is authenticated (the OWASP "BFLA" risk) |
| Mass-assignment protection | Restated from Section 1, applied specifically to API request bodies |
| Query filtering | Restated from [authorization-and-privileged-access-assurance.md, Section 1](authorization-and-privileged-access-assurance.md#1-authorization-assurance--preserving-the-phase-03-formula) |
| Export controls | Per [../02-data/import-export-and-data-exchange.md, Section 2](../02-data/import-export-and-data-exchange.md#2-export-architecture) |
| API logging | Every API request is logged with correlation ID, actor, and outcome — sensitive payload content is never logged in full (Section 38 of the main document) |
| Public API throttling | The Public API (per [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md)) has the strictest throttling, isolated from device/mobile API capacity |
| Device API isolation | Device/mobile APIs are architecturally and operationally isolated from public traffic capacity, per [../01-architecture/phase-0.4-application-integration-runtime-architecture.md, Section 18](../01-architecture/phase-0.4-application-integration-runtime-architecture.md#18-public-portal-runtime-boundary) |
| Webhook signing | See Section 3 below |

## 3. Webhook Security

No webhook is currently approved (per [../01-architecture/internal-integration-architecture.md, Section 4](../01-architecture/internal-integration-architecture.md#4-external-integration-status)). This section defines the requirements any future inbound or outbound webhook must satisfy before approval:

| Requirement | Direction |
|---|---|
| Signature verification | Every inbound webhook payload is verified against a shared secret or asymmetric signature before being trusted |
| Timestamp validation | A signed timestamp within the payload is checked against a reasonable window to reject stale replays |
| Replay prevention | Combined with idempotency keys (Section, [../02-data/offline-sync-and-conflict-data-model.md](../02-data/offline-sync-and-conflict-data-model.md)), a previously-processed webhook payload is not reprocessed |
| Shared-secret or asymmetric-signature readiness | Either mechanism is architecturally acceptable; selection is an implementation-phase decision once a specific webhook integration is approved |
| Source allowlisting where practical | Inbound webhook sources are restricted to known IP ranges/hosts where the provider supports it |
| Idempotency | Restated from Section 2 |
| Payload size limits | Oversized webhook payloads are rejected before processing |
| Schema validation | Payload structure is validated against an expected schema before being trusted |
| Error handling | A failed webhook is logged and retried per the provider's contract, never silently dropped |
| Retry handling | Outbound webhook delivery (if PMMS ever sends webhooks) uses bounded retry with backoff |
| Secret rotation | Webhook signing secrets are rotatable without a service outage |
| Audit | Every webhook receipt/delivery is an audit-relevant event |
| Dead-letter handling | Permanently failing webhook deliveries land in a reviewable dead-letter state, never silently discarded |
| No direct trust of webhook business claims | A webhook's claimed business fact (e.g., "payment succeeded") is verified against the source system's own authoritative state where feasible, never accepted purely on the webhook's say-so for a high-integrity decision |

## 4. Real-Time and Reverb Security

| Control | Direction |
|---|---|
| Channel authorization | Every Reverb channel subscription is authorized server-side via a channel-authorization callback, per [../01-architecture/realtime-architecture.md, Section 3](../01-architecture/realtime-architecture.md#3-rules) |
| Public versus private channels | Public channels (scoreboard, announcements) carry only Public-tier data; private/presence channels require authenticated, authorized subscription |
| Presence-channel restrictions | Presence-channel membership itself (who is viewing) is treated as potentially sensitive metadata, restricted to appropriate audiences |
| Minimal payloads | Broadcast payloads carry the minimum data needed for the UI update, never a full sensitive record |
| No protected data on public channels | Restricted/Confidential/Highly Restricted-tier data is never broadcast on a public channel, under any circumstance |
| Reconnection authorization | A reconnecting client re-authorizes its channel subscriptions — no assumption that a prior authorization persists indefinitely |
| Token expiry | Reverb connection tokens/credentials expire and require renewal |
| Channel revocation | A user whose access is revoked loses active channel subscriptions on their next connection check |
| Rate limiting | Broadcast-triggering events are rate-limited to prevent a single actor from flooding a channel |
| Event throttling | High-frequency updates (e.g., live scoring) are throttled/batched where appropriate to prevent overwhelming clients |
| Message size limits | Broadcast payload size is bounded |
| Fan-out abuse protection | A public channel with many subscribers (e.g., a popular meet's scoreboard) is protected against broadcast-triggered resource exhaustion |
| Public scoreboard isolation | Public real-time traffic is isolated from administrative/scoring-critical Reverb capacity, mirroring the public-traffic isolation principle from Phase 0.4 |
| Fallback behavior | Every real-time-driven UI has a normal polling/query fallback — a missed broadcast never leaves a client permanently stale, per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) |
| Audit for sensitive administrative channels | Subscription to any non-public administrative channel is itself audit-relevant |

## 5. React and Inertia Security

| Control | Direction |
|---|---|
| Server-authoritative permissions | The frontend never decides what a user is allowed to do — it reflects a server-provided authorization state, restated as the single most load-bearing rule from [../01-architecture/runtime-security-architecture.md, Section 2](../01-architecture/runtime-security-architecture.md#2-authorization-enforcement-points) |
| No secrets in props | Inertia page props never include an API secret, encryption key, or other credential |
| No unnecessary sensitive hydration | A page's Inertia props include only the data that specific page's UI needs — never a broader object dump "just in case" |
| Safe HTML rendering | No unsanitized user-supplied HTML rendering; React's default escaping is relied upon, not bypassed |
| XSS prevention | Restated from Section 1 |
| CSRF handling | Inertia's built-in CSRF token handling is retained, not bypassed |
| Sensitive-data masking | UI-layer masking (per [../02-data/audit-and-security-data-architecture.md, Section 4](../02-data/audit-and-security-data-architecture.md#4-data-masking-and-redaction)) is applied server-side before the data reaches the client, not client-side after full data delivery |
| Route-state handling | Sensitive route parameters avoid exposing internal sequential IDs where a public ID is the appropriate reference, per [../02-data/identifier-and-reference-strategy.md](../02-data/identifier-and-reference-strategy.md) |
| Real-time event validation | Client-received broadcast events are treated as hints to refetch/update, never as authoritative data to render without validation against the page's own authorization context |
| Public-cache restrictions | Pages containing non-Public data are never HTTP-cached in a way a subsequent unauthorized visitor on a shared device could retrieve |
| File-upload UX without trusting client validation | Client-side file-type/size checks are a UX convenience only — the server independently re-validates everything, per [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| Error-message minimization | Frontend error displays avoid echoing raw server exception detail |
| Permission-aware UI as convenience only | Hiding a button/action in the UI improves usability; it is never the actual access control |
| Browser storage restrictions | No Restricted/Highly Restricted-tier data is placed in `localStorage`/`sessionStorage`/browser cache |
| Avoid storing protected data in localStorage | Restated explicitly — this is a common real-world mistake this documentation exists to prevent |

## 6. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably API versioning strategy, specific rate-limit thresholds, and Content-Security-Policy scope, all implementation-phase tuning decisions not finalized here.
