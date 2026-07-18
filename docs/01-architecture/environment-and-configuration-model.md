# PMMS Environment and Configuration Model

**Status:** Draft Complete — Pending Architecture, Security, and Engineering Validation
**Related:** [phase-0.4-application-integration-runtime-architecture.md](phase-0.4-application-integration-runtime-architecture.md) · [runtime-security-architecture.md](runtime-security-architecture.md) · [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md)

This document defines environments, configuration categories, secrets handling, and conceptual deployment units. **No `.env` file, Docker file, or deployment script is created here.**

---

## 1. Environment Model

| Environment | Purpose | Data Type | Access | Integration Behavior | Logging Level | Queue Behavior | Reverb Behavior | MinIO Usage | External Exposure |
|---|---|---|---|---|---|---|---|---|---|
| **Local** | Individual developer machine | Synthetic/fake | Developer only | Mocked/stubbed | Verbose (Debug) | Sync or local worker | Local Reverb instance or stubbed | Local disk or local MinIO container | None |
| **Development** | Shared integration environment for the dev team | Synthetic | Dev team | Sandboxed integrations where any exist | Verbose | Local/shared workers | Shared dev Reverb instance | Dev MinIO instance | Internal only |
| **Test** | Automated test execution (CI, when introduced) | Synthetic, ephemeral | CI system only | Mocked | Minimal (test-relevant only) | Sync (no real queue latency in tests) | Stubbed | Stubbed/in-memory equivalent | None |
| **Staging** | Pre-production validation, production-like configuration | Synthetic or sanitized | Project team | Sandboxed integrations, production-like behavior | Standard | Real queue/Horizon | Real Reverb | Staging MinIO instance | Restricted (VPN/allowlist) |
| **Pilot** | The first real meet run on PMMS | **Real**, but scoped to the pilot meet | Meet stakeholders, project team | Real (whatever integrations are approved by then) | Standard, elevated for security | Real | Real | Real | Restricted to pilot participants + public portal as approved |
| **Production** | Live, ongoing operation | Real | Authorized users only | Real | Standard, elevated for security | Real | Real | Real | Public (public portal), restricted (administrative) |
| **Disaster Recovery** | Standby/failover target | Replicated real data | Same as Production, activated on failover | Real | Standard | Real | Real | Real (replicated) | Same as Production upon activation |

**Not every environment must exist immediately** — Local and Development are needed from the start of implementation; Test formalizes once CI is introduced (a later phase); Staging, Pilot, and Disaster Recovery are introduced as the project approaches and passes its first real deployment.

### Cross-Environment Rules
- **Production data must not be copied casually into lower environments.** Any Staging/Development dataset derived from Production must be synthetic or properly sanitized/de-identified — never a raw copy, given the sensitivity of eligibility, medical, and minor-athlete data.
- Reset policy: Local/Development/Test are freely resettable; Staging is periodically reset to a known-clean state; Pilot and Production are never casually reset.
- Backup expectations scale with environment — Pilot and Production require the full backup/recovery discipline in [resilience-performance-and-scaling.md](resilience-performance-and-scaling.md); lower environments do not.

## 2. Configuration Categories

Application, Database, Redis, Queue, Horizon, Reverb, MinIO, Email, SMS, Push, AI, External integrations, Feature flags, Security, Session, Cache, Logging.

## 3. Configuration and Secrets Rules

- **Secrets are never committed** to the repository — `.env` files remain untracked (already the repository's default `.gitignore` behavior), and this discipline extends to every new configuration category introduced (Redis credentials, MinIO keys, Reverb app keys, any future AI-service or integration API keys).
- **Environment files are not shared as production credentials** — a Development `.env` never contains real Production secrets, even temporarily.
- **Secret rotation must be possible** — no configuration category is designed assuming a credential is permanent and unrotatable (specific rotation cadence is an operational-policy decision, per [access-open-decisions.md, AD-14](access-open-decisions.md#ad-14--device-credential-rotation-cadence) for the device-credential analog, and [runtime-open-decisions.md](runtime-open-decisions.md) for the broader question).
- **Configuration defaults are safe** — where a value is not explicitly set, the default never silently widens access or weakens security (e.g., no default that disables CSRF, no default that grants broad CORS access).
- **Environment-specific values are explicit** — no configuration value is inferred implicitly from environment name string-matching in a fragile way; explicit environment-specific configuration files/values are used.
- **Sensitive configuration never appears in client bundles** — nothing feeding the React/Inertia frontend build or the Flutter mobile build contains a server-side secret; `VITE_`-prefixed (or equivalent) client-exposed variables are reviewed specifically for this before being added.
- **Mobile applications do not contain privileged server secrets** — the Flutter app authenticates via user/device credentials (per [device-and-service-identity-model.md](device-and-service-identity-model.md)), never an embedded API master key.
- **Device credentials are revocable** — per [access-review-and-revocation.md](access-review-and-revocation.md).
- **Configuration changes affecting rules are auditable where appropriate** — e.g., a feature-flag change affecting a high-integrity workflow's availability is itself an audited event, not a silent config-file edit.

## 4. Runtime Deployment Units

Conceptual deployable units (topology, not code):

| Unit | Role |
|---|---|
| Laravel web application | Serves Inertia/administrative traffic and API traffic |
| Queue workers | Execute background jobs (per [event-and-queue-architecture.md](event-and-queue-architecture.md)), supervised by Horizon |
| Scheduler | Triggers scheduled/maintenance tasks (Laravel's task scheduler) |
| Reverb server | Real-time broadcast delivery |
| Public web runtime | May initially be the same Laravel web application unit, with the public-vs-administrative separation enforced at the application layer (per [application-architecture.md, Section 3](application-architecture.md#3-public-portal-separation)) rather than a separate deployable, pending evidence it needs to be physically separate |
| MinIO | Object storage service |
| Redis | Cache/queue/session/Reverb backing store |
| MySQL | Authoritative relational database |
| Flutter mobile application | Distributed to field-operations devices, not a server-side deployable unit |
| Optional local venue synchronization service | A conceptual future unit for venues with poor central connectivity — not committed to in this phase (see [runtime-open-decisions.md](runtime-open-decisions.md)) |
| Optional reporting worker | A dedicated worker pool for `analytics`/`exports` queue categories, separable from general workers if load warrants it |
| Optional media-processing worker | A dedicated worker pool for `media` queue category, separable if load warrants it |

**These units may initially be deployed together (e.g., on one or a small number of servers) or separately, depending on environment and scale** — this document does not commit to a specific physical topology; it establishes the logical units that later DevOps/infrastructure phases will place.

## 5. Open Questions

- Specific secret-rotation cadence per configuration category (Section 3).
- Whether/when a local venue synchronization service (Section 4) becomes a real requirement — depends on pilot-meet connectivity findings, mirroring the reasoning in [access-open-decisions.md, AD-19](access-open-decisions.md#ad-19--extended-outage-policy).
- Feature-flag system selection and governance.

Tracked in [runtime-open-decisions.md](runtime-open-decisions.md).
