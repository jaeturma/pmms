# PMMS Configuration, Feature-Flag, and Secret Management

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../03-security/cryptography-key-and-secret-management.md, Section 3](../03-security/cryptography-key-and-secret-management.md#3-secret-management) · [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md)

This document defines configuration-management categories/rules, secret-management operations, and feature-flag architecture. **No `.env` file with values, secret, or configuration schema is created here**, per working rules 15 and 39.

---

## 1. Configuration Categories

Application · database · Redis · cache · queue · Horizon · Reverb · MinIO · session · mail · SMS · push · AI · logging · monitoring · feature flags · security · mobile API · public portal · integrations.

Each maps directly to an already-established `config/*.php` file (application → `config/app.php`, database → `config/database.php`, session → `config/session.php`, etc.) or a future one (Horizon/Reverb/MinIO configuration, once those packages are installed) — this document does not invent a new configuration surface, it governs the operational discipline around the existing and anticipated one.

## 2. Configuration Rules

1. **Safe defaults** — every configuration value has a default that fails toward the safer, more restrictive behavior, never toward permissive/insecure.
2. **Explicit environment overrides** — a value that differs by environment is overridden explicitly per environment, never inferred.
3. **Schema or validation readiness** — a future capability (validating required configuration is present and well-formed at boot) is anticipated, not yet implemented.
4. **No client exposure of secrets** — restated absolutely from [../03-security/application-api-and-client-security.md, Section 5](../03-security/application-api-and-client-security.md#5-react-and-inertia-security); no configuration value containing a secret ever reaches an Inertia prop or API response.
5. **Configuration changes reviewed** — a configuration change (not just a code change) goes through the same review discipline as application code, per [incident-problem-change-and-release-management.md, Section 5](incident-problem-change-and-release-management.md#5-change-management).
6. **Sensitive configuration audited where appropriate** — a change to a security-relevant configuration value (session lifetime, CORS origins, rate limits) is audit-relevant.
7. **Configuration drift detection readiness** — a future capability (detecting when a running environment's actual configuration diverges from its intended, version-controlled state) is anticipated.
8. **Startup failure for missing critical configuration** — the application fails fast and loudly if a required configuration value is absent, rather than starting in a silently-misconfigured state.
9. **Documentation for every required variable** — every environment variable a deployment must set is documented, extending `.env.example`'s existing role as a documented-but-valueless template.

## 3. Secret Management

Extends [../03-security/cryptography-key-and-secret-management.md, Section 3](../03-security/cryptography-key-and-secret-management.md#3-secret-management) with operational lifecycle detail:

| Activity | Direction |
|---|---|
| Secret inventory | The thirteen categories already named in Phase 0.6 (application key, database/Redis/MinIO/Reverb/mail/SMS/push/AI credentials, webhook secrets, device credentials, API client secrets, encryption keys) |
| Environment separation | Restated absolutely — no secret crosses an environment boundary |
| Ownership | Every secret category has a named owning role (Security owner or Infrastructure owner) |
| Generation | Cryptographically secure generation, never a predictable or manually-chosen value |
| Storage | Never in source control, never in this documentation, never in a chat/ticket — restated per working rule 39 |
| Injection | Delivered to the running application via environment variables or a future secret-management platform, never hard-coded |
| Rotation | Every secret category has a rotation capability |
| Revocation | A suspected-compromised secret is immediately revocable |
| Expiry | Secrets are not permanent, unexpiring values by default |
| Backup | Secrets are backed up separately from the data they protect, with tightly restricted access to that backup |
| Recovery | A documented recovery process exists that does not depend on a single point of failure |
| Access logging | Access to a secret-management system (once one exists) is itself audit-relevant |
| Emergency rotation | A defined, practiced procedure for rotating a secret under incident conditions, not improvised |
| Repository secret scanning | A candidate CI-integrated control, per [ci-cd-and-release-pipeline-architecture.md](ci-cd-and-release-pipeline-architecture.md) |

### Candidate Future Mechanisms (None Selected)

Managed secret store (e.g., a cloud-native secrets manager or Vault-style tool) · encrypted deployment secrets (e.g., GitHub Actions encrypted secrets, once CI exists) · protected CI secrets · local developer secret handling (e.g., a per-developer `.env` never committed, already the confirmed `.gitignore` behavior). **No specific platform is selected in this phase.**

## 4. Feature-Flag Architecture

### Uses

Controlled rollout · pilot-only features · organization-specific features · meet-specific features · emergency disablement · AI-feature disablement (per [../03-security/ai-security-privacy-and-governance.md, Section 2](../03-security/ai-security-privacy-and-governance.md#2-ai-privacy-and-governance), "every AI feature can be disabled platform-wide or per-context") · mobile compatibility (gating a feature until a minimum app version is broadly deployed) · new public functionality · risky integration rollout.

### Rules

1. **Flags must have owners** — no ownerless, indefinitely-lingering flag.
2. **Flags must have expiry or review dates** — a flag is a temporary control mechanism, not a permanent configuration substitute.
3. **Flags cannot bypass authorization** — restated absolutely; a feature flag controls whether a capability exists, never whether an authorization check runs.
4. **Flags cannot permanently replace configuration** — restated from rule 2; a flag that never gets removed and never gets promoted to ordinary configuration is quality debt, per [../04-quality/defect-triage-root-cause-and-quality-debt.md, Section 6](../04-quality/defect-triage-root-cause-and-quality-debt.md#6-quality-debt).
5. **Sensitive flags require audit** — a flag controlling a high-integrity or security-relevant capability is itself an audit-relevant configuration.
6. **Old flags must be removed** — dead flag code is removed once a rollout completes, not left indefinitely as branching complexity.
7. **Flag states must be environment-specific** — a flag enabled in Staging is not assumed enabled in Production.
8. **Public and administrative flags may differ** — a feature can be flagged independently for public-facing versus administrative surfaces.

**No feature-flag platform/package is selected in this phase.**

## 5. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably feature-flag platform/package selection and secret-management-platform selection, both Phase 0.9+ implementation decisions.
