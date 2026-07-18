# PMMS DevOps, Environment, CI/CD, Deployment, and Operations — Open Decisions

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [devops-and-platform-operations-strategy.md](devops-and-platform-operations-strategy.md) · [../04-quality/quality-open-decisions.md](../04-quality/quality-open-decisions.md) · [../03-security/security-open-decisions.md](../03-security/security-open-decisions.md)

This document tracks every unresolved Phase 0.8 decision using Decision ID prefix `DV-` (DevOps), distinct from Phase 0.1's `OD-`, Phase 0.2's `DD-`, Phase 0.3's `AD-`, Phase 0.4's `RD-`, Phase 0.5's `PD-`, Phase 0.6's `SD-`, and Phase 0.7's `QD-` series. Each entry follows the established format: Question / Areas Affected / Why It Matters / Options / Recommended Direction / Evidence Required / Decision Owner / Target Phase / Status.

---

### DV-01 — Deployment Topology and Cloud-Versus-On-Premise

- **Question:** Is PMMS deployed to a cloud provider, on-premise, or a hybrid model, and which cloud provider if applicable?
- **Areas affected:** [deployment-topology-and-runtime-units.md](deployment-topology-and-runtime-units.md), [cost-resource-and-capacity-governance.md, Section 3](cost-resource-and-capacity-governance.md#3-cloud-versus-on-premise-strategy)
- **Why it matters:** The single largest unresolved DevOps question — blocks finalizing network architecture, stateful-service hosting, container-registry selection, and DR-environment design.
- **Options:** Cloud (specific provider TBD) · on-premise · hybrid.
- **Recommended direction:** None — mirrors [Phase 0.1, Section 17](../00-product/phase-0.1-product-foundation.md#17-deployment-model) and [../03-security/security-open-decisions.md, SD-22](../03-security/security-open-decisions.md#sd-22--deployment-topology-cross-reference) exactly, carried unresolved across five prior phases.
- **Evidence required:** DepEd infrastructure/budget decision.
- **Decision owner:** DepEd Leadership + Infrastructure owner
- **Target phase:** Pre-0.9, blocking
- **Status:** Open — blocking, mirrors SD-22

### DV-02 — CI Platform Selection

- **Question:** Which CI platform executes the pipeline in [ci-cd-and-release-pipeline-architecture.md](ci-cd-and-release-pipeline-architecture.md)?
- **Areas affected:** [ci-cd-and-release-pipeline-architecture.md](ci-cd-and-release-pipeline-architecture.md)
- **Why it matters:** Mirrors [../04-quality/quality-open-decisions.md, QD-03](../04-quality/quality-open-decisions.md#qd-03--ci-platform-and-pipeline-configuration).
- **Options:** GitHub Actions (natural fit given confirmed GitHub direction) vs. an alternative.
- **Recommended direction:** GitHub Actions.
- **Evidence required:** None — low-risk, reversible.
- **Decision owner:** DevOps reviewer
- **Target phase:** 0.9+
- **Status:** Open — mirrors QD-03

### DV-03 — Secret-Management Platform Selection

- **Question:** What platform manages the secret inventory in [configuration-feature-flag-and-secret-management.md, Section 3](configuration-feature-flag-and-secret-management.md#3-secret-management)?
- **Areas affected:** [configuration-feature-flag-and-secret-management.md](configuration-feature-flag-and-secret-management.md)
- **Why it matters:** Every deployed environment beyond Local needs a real secret-delivery mechanism.
- **Options:** Environment variables managed via CI/CD platform secrets (simplest, sufficient initially) vs. a dedicated managed secret store (Vault-style), adopted as scale/compliance need grows.
- **Recommended direction:** CI/CD-managed environment variables initially; a dedicated secret-management platform evaluated once team size or compliance requirements justify the added operational complexity.
- **Evidence required:** DV-01 (topology) and DV-02 (CI platform) resolution.
- **Decision owner:** Security owner + Infrastructure owner
- **Target phase:** 0.9+
- **Status:** Open

### DV-04 — Feature-Flag Platform Selection

- **Question:** Is a dedicated feature-flag platform adopted, or is flagging implemented via simple configuration?
- **Areas affected:** [configuration-feature-flag-and-secret-management.md, Section 4](configuration-feature-flag-and-secret-management.md#4-feature-flag-architecture)
- **Why it matters:** A dedicated platform offers richer rollout control at added cost/complexity.
- **Options:** Simple database/config-driven flags (sufficient initially) vs. a dedicated platform.
- **Recommended direction:** Simple config-driven flags initially, given current team size and feature-flag volume.
- **Evidence required:** None — low-risk, reversible.
- **Decision owner:** Technical lead
- **Target phase:** 0.9
- **Status:** Open

### DV-05 — Branch Protection and CODEOWNERS Adoption Timing

- **Question:** When are GitHub branch-protection rules and a CODEOWNERS file adopted?
- **Areas affected:** [source-control-branching-and-release-workflow.md, Section 1](source-control-branching-and-release-workflow.md#1-source-control-workflow)
- **Why it matters:** Currently no CODEOWNERS exists and branch protection is unconfirmed; both become more valuable as team size grows.
- **Options:** Adopt now (single-contributor repository) vs. adopt once a second contributor joins.
- **Recommended direction:** Basic branch protection (no force-push to `main`, required status checks once CI exists) as soon as CI is established (DV-02); CODEOWNERS once module ownership is concretely assigned to specific engineers.
- **Evidence required:** Team-growth timeline.
- **Decision owner:** Technical lead
- **Target phase:** 0.9+
- **Status:** Open

### DV-06 — Release Versioning Scheme Confirmation

- **Question:** Is semantic versioning formally adopted and enforced (e.g., via tooling) for the application release artifact?
- **Areas affected:** [source-control-branching-and-release-workflow.md, Section 4](source-control-branching-and-release-workflow.md#4-release-versioning)
- **Why it matters:** A recommended direction exists; formal enforcement tooling is not yet selected.
- **Options:** Manual semantic versioning discipline vs. automated tooling (e.g., conventional-commits-driven automatic version bumping).
- **Recommended direction:** Manual discipline initially; automated tooling evaluated once conventional-commits adoption (Section, [source-control-branching-and-release-workflow.md, Section 2](source-control-branching-and-release-workflow.md#2-commit-standards)) is consistent enough to drive it reliably.
- **Evidence required:** None — low-risk, reversible.
- **Decision owner:** Technical lead
- **Target phase:** 0.9+
- **Status:** Open

### DV-07 — Release-Train Cadence

- **Question:** Is a predictable release-train cadence adopted, and if so, what cadence?
- **Areas affected:** [source-control-branching-and-release-workflow.md, Section 5](source-control-branching-and-release-workflow.md#5-release-trains)
- **Why it matters:** Directly entangled with [../04-quality/quality-open-decisions.md, QD-22](../04-quality/quality-open-decisions.md#qd-22--release-cadence).
- **Options:** Continuous delivery vs. meet-cycle-aligned scheduled releases.
- **Recommended direction:** Aligns with QD-22's recommended direction — meet-cycle-aligned scheduled releases initially.
- **Evidence required:** QD-22 resolution.
- **Decision owner:** Release approver + Product owner
- **Target phase:** 0.9+
- **Status:** Open — mirrors QD-22

### DV-08 — Docker Adoption Timing for Local Development

- **Question:** Is Sail/Docker-based local development adopted before or after the first pilot?
- **Areas affected:** [local-development-environment.md, Section 3](local-development-environment.md#3-local-development-tooling-evaluation), [containerization-and-docker-adoption-roadmap.md, "Phase B"](containerization-and-docker-adoption-roadmap.md#phase-b--local-development-candidate-not-committed)
- **Why it matters:** Affects developer onboarding speed and environment-parity confidence.
- **Options:** Adopt before pilot (more upfront tooling investment) vs. after (native development continues, Docker introduced once team grows).
- **Recommended direction:** After the first pilot, given the current team size and the native-development approach already working via the confirmed `composer.json`/`package.json` scripts.
- **Evidence required:** Team-growth timeline, Windows-performance evaluation of Docker Desktop.
- **Decision owner:** Technical lead
- **Target phase:** 0.9+
- **Status:** Open

### DV-09 — Reverse-Proxy Product Selection

- **Question:** Which reverse-proxy product terminates TLS and routes traffic?
- **Areas affected:** [network-reverse-proxy-tls-and-domain-architecture.md, Section 1](network-reverse-proxy-tls-and-domain-architecture.md#1-reverse-proxy-architecture)
- **Why it matters:** A foundational infrastructure choice affecting WebSocket (Reverb) proxying support specifically.
- **Options:** Nginx (common default, strong WebSocket support) vs. Caddy (simpler automatic-TLS story) vs. a cloud-native load balancer if DV-01 resolves toward a specific cloud provider.
- **Recommended direction:** None — depends directly on DV-01's resolution.
- **Evidence required:** DV-01 resolution.
- **Decision owner:** Infrastructure owner
- **Target phase:** 0.9+
- **Status:** Open — depends on DV-01

### DV-10 — Stateful Service Hosting Model (Self-Hosted vs. Managed)

- **Question:** Are MySQL, Redis, and MinIO self-hosted (containerized or on dedicated servers) or run as managed cloud services?
- **Areas affected:** [mysql-redis-minio-and-stateful-service-operations.md](mysql-redis-minio-and-stateful-service-operations.md), [containerization-and-docker-adoption-roadmap.md, "Phase D"](containerization-and-docker-adoption-roadmap.md#phase-d--staging-and-production)
- **Why it matters:** Directly affects operational burden, cost (per [cost-resource-and-capacity-governance.md, Section 2](cost-resource-and-capacity-governance.md#2-cost-management)), and failover/backup mechanics.
- **Options:** Fully self-hosted vs. fully managed vs. a mix (e.g., managed MySQL, self-hosted MinIO).
- **Recommended direction:** None — depends directly on DV-01's resolution.
- **Evidence required:** DV-01 resolution, cost comparison once a provider is known.
- **Decision owner:** Infrastructure owner
- **Target phase:** 0.9+
- **Status:** Open — depends on DV-01

### DV-11 — Blue-Green/Canary Deployment Adoption Timing

- **Question:** When does PMMS adopt blue-green or canary deployment beyond the initial in-place/rolling approach?
- **Areas affected:** [deployment-strategies-rollbacks-and-maintenance.md, Section 1](deployment-strategies-rollbacks-and-maintenance.md#1-deployment-strategy-evaluation)
- **Why it matters:** A meaningful infrastructure-cost and complexity increase, justified only once institutional stakes/traffic demonstrate the need.
- **Options:** Adopt before general availability vs. after, based on demonstrated reliability need.
- **Recommended direction:** After the pilot, once real deployment-risk evidence justifies the added infrastructure investment.
- **Evidence required:** Pilot and early-production deployment incident history.
- **Decision owner:** Infrastructure owner
- **Target phase:** Post-pilot
- **Status:** Open

### DV-12 — Online Schema-Change Tool Adoption

- **Question:** Is a dedicated online-schema-change tool (e.g., `gh-ost`-style) adopted for high-volume table migrations?
- **Areas affected:** [database-migration-and-release-safety.md, Section 3](database-migration-and-release-safety.md#3-specific-migration-safety-concerns)
- **Why it matters:** Relevant specifically for the "Very High" volume tables flagged in [../02-data/indexing-performance-and-capacity.md, Section 2](../02-data/indexing-performance-and-capacity.md#2-capacity-and-growth-categories).
- **Options:** Rely on MySQL's native online DDL support initially vs. adopt dedicated tooling proactively.
- **Recommended direction:** Native online DDL initially; dedicated tooling evaluated once real table volume at Phase 0.9's physical-schema stage demonstrates a genuine need.
- **Evidence required:** Real table-volume data from Phase 0.9 implementation.
- **Decision owner:** Infrastructure owner
- **Target phase:** 0.9+
- **Status:** Open

### DV-13 — Monitoring/Observability Platform Selection

- **Question:** Which observability platform implements [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)?
- **Areas affected:** [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md)
- **Why it matters:** Directly mirrors [../01-architecture/runtime-open-decisions.md, RD-14](../01-architecture/runtime-open-decisions.md#rd-14--monitoringobservability-stack-selection), carried unresolved across five prior phases.
- **Options:** A self-hosted stack (e.g., Prometheus/Grafana-style) vs. a managed observability SaaS vs. Laravel-ecosystem-native tooling (Pulse/Telescope, both already present as disabled config flags in `phpunit.xml`).
- **Recommended direction:** None — genuinely open, entangled with DV-01.
- **Evidence required:** DV-01 resolution, team observability-tooling familiarity.
- **Decision owner:** Infrastructure owner
- **Target phase:** 0.9+
- **Status:** Open — mirrors RD-14

### DV-14 — Alerting and On-Call Tooling Selection

- **Question:** What tool routes and manages alerts and on-call rotation?
- **Areas affected:** [observability-logging-metrics-tracing-and-alerting.md, Section 6](observability-logging-metrics-tracing-and-alerting.md#6-alerting-architecture), [incident-problem-change-and-release-management.md, Section 2](incident-problem-change-and-release-management.md#2-on-call-readiness)
- **Why it matters:** No production environment exists yet to be on-call for — this is a readiness question, not an immediate need.
- **Options:** A dedicated on-call platform vs. lightweight team-communication-tool-based alerting initially.
- **Recommended direction:** Deferred until Production exists and real on-call need is demonstrated.
- **Evidence required:** Production launch timeline.
- **Decision owner:** Infrastructure owner
- **Target phase:** Pre-launch
- **Status:** Open

### DV-15 — First SLO-Setting Exercise Timing

- **Question:** When does PMMS set its first real Service-Level Objectives?
- **Areas affected:** [service-level-capacity-and-performance-management.md, Section 1](service-level-capacity-and-performance-management.md#1-service-level-management)
- **Why it matters:** SLOs are only meaningful once pilot data exists to ground them.
- **Options:** N/A — timing question only.
- **Recommended direction:** Immediately following the first pilot's completion.
- **Evidence required:** Pilot completion.
- **Decision owner:** Quality owner + Infrastructure owner
- **Target phase:** Post-pilot
- **Status:** Open

### DV-16 — Disaster-Recovery Environment Provisioning Timing

- **Question:** Is a DR environment provisioned before or only after the first pilot meet?
- **Areas affected:** [backup-restore-disaster-recovery-and-continuity.md, Section 4](backup-restore-disaster-recovery-and-continuity.md#4-disaster-recovery)
- **Why it matters:** Mirrors the still-open Phase 0.5 question in [../02-data/backup-restore-and-data-recovery.md, Section 6](../02-data/backup-restore-and-data-recovery.md#6-open-questions).
- **Options:** Before pilot (higher upfront cost, stronger pilot-time protection) vs. after (lower initial cost, accepted pilot-time risk).
- **Recommended direction:** After the pilot for a genuinely dedicated DR environment, with robust backup/restore capability (Section, [backup-restore-disaster-recovery-and-continuity.md, Sections 1–2](backup-restore-disaster-recovery-and-continuity.md#1-backup-architecture)) as the pilot-time minimum protection.
- **Evidence required:** DV-01 resolution, budget approval.
- **Decision owner:** Infrastructure owner + DepEd Leadership
- **Target phase:** Post-pilot
- **Status:** Open

### DV-17 — Numeric RPO/RTO Targets

- **Question:** What are the numeric Recovery Point/Time Objectives?
- **Areas affected:** [backup-restore-disaster-recovery-and-continuity.md, Section 3](backup-restore-disaster-recovery-and-continuity.md#3-point-in-time-recovery-readiness)
- **Why it matters:** Mirrors [../03-security/security-open-decisions.md, SD-24](../03-security/security-open-decisions.md#sd-24--rporto-numeric-targets-cross-reference), [../02-data/data-open-decisions.md, PD-23](../02-data/data-open-decisions.md#pd-23--rporto-numeric-targets), and [../01-architecture/runtime-open-decisions.md, RD-18](../01-architecture/runtime-open-decisions.md#rd-18--rporto-targets) exactly — the single longest-carried open decision across this entire architecture effort.
- **Options:** N/A — requires DepEd institutional-record requirements.
- **Recommended direction:** None — genuinely open across five phases now.
- **Evidence required:** DepEd institutional requirements, infrastructure capability assessment.
- **Decision owner:** Infrastructure owner + DepEd Leadership
- **Target phase:** Pre-launch
- **Status:** Open — mirrors SD-24/PD-23/RD-18

### DV-18 — Support-Tier Staffing and Hours Model

- **Question:** Who staffs Tiers 1–3 support, and during what hours (ordinary operation vs. meet-day)?
- **Areas affected:** [production-support-access-and-data-repair-operations.md, Section 3](production-support-access-and-data-repair-operations.md#3-support-model)
- **Why it matters:** A genuine operational-planning question outside this documentation phase's authority.
- **Options:** Dedicated support staff vs. engineering team covering support informally at current scale.
- **Recommended direction:** Engineering-covers-support informally pre-pilot, with a dedicated model established as usage scale justifies it.
- **Evidence required:** DepEd staffing/budget planning.
- **Decision owner:** DepEd Leadership + Quality owner
- **Target phase:** Pre-launch
- **Status:** Open

### DV-19 — Vulnerability-Remediation SLA Policy

- **Question:** What remediation-time expectation applies per vulnerability severity?
- **Areas affected:** [patch-vulnerability-and-dependency-operations.md, Section 2](patch-vulnerability-and-dependency-operations.md#2-vulnerability-operations)
- **Why it matters:** Restated per the phase's own working instruction not to define remediation deadlines without an approved policy.
- **Options:** None — requires a named policy owner.
- **Recommended direction:** None.
- **Evidence required:** Security-owner-approved policy.
- **Decision owner:** Security owner
- **Target phase:** 0.9+
- **Status:** Open

### DV-20 — Multi-Organization Support Adoption (Cross-Reference)

- **Question:** Does PMMS ever support multiple organizations operationally?
- **Areas affected:** [tenant-onboarding-offboarding-and-data-portability.md](tenant-onboarding-offboarding-and-data-portability.md)
- **Why it matters:** Mirrors [Phase 0.1, OD-02](../00-product/open-decisions.md#od-02--single-organization-versus-multi-organization) exactly — entirely outside this phase's authority to resolve.
- **Options:** Per OD-02.
- **Recommended direction:** None — carried unchanged.
- **Evidence required:** Product-direction decision, outside DevOps's scope.
- **Decision owner:** Product owner + DepEd Leadership
- **Target phase:** Unresolved since Phase 0.1
- **Status:** Open — mirrors OD-02

### DV-21 — Container Registry Selection

- **Question:** Which container registry stores images once Docker adoption reaches Phase D?
- **Areas affected:** [containerization-and-docker-adoption-roadmap.md, Section 3](containerization-and-docker-adoption-roadmap.md#3-container-registry-strategy-not-yet-selected)
- **Why it matters:** A prerequisite for the Staging/Production containerization phase.
- **Options:** GitHub Container Registry (natural fit) vs. a cloud-provider-native registry.
- **Recommended direction:** GitHub Container Registry initially; revisit if DV-01 resolves toward a cloud provider with a strongly integrated native registry.
- **Evidence required:** DV-01 and DV-02 resolution.
- **Decision owner:** Infrastructure owner
- **Target phase:** Phase D of Docker adoption
- **Status:** Open

### DV-22 — Meet-Closure Checklist Ownership and Cadence

- **Question:** Who executes the meet-closure checklist in [tenant-onboarding-offboarding-and-data-portability.md, Section 6](tenant-onboarding-offboarding-and-data-portability.md#6-meet-closure-operations), and how soon after a meet ends?
- **Areas affected:** [tenant-onboarding-offboarding-and-data-portability.md, Section 6](tenant-onboarding-offboarding-and-data-portability.md#6-meet-closure-operations)
- **Why it matters:** Meet closure is a recurring, high-value operational event with no assigned owner yet.
- **Options:** Meet Director-led with ICT/DevOps support vs. a dedicated closure-coordinator role.
- **Recommended direction:** Meet Director-led with ICT/DevOps support, consistent with the Meet Director's existing Phase 0.3 authority.
- **Evidence required:** None — an organizational assignment, not a technical decision.
- **Decision owner:** Product owner
- **Target phase:** Pre-pilot
- **Status:** Open

### DV-23 — Training Format and Schedule for Committee Staff

- **Question:** What specific format and schedule delivers committee-staff training ahead of the first pilot?
- **Areas affected:** [operational-readiness-handover-and-training.md, Section 2](operational-readiness-handover-and-training.md#2-training-and-handover)
- **Why it matters:** Committee-staff usability and confidence are load-bearing for pilot success, per [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md).
- **Options:** In-person hands-on sessions vs. recorded/written self-paced material vs. a hybrid.
- **Recommended direction:** A hybrid — written/recorded material for self-paced review, supplemented by hands-on sessions ahead of the pilot specifically, given the named low-digital-literacy constraint from Phase 0.1.
- **Evidence required:** DepEd stakeholder availability, per [../04-quality/quality-open-decisions.md, QD-19](../04-quality/quality-open-decisions.md#qd-19--uat-participant-recruitment-and-scheduling).
- **Decision owner:** UAT coordinator + Product owner
- **Target phase:** Pre-pilot
- **Status:** Open

### DV-24 — `docs/05-devops/` Ongoing Ownership

- **Question:** Who keeps this documentation package current as Phase 0.9+ implementation actually begins?
- **Areas affected:** [operational-readiness-handover-and-training.md, Section 3](operational-readiness-handover-and-training.md#3-relationship-to-this-documentation-package)
- **Why it matters:** Documentation that isn't maintained becomes actively misleading rather than merely stale.
- **Options:** A rotating engineering responsibility vs. a dedicated documentation-owner role.
- **Recommended direction:** The Quality owner and Infrastructure owner jointly, consistent with their existing Phase 0.7/0.8 governance roles, updating the specific sections their area affects.
- **Evidence required:** None — an organizational assignment.
- **Decision owner:** Quality owner + Infrastructure owner
- **Target phase:** Immediate, ongoing
- **Status:** Open

---

## Summary of Blocking / High-Priority DevOps Decisions

| Decision | Why It Blocks |
|---|---|
| **DV-01** | Deployment topology/cloud-provider selection blocks DV-09, DV-10, DV-13, DV-16, and DV-21 directly — the single largest unresolved dependency chain in this package |
| **DV-17** | RPO/RTO numeric targets remain unresolved across five consecutive phases (RD-18 → PD-23 → SD-24 → DV-17) |
| **DV-19** | Vulnerability-remediation SLA policy requires a named policy owner not yet identified |
| **DV-20** | Multi-organization support mirrors the still-unresolved Phase 0.1 OD-02, entirely outside DevOps's own authority |
