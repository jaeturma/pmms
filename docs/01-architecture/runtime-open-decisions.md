# PMMS Runtime Open Decisions

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) · [domain-open-decisions.md](domain-open-decisions.md) (Phase 0.2) · [access-open-decisions.md](access-open-decisions.md) (Phase 0.3)

This document records unresolved **application, integration, and runtime** questions identified during Phase 0.4, distinct from Phase 0.2's `DD-XX` domain decisions and Phase 0.3's `AD-XX` access decisions, using `RD-XX` (Runtime Decision) identifiers. **No decision below is final.**

---

### RD-01 — Reliable Event Delivery Mechanism
- **Question:** Does PMMS implement a formal outbox table for critical domain-event delivery, or rely on Laravel's `after_commit` queue-dispatch semantics as a lighter-weight equivalent?
- **Areas affected:** [event-and-queue-architecture.md, Section 5](event-and-queue-architecture.md#5-reliable-delivery-outbox-consideration), [laravel-architecture.md, Section 4](laravel-architecture.md#4-transaction-boundaries).
- **Why it matters:** Affects the reliability guarantee for events feeding high-integrity workflows (e.g., `ResultCertified` → Medal Tally).
- **Options:** (a) Formal outbox table with a dedicated dispatcher; (b) `after_commit` queue dispatch (simpler, already a Laravel-native mechanism, per the `after_commit` option already visible in the repository's default `config/queue.php`).
- **Recommended direction:** Start with (b) for MVP scope, given it is already framework-native and requires no additional infrastructure; revisit (a) if measured event-loss incidents demonstrate the need.
- **Evidence required:** None blocking for the initial direction.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5 (implementation architecture)
- **Status:** Open — recommended direction stated

### RD-02 — Queue Category Naming Finalization
- **Question:** Are the 11 conceptual queue categories in [event-and-queue-architecture.md, Section 1](event-and-queue-architecture.md#1-queue-categories) the final literal queue names, or do they need adjustment once real workload patterns are known?
- **Areas affected:** Event and Queue Architecture, Horizon configuration (later phase).
- **Why it matters:** Queue names become operational conventions that are costly to rename once jobs are dispatched against them in production.
- **Options:** Adopt as-is; revise after initial implementation reveals actual workload shapes.
- **Recommended direction:** Adopt as a starting point; treat as revisable until first Production deployment, fixed thereafter.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5
- **Status:** Open

### RD-03 — Malware/Content Scanning for Uploads
- **Question:** Is a malware/content-scanning capability included in the initial document-upload flow, or deferred?
- **Areas affected:** [object-storage-and-document-runtime.md, Section 2](object-storage-and-document-runtime.md#2-document-flow).
- **Why it matters:** Affects security posture for user-uploaded content (eligibility evidence, medical attachments) versus initial implementation scope/cost.
- **Options:** Include from launch; defer with compensating controls (file-type/size validation only).
- **Recommended direction:** Defer with compensating controls at launch, given no evidenced threat pattern yet and the cost of integrating a scanning service; revisit if a security review recommends otherwise.
- **Evidence required:** Security review input.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Phase 0.5
- **Status:** Open — recommended direction stated

### RD-04 — Signed URL vs. Proxied Download Default
- **Question:** For each document category, is direct signed-URL access to MinIO used, or does every download proxy through the Laravel application?
- **Areas affected:** [object-storage-and-document-runtime.md, Section 3](object-storage-and-document-runtime.md#3-rules).
- **Why it matters:** Signed URLs reduce application server load for large files but require careful expiry/scope design; proxying gives tighter control but costs server resources.
- **Options:** Signed URLs for all downloads; proxied downloads for all; a hybrid based on classification/file size.
- **Recommended direction:** Hybrid — proxy Restricted/Highly Restricted downloads (tighter control, audit-at-point-of-serve), signed URLs for Public/Internal content (performance).
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5
- **Status:** Open — recommended direction stated

### RD-05 — Notification Channel Providers
- **Question:** Which SMS and push-notification providers does PMMS integrate with?
- **Areas affected:** [notification-architecture.md, Section 2](notification-architecture.md#2-delivery-channel-categories).
- **Why it matters:** No vendor is currently selected; this is a deferred integration per [Phase 0.1 product-scope.md, Section 8](../00-product/product-scope.md#8-deferred-integrations).
- **Options:** Deferred until approved.
- **Recommended direction:** None — explicitly not decided here, consistent with working rule against inventing integration requirements.
- **Evidence required:** DepEd-approved vendor list/procurement decision.
- **Decision owner:** To be identified (DepEd Leadership / ICT Committee)
- **Target phase:** Deferred
- **Status:** Open

### RD-06 — Concurrent Session Policy
- **Question:** Does PMMS restrict concurrent sessions (e.g., one active session per user) for any role tier, particularly high-integrity roles?
- **Areas affected:** [caching-and-session-architecture.md, Section 2](caching-and-session-architecture.md#2-session-architecture).
- **Why it matters:** Restricting concurrency reduces credential-sharing/compromise risk but adds friction and complicates legitimate multi-device use.
- **Options:** No restriction; restriction for high-integrity roles only; restriction platform-wide.
- **Recommended direction:** No restriction at launch; revisit for high-integrity roles if a security review or incident pattern warrants it.
- **Evidence required:** Security-policy input.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Phase 0.5
- **Status:** Open — recommended direction stated

### RD-07 — Sensitive-Action Reauthentication Triggers
- **Question:** Which specific high-integrity actions require a fresh authentication challenge (not merely a valid existing session)?
- **Areas affected:** [caching-and-session-architecture.md, Section 2](caching-and-session-architecture.md#2-session-architecture).
- **Why it matters:** Directly depends on which roles hold which high-integrity authorities — currently blocked.
- **Options:** N/A pending blocking dependency.
- **Recommended direction:** None.
- **Evidence required:** Resolution of [Phase 0.1 OD-07/OD-08/OD-09/OD-12](../00-product/open-decisions.md).
- **Decision owner:** To be identified
- **Target phase:** Phase 0.5, after blocking Phase 0.1 decisions resolve
- **Status:** Open — blocked

### RD-08 — Search Infrastructure Timing
- **Question:** At what point does PMMS introduce a dedicated search engine beyond MySQL-backed search?
- **Areas affected:** [reporting-search-and-read-model-runtime.md, Section 3](reporting-search-and-read-model-runtime.md#3-search-architecture).
- **Why it matters:** Mirrors [domain-open-decisions.md, DD-25](domain-open-decisions.md#dd-25--data-warehouse-timing)'s reasoning — premature infrastructure risk vs. delayed capability risk.
- **Options:** Introduce proactively; introduce only once MySQL-backed search demonstrably cannot serve a real query pattern.
- **Recommended direction:** Reactive — introduce only once justified by measured need.
- **Evidence required:** Pilot/production query-pattern data.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### RD-09 — Offline Conflict-Resolution Ownership
- **Question:** Which role reviews and resolves each type of sync conflict (score conflicts, schedule conflicts, etc.)?
- **Areas affected:** [offline-sync-runtime-architecture.md, Section 4](offline-sync-runtime-architecture.md#4-conflict-detection-and-resolution).
- **Why it matters:** Depends on the same blocked role-authority decisions as other high-integrity workflows.
- **Options:** N/A pending blocking dependency.
- **Recommended direction:** None.
- **Evidence required:** Resolution of blocked Phase 0.1/0.3 role-authority decisions.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.5, after blocking decisions resolve
- **Status:** Open — blocked

### RD-10 — Local Device Data Retention Before Cleanup
- **Question:** How long does synced-and-confirmed data remain on a mobile device before local cleanup?
- **Areas affected:** [offline-sync-runtime-architecture.md, Section 1](offline-sync-runtime-architecture.md#1-conceptual-components) ("Data cleanup").
- **Why it matters:** Balances device storage constraints and offline-resilience (re-access without re-download) against minimizing on-device exposure of synced sensitive data.
- **Options:** Aggressive cleanup (immediate post-confirmation); retained cleanup (kept for a defined window); user-configurable.
- **Recommended direction:** None — requires pilot-meet device-usage data, mirrors [access-open-decisions.md, AD-17](access-open-decisions.md#ad-17--offline-snapshot-validity-durations)'s reasoning.
- **Evidence required:** Pilot-meet data.
- **Decision owner:** To be identified (ICT Committee)
- **Target phase:** Post-pilot review
- **Status:** Open

### RD-11 — Secret Rotation Cadence
- **Question:** How frequently are application secrets (database credentials, Redis, MinIO keys, Reverb app keys) rotated?
- **Areas affected:** [environment-and-configuration-model.md, Section 3](environment-and-configuration-model.md#3-configuration-and-secrets-rules).
- **Why it matters:** Balances operational overhead against exposure-window minimization.
- **Options:** Fixed schedule; event-triggered only (e.g., on suspected compromise); hybrid.
- **Recommended direction:** Hybrid — event-triggered rotation always, plus a periodic baseline rotation for the highest-sensitivity credentials.
- **Evidence required:** Security-policy input.
- **Decision owner:** To be identified (Security Administrator)
- **Target phase:** Phase 0.5
- **Status:** Open — recommended direction stated

### RD-12 — Risk-Based Authentication Scope
- **Question:** Which specific risk signals (new device, unusual location, unusual time) trigger elevated authentication challenges, and for which roles?
- **Areas affected:** [runtime-security-architecture.md, Section 1](runtime-security-architecture.md#1-authentication-integration-boundary).
- **Why it matters:** A future-phase capability anticipated architecturally but not designed in detail here.
- **Options:** Deferred entirely; scoped to high-integrity roles only when introduced.
- **Recommended direction:** Deferred — no risk-based authentication at launch; architecture anticipates it (MFA-readiness already established in Phase 0.3) without building it now.
- **Evidence required:** None blocking for deferral.
- **Decision owner:** To be identified
- **Target phase:** Future scope
- **Status:** Open — recommended direction stated

### RD-13 — Log and Audit Retention Durations
- **Question:** How long are operational logs and audit records retained?
- **Areas affected:** [observability-and-error-handling.md, Section 2](observability-and-error-handling.md#2-logging-architecture).
- **Why it matters:** Mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements) — depends on DepEd records-management policy.
- **Options:** N/A pending policy input.
- **Recommended direction:** None.
- **Evidence required:** DepEd records-management policy.
- **Decision owner:** To be identified
- **Target phase:** Phase 0.5, contingent on OD-24
- **Status:** Open — blocked on Phase 0.1 OD-24

### RD-14 — Monitoring/Observability Stack Selection
- **Question:** Which monitoring/observability tooling (APM, log aggregation, alerting) does PMMS use?
- **Areas affected:** [observability-and-error-handling.md, Section 3](observability-and-error-handling.md#3-observability-architecture).
- **Why it matters:** A vendor/tooling decision, not an architecture decision — deliberately deferred per working rule ("do not select a monitoring vendor").
- **Options:** Deferred.
- **Recommended direction:** None — later DevOps-phase decision.
- **Evidence required:** DevOps-phase evaluation.
- **Decision owner:** To be identified (DevOps lead, once identified)
- **Target phase:** Later infrastructure/DevOps phase
- **Status:** Open

### RD-15 — Alerting Thresholds
- **Question:** What specific thresholds (queue depth, error rate, latency) trigger alerts?
- **Areas affected:** [observability-and-error-handling.md, Section 3](observability-and-error-handling.md#3-observability-architecture).
- **Why it matters:** Requires operational baseline data; arbitrary thresholds risk alert fatigue or missed incidents.
- **Options:** Set now (risk: unvalidated); set after pilot-meet baseline established.
- **Recommended direction:** Set after pilot-meet baseline, consistent with the approach taken for KPI targets in [Phase 0.1 success-framework.md](../00-product/success-framework.md#11-baseline-requirements).
- **Evidence required:** Pilot-meet operational data.
- **Decision owner:** To be identified (ICT Committee)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### RD-16 — Local Venue Synchronization Service
- **Question:** Is a dedicated local-venue synchronization service needed for venues with poor central connectivity, beyond the standard mobile offline model?
- **Areas affected:** [environment-and-configuration-model.md, Section 4](environment-and-configuration-model.md#4-runtime-deployment-units).
- **Why it matters:** Mirrors [access-open-decisions.md, AD-19](access-open-decisions.md#ad-19--extended-outage-policy)'s extended-outage reasoning — an additional infrastructure unit only justified by real connectivity data.
- **Options:** Build proactively; defer until pilot-meet connectivity data demonstrates need.
- **Recommended direction:** Defer — the standard Flutter offline model (per [offline-sync-runtime-architecture.md](offline-sync-runtime-architecture.md)) is the default; a local venue server is an escalation only if genuinely needed.
- **Evidence required:** Pilot-meet venue connectivity assessment.
- **Decision owner:** To be identified (ICT Committee)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### RD-17 — Feature Flag System
- **Question:** What mechanism governs feature flags (e.g., a config-based approach vs. a dedicated feature-flag service), and who has authority to change them?
- **Areas affected:** [environment-and-configuration-model.md, Section 2](environment-and-configuration-model.md#2-configuration-categories).
- **Why it matters:** Feature flags affecting high-integrity workflow availability require the same governance rigor as any other sensitive configuration change (per [environment-and-configuration-model.md, Section 3](environment-and-configuration-model.md#3-configuration-and-secrets-rules)).
- **Options:** Simple config-based flags (environment variables); a dedicated feature-flag package/service.
- **Recommended direction:** Simple config-based flags at launch, given the current scale; revisit if the number/complexity of flags grows.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5
- **Status:** Open — recommended direction stated

### RD-18 — RPO/RTO Targets
- **Question:** What are PMMS's Recovery Point Objective and Recovery Time Objective?
- **Areas affected:** [resilience-performance-and-scaling.md, Section 4](resilience-performance-and-scaling.md#4-backup-and-recovery-considerations).
- **Why it matters:** Directly shapes backup frequency and disaster-recovery investment.
- **Options:** N/A pending DepEd/legal input.
- **Recommended direction:** None.
- **Evidence required:** DepEd institutional-record requirements, legal/compliance input.
- **Decision owner:** To be identified (DepEd Leadership, Data Privacy and Legal Stakeholders)
- **Target phase:** Phase 0.5
- **Status:** Open

### RD-19 — Public Traffic Deployment Separation Timing
- **Question:** At what point, if ever, does the public portal become a physically separate deployable unit from the administrative application?
- **Areas affected:** [application-architecture.md, Section 3](application-architecture.md#3-public-portal-separation), [resilience-performance-and-scaling.md, Section 1](resilience-performance-and-scaling.md#1-scaling-boundaries).
- **Why it matters:** Premature separation adds operational complexity; delayed separation risks public traffic degrading critical workflows under real load.
- **Options:** Separate from launch; separate only if measured traffic demonstrates need.
- **Recommended direction:** Single deployment with strong application-layer isolation (Section 3 of [application-architecture.md](application-architecture.md)) at launch; separate only if measured pilot/production traffic demonstrates a genuine need — consistent with the modular-monolith-first direction from [phase-0.2-domain-architecture.md, Section 22](phase-0.2-domain-architecture.md#22-recommended-initial-architecture-direction).
- **Evidence required:** Pilot/production traffic data.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### RD-20 — CI Pipeline Introduction Timing
- **Question:** When is a CI pipeline (GitHub Actions or equivalent) introduced?
- **Areas affected:** [testing-architecture.md, Section 11](testing-architecture.md#11-open-questions).
- **Why it matters:** Explicitly out of scope for Phase 0.4 (working rule 9); needed before meaningful automated-test enforcement is possible.
- **Options:** N/A — deferred by working rule.
- **Recommended direction:** Introduce at the start of implementation (Phase 0.5 or the first coding phase), not before.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5 / first implementation phase
- **Status:** Open — recommended direction stated

### RD-21 — API Versioning Scheme
- **Question:** Is API versioning URL-based (`/api/v1/...`), header-based, or another convention?
- **Areas affected:** [api-and-client-boundaries.md, Section 5](api-and-client-boundaries.md#5-open-questions).
- **Why it matters:** A convention decision affecting every future API category.
- **Options:** URL-based (simplest, most explicit); header-based (cleaner URLs, less discoverable).
- **Recommended direction:** URL-based — simplest for mobile/device clients with constrained tooling to implement correctly.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5
- **Status:** Open — recommended direction stated

### RD-22 — Flutter Local Database Technology
- **Question:** What local persistence technology does the Flutter app use for its device-local store?
- **Areas affected:** [flutter-architecture.md, Section 7](flutter-architecture.md#7-open-questions).
- **Why it matters:** Implementation-phase detail, not architecturally blocking, but affects sync-engine design specifics.
- **Options:** Deferred to implementation phase.
- **Recommended direction:** None — a Flutter-ecosystem-specific technology choice appropriately deferred.
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (Flutter lead, once identified)
- **Target phase:** Phase 0.5 / mobile implementation phase
- **Status:** Open

### RD-23 — Client-Side State Management for Complex Features
- **Question:** Does any specific React feature (e.g., live scoring) need a dedicated state-management library beyond React's built-in state and Inertia's page-prop model?
- **Areas affected:** [react-inertia-architecture.md, Section 7](react-inertia-architecture.md#7-open-questions).
- **Why it matters:** Premature adoption adds complexity; delayed adoption risks awkward workarounds for a genuinely complex feature.
- **Options:** No dedicated library, evaluate per-feature as built; adopt one proactively.
- **Recommended direction:** No proactive adoption — evaluate only when a specific feature's implementation demonstrates real friction with the default approach.
- **Evidence required:** Concrete feature-implementation experience.
- **Decision owner:** To be identified (Frontend lead, once identified)
- **Target phase:** Phase 0.5 / frontend implementation phase
- **Status:** Open — recommended direction stated

### RD-24 — Real-Time Broadcast Throttling Strategy
- **Question:** What specific throttling/aggregation approach is used for high-volume public Reverb channels (e.g., a busy live scoreboard)?
- **Areas affected:** [realtime-architecture.md, Section 3](realtime-architecture.md#3-rules).
- **Why it matters:** Affects both server load and client experience under peak conditions.
- **Options:** Time-windowed batching; count-based coalescing; no throttling until measured need.
- **Recommended direction:** No throttling until measured need — avoid premature complexity, consistent with the platform's general bias against solving unmeasured problems.
- **Evidence required:** Pilot-meet real-time traffic data.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Post-pilot review
- **Status:** Open — recommended direction stated

### RD-25 — Admin Integration API and Webhook API Timing
- **Question:** When, if ever, are the Administrative Integration API and Webhook API categories (per [api-and-client-boundaries.md](api-and-client-boundaries.md)) actually built?
- **Areas affected:** API and Client Boundaries, Internal Integration Architecture.
- **Why it matters:** No external integration is currently approved (per [internal-integration-architecture.md, Section 4](internal-integration-architecture.md#4-external-integration-status)); building these categories without a concrete consumer is speculative work.
- **Options:** Build proactively as a general-purpose capability; build only when a specific integration is approved.
- **Recommended direction:** Build only when a specific integration is approved — the pattern (anti-corruption adapter) is documented now precisely so it doesn't need to be re-derived later, but the actual endpoints are not built speculatively.
- **Evidence required:** A specific, approved integration proposal.
- **Decision owner:** To be identified
- **Target phase:** Future scope
- **Status:** Open — recommended direction stated

### RD-26 — Non-Functional Test Tooling
- **Question:** What tooling is used for load/concurrency/failover testing?
- **Areas affected:** [testing-architecture.md, Section 8](testing-architecture.md#8-non-functional-tests).
- **Why it matters:** Implementation-phase tooling decision.
- **Options:** Deferred.
- **Recommended direction:** None — later implementation-phase decision, most meaningfully made once a pilot meet establishes realistic load profiles to test against.
- **Evidence required:** None blocking for deferral.
- **Decision owner:** To be identified
- **Target phase:** Post-pilot review
- **Status:** Open

### RD-27 — Circuit-Breaker Adoption Threshold
- **Question:** At what point does a future external integration warrant a circuit-breaker pattern versus simple retry/timeout?
- **Areas affected:** [resilience-performance-and-scaling.md, Section 3](resilience-performance-and-scaling.md#3-availability-and-resilience).
- **Why it matters:** No integration currently exists to apply this to; premature generalization is unwarranted.
- **Options:** Evaluate per-integration once one exists.
- **Recommended direction:** Evaluate per-integration at the time it is approved and built — not a blanket policy today.
- **Evidence required:** A specific, approved integration proposal.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Deferred, per-integration
- **Status:** Open — recommended direction stated

### RD-28 — Test-Data Management Strategy
- **Question:** How are test fixtures/factories managed to reflect PMMS's domain model (participants, meets, results) without ever touching production data?
- **Areas affected:** [testing-architecture.md, Section 11](testing-architecture.md#11-open-questions).
- **Why it matters:** PestPHP factories/seeders for test purposes are distinct from any production data seeding (explicitly out of scope per working rule 6) — this decision concerns test-only fixtures.
- **Options:** Standard Laravel model factories per domain module; a shared test-data-builder utility.
- **Recommended direction:** Standard Laravel factories, one per aggregate root, owned by each domain module's test suite — consistent with the modular boundary discipline in [laravel-architecture.md](laravel-architecture.md).
- **Evidence required:** None blocking.
- **Decision owner:** To be identified (software architect)
- **Target phase:** Phase 0.5 / first implementation phase
- **Status:** Open — recommended direction stated

---

## Summary of Blocking / High-Priority Runtime Decisions

- **RD-07, RD-09** — blocked directly on the still-unresolved Phase 0.1 role-authority decisions (OD-07/08/09/12).
- **RD-13, RD-18** — blocked on DepEd records-management/legal policy input (mirrors [Phase 0.1 OD-24](../00-product/open-decisions.md#od-24--data-retention-requirements)).
- **RD-19** — the most architecturally consequential open question in this phase (whether/when the public portal becomes a separate deployment unit), recommended to be resolved only with real traffic data, not speculatively.

These should be prioritized for stakeholder/engineering consultation as implementation approaches.
