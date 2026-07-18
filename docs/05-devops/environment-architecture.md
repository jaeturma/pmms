# PMMS Environment Architecture

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md) · [../04-quality/test-environment-and-service-virtualization.md](../04-quality/test-environment-and-service-virtualization.md) · [local-development-environment.md](local-development-environment.md)

This document extends [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md) (Phase 0.4) and [../04-quality/test-environment-and-service-virtualization.md](../04-quality/test-environment-and-service-virtualization.md) (Phase 0.7) with DevOps-operational detail: per-environment infrastructure fidelity, isolation, and parity. **No environment is provisioned by this document.**

---

## 1. Environment Strategy

| Environment | Purpose |
|---|---|
| Local | Individual developer workstation |
| Automated Test | Ephemeral, CI-driven fast-suite execution |
| Shared Development | A stable, shared integration point for in-progress work |
| Integration | Real MySQL/Redis/MinIO/Reverb interaction testing |
| QA | Manual/exploratory testing |
| Staging | Production-like pre-release validation |
| Pilot | The controlled-pilot-specific environment |
| Production | The live, operational platform |
| Performance | Dedicated load/stress/soak testing |
| Security | Dedicated security/penetration testing |
| Disaster Recovery | The DR environment itself |

**Not all environments must exist immediately** — restated from Phase 0.7; Local and Automated Test are needed from the first implementation commit, while QA, Staging, Pilot, Performance, Security, and Disaster Recovery are established progressively as the platform matures toward pilot and launch.

### Per-Environment Properties

For every environment above, the following are defined before it is relied upon:

| Property | Question |
|---|---|
| Purpose | What is this environment specifically for? |
| Intended users | Who accesses it? |
| Data classification | What is the highest classification tier of data present, and is it synthetic (the default) or an approved masked exception? |
| Access restrictions | Who can access it, under what governance? |
| Infrastructure fidelity | How closely does it mirror Production's actual infrastructure? |
| External integrations | Real, simulated, or absent, per [../04-quality/test-environment-and-service-virtualization.md, Section 3](../04-quality/test-environment-and-service-virtualization.md#3-service-virtualization) |
| Notification behavior | Suppressed/redirected (every environment below Production) or live (Production only) |
| AI behavior | Bounded by [../03-security/ai-security-privacy-and-governance.md](../03-security/ai-security-privacy-and-governance.md) regardless of environment |
| Queue behavior | Sync, in-memory, or real Horizon-supervised workers |
| Reverb behavior | Real, simulated, or disabled |
| Storage behavior | Local disk, a dedicated test bucket, or the real MinIO deployment |
| Reset policy | How and how often the environment returns to a known state |
| Backup expectations | Whether and how this environment is backed up (most lower environments are not) |
| Monitoring expectations | What observability exists here, per [observability-logging-metrics-tracing-and-alerting.md](observability-logging-metrics-tracing-and-alerting.md) |
| Retention | How long environment-specific data/evidence persists |
| Approval requirements | What governance gates access or promotion into this environment |
| Public exposure | Whether this environment is reachable from the public internet (Production and, cautiously, Pilot only) |
| Cost considerations | Relative infrastructure cost, informing provisioning priority |

## 2. Environment Isolation

Every environment beyond Local requires:

Separate credentials · separate databases · separate Redis instances or securely isolated namespaces · separate MinIO buckets or instances · separate queue prefixes · separate Reverb credentials · separate encryption keys · separate mobile API configuration · separate external service credentials · separate notification sinks · separate domains or subdomains · separate monitoring labels.

**Absolute rules:** no production secrets in lower environments · no accidental real notifications · no public indexing of lower environments · no production personal data without approved masking, per [../03-security/retention-disposal-and-legal-hold-governance.md, "Test and Lower-Environment Data Governance"](../03-security/retention-disposal-and-legal-hold-governance.md#test-and-lower-environment-data-governance) and [../04-quality/test-data-fixture-and-scenario-strategy.md, Section 7](../04-quality/test-data-fixture-and-scenario-strategy.md#7-privacy-safe-lower-environment-data).

## 3. Environment Parity

Parity goals (not identical capacity, but identical *behavior*) for: PHP · Laravel · Node.js · React · Flutter · MySQL · Redis · MinIO · Reverb · Horizon · queue configuration · file storage · scheduled jobs · time zones · extensions · build tooling.

**Parity does not require identical capacity** — restated explicitly; a Local environment's MySQL instance need not match Production's provisioned resources, but it must behave the same way (same version, same relevant configuration flags, same extensions) so that a passing test in a lower environment is meaningful evidence about Production behavior.

## 4. Environment Data Rules

| Environment | Default Data |
|---|---|
| Local, Automated Test, Shared Development, Integration, QA | Synthetic only, per [../04-quality/test-data-fixture-and-scenario-strategy.md](../04-quality/test-data-fixture-and-scenario-strategy.md) — no exception |
| Staging | Synthetic by default; a masked-production-data exception follows the formal approval process in [../03-security/retention-disposal-and-legal-hold-governance.md](../03-security/retention-disposal-and-legal-hold-governance.md), never casually copied |
| Pilot | Real data from the pilot's own limited, consented scope — governed with the same rigor as Production for the categories it touches |
| Performance, Security | Synthetic, large-volume/adversarial datasets specifically constructed for their purpose |
| Production | The authoritative, real dataset |
| Disaster Recovery | A replicated copy of Production data, governed identically to Production |

**Production data is never copied casually into lower environments** — restated absolutely per working rule 40.

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably which environments are provisioned before the first pilot versus deferred until post-pilot, and whether Shared Development and Integration are merged into one environment initially given team size.
