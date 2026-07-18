# PMMS Secure Development Lifecycle

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [security-testing-and-assurance.md](security-testing-and-assurance.md) · [../01-architecture/testing-architecture.md](../01-architecture/testing-architecture.md) · [compliance-control-framework.md](compliance-control-framework.md)

This document defines the secure-development-lifecycle phases, secure-coding standards, vulnerability-management, and dependency/supply-chain security requirements. **No CI/CD configuration, linting rule, or code is created here.**

---

## 1. Secure Development Lifecycle Phases

| Phase | Security Activity |
|---|---|
| 1. Requirements | Security/privacy requirements are captured alongside functional requirements for every work package (Section 2 below) |
| 2. Architecture | New architecture decisions are checked against this Phase 0.6 package before implementation begins |
| 3. Threat modeling | A new significant feature receives a lightweight threat-model pass (per [threat-model.md, Section 3](threat-model.md#3-threat-modeling-method)) proportionate to its risk |
| 4. Design review | A design review confirms authorization, classification, and audit requirements are addressed before implementation starts |
| 5. Implementation | Secure-coding standards (Section 2) are followed |
| 6. Code review | Every change is peer-reviewed, with security-relevant changes (auth, authorization, crypto, file handling) receiving explicit attention |
| 7. Automated testing | Per [security-testing-and-assurance.md](security-testing-and-assurance.md) |
| 8. Security testing | Per [security-testing-and-assurance.md](security-testing-and-assurance.md) |
| 9. Privacy review | Changes touching personal data are checked against [privacy-by-design-architecture.md](privacy-by-design-architecture.md) |
| 10. Release approval | Per [security-metrics-monitoring-and-reporting.md, Section 4](security-metrics-monitoring-and-reporting.md#4-release-security-gates) |
| 11. Deployment | A future infrastructure-phase concern; this document anticipates but does not define deployment tooling |
| 12. Monitoring | Per [security-metrics-monitoring-and-reporting.md](security-metrics-monitoring-and-reporting.md) |
| 13. Incident response | Per [incident-response-and-breach-readiness.md](incident-response-and-breach-readiness.md) |
| 14. Maintenance | Ongoing dependency updates, control review, and documentation currency |
| 15. Retirement | A retired feature/component's data-disposal follows [retention-disposal-and-legal-hold-governance.md](retention-disposal-and-legal-hold-governance.md); its access is revoked, not left dormant |

## 2. Secure Coding Standards (Future Requirements, Not Code)

| Area | Standard Direction |
|---|---|
| Validation | Every input is validated server-side before use, regardless of client-side validation, per [application-api-and-client-security.md, Section 1](application-api-and-client-security.md#1-application-security) |
| Authorization | Every action checks the centralized authorization decision, never a hand-rolled check, per [authorization-and-privileged-access-assurance.md](authorization-and-privileged-access-assurance.md) |
| Transactions | High-integrity operations use database transactions with appropriate locking, per [../02-data/transaction-concurrency-and-locking.md](../02-data/transaction-concurrency-and-locking.md) |
| Output encoding | Framework-managed encoding is relied upon, never bypassed for convenience |
| File handling | Per [file-object-storage-and-malware-security.md](file-object-storage-and-malware-security.md) |
| Query construction | Parameterized queries only, never string-concatenated SQL |
| Secrets | Never hard-coded; always sourced from environment/secret-management, per [cryptography-key-and-secret-management.md, Section 3](cryptography-key-and-secret-management.md#3-secret-management) |
| Logging | Per [audit-and-security-event-architecture.md, Section 9](audit-and-security-event-architecture.md#9-logging-and-monitoring-boundaries) — no sensitive data logged |
| Errors | Minimal user-facing detail, full detail only in restricted-access logs, per [../01-architecture/observability-and-error-handling.md](../01-architecture/observability-and-error-handling.md) |
| Cryptography | Uses framework-provided, vetted primitives — no custom cryptographic implementation |
| Randomness | Cryptographically secure random-number generation for any security-relevant value (tokens, keys) — never a predictable source |
| IDs | Follows [../02-data/identifier-and-reference-strategy.md](../02-data/identifier-and-reference-strategy.md) — internal IDs never used as security boundaries |
| Queue jobs | Re-validate authorization at execution time, per [../01-architecture/event-and-queue-architecture.md, Section 2](../01-architecture/event-and-queue-architecture.md#2-job-rules) |
| Webhooks | Per [application-api-and-client-security.md, Section 3](application-api-and-client-security.md#3-webhook-security) |
| Real-time messages | Per [application-api-and-client-security.md, Section 4](application-api-and-client-security.md#4-real-time-and-reverb-security) |
| Mobile storage | Per [mobile-device-and-offline-security.md, Section 1](mobile-device-and-offline-security.md#1-flutter-security) |
| Offline sync | Per [mobile-device-and-offline-security.md, Section 3](mobile-device-and-offline-security.md#3-offline-security) |
| AI integrations | Per [ai-security-privacy-and-governance.md](ai-security-privacy-and-governance.md) |
| Dependency use | Per Section 4 below |

**No code is written to satisfy these standards in this phase** — they are documented requirements a future implementation phase's coding-standards enforcement (linting, code review, static analysis) applies.

## 3. Vulnerability Management

| Activity | Direction |
|---|---|
| Asset inventory | The bounded-context/module inventory from [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) and infrastructure components from [infrastructure-runtime-and-network-security.md](infrastructure-runtime-and-network-security.md) form the basis for vulnerability-scanning scope |
| Dependency scanning | Composer/npm/Flutter dependencies are scanned for known vulnerabilities on a continuous (CI-integrated) basis once CI/CD exists |
| Code scanning | Static-analysis security scanning is a candidate CI-integrated control — Larastan/Pint are already present for code quality; a dedicated security-focused scanner is a future addition |
| Secret scanning | Per [cryptography-key-and-secret-management.md, Section 3](cryptography-key-and-secret-management.md#3-secret-management) |
| Container scanning later | Anticipated once Docker is introduced in a later infrastructure phase |
| Infrastructure scanning later | Anticipated once infrastructure configuration exists |
| Mobile dependency scanning | Flutter/Dart package dependencies are scanned equivalently to Composer/npm |
| Vulnerability triage | Findings are triaged by severity, exploitability, and affected-component sensitivity (a Highly Restricted-data-adjacent component's vulnerability is triaged with higher urgency) |
| Severity assessment | Uses the scanning tool's native severity rating as a starting point, adjusted for PMMS-specific context by the Application/Security owner |
| Exploitability | Assessed alongside severity — a theoretical vulnerability in an unreachable code path is triaged differently than one in a public-facing endpoint |
| Patch planning | Tracked to resolution, not left open-ended |
| Compensating controls | Where a patch isn't immediately available, a documented compensating control (e.g., restricting the affected feature) is applied |
| Verification | A patched vulnerability is verified closed, not merely assumed fixed |
| Exception approval | A deliberately-deferred vulnerability requires a named approval and review date, never a silent indefinite deferral |
| Metrics | Feed [security-metrics-monitoring-and-reporting.md](security-metrics-monitoring-and-reporting.md) |
| Retesting | Periodic retesting confirms previously-fixed vulnerabilities remain fixed |

**No CI configuration is created by this document.**

## 4. Dependency and Supply-Chain Security

| Control | Direction |
|---|---|
| Composer packages | Reviewed for source trustworthiness before adoption; the current `composer.json` dependencies (Laravel, Fortify, Inertia, Wayfinder, Pest, Larastan, Pint) are all well-established, actively-maintained packages |
| npm packages | Same review discipline for `package.json` dependencies |
| Flutter packages | Same review discipline once the Flutter app is scaffolded |
| GitHub Actions | Any future workflow uses pinned, reviewed actions — never an unpinned `@main`/`@latest` reference to a third-party action |
| Docker images later | Anticipated once Docker is introduced — official/verified base images only |
| OS packages | A future infrastructure-phase concern |
| AI tools | Any AI-service SDK/client library follows the same dependency-review discipline |
| Third-party SDKs | Any future SDK (payment, notification-delivery, etc.) is reviewed before adoption |
| MinIO clients | The S3-compatible client library (already present via Laravel's `s3` filesystem driver) follows standard dependency hygiene |
| Redis clients | The `phpredis`/`predis` client (per `.env.example`'s `REDIS_CLIENT=phpredis`) follows standard dependency hygiene |

### Requirements

- **Trusted sources** — packages are sourced from official registries (Packagist, npm, pub.dev), never an unverified fork or mirror.
- **Version pinning strategy** — lock files (`composer.lock`, `package-lock.json`/equivalent) are committed and respected, ensuring reproducible installs.
- **Lock files** — restated as required, not optional.
- **Review** — a new dependency addition is reviewed for necessity, maintenance activity, and security history before adoption.
- **Update process** — dependencies are updated on a regular cadence, not left stale indefinitely.
- **Vulnerability alerts** — a dependency-vulnerability alerting mechanism (e.g., GitHub Dependabot-equivalent) is a candidate control once CI/CD exists.
- **License review readiness** — dependency licenses are reviewed for compatibility with PMMS's own licensing, a candidate future process.
- **Removal of abandoned packages** — a dependency that becomes unmaintained is a candidate for replacement, tracked rather than silently accepted indefinitely.
- **Reproducible builds** — the build process produces consistent output from the same source and lock-file state.
- **Artifact integrity** — deployment artifacts are verifiable as originating from the reviewed source, once a deployment pipeline exists.
- **Dependency inventory** — the current `composer.json`/`package.json` dependency lists constitute the inventory; kept current as dependencies change.

## 5. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably which static-analysis security scanner (beyond the existing Larastan/Pint code-quality tooling) is adopted, and CI/CD introduction timing (a prerequisite for several controls in this document).
