# PMMS Test Automation, CI, and Quality Gates

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../03-security/security-metrics-monitoring-and-reporting.md, "Release Security Gates"](../03-security/security-metrics-monitoring-and-reporting.md#4-release-security-gates) · [../03-security/secure-development-lifecycle.md](../03-security/secure-development-lifecycle.md)

This document defines the test-automation suite architecture and candidate CI quality gates. **No pipeline, GitHub Actions workflow, or CI configuration is created here**, per working rule 12.

---

## 1. Test Automation Architecture

| Suite | Purpose | Runs |
|---|---|---|
| Fast local suite | Immediate developer feedback (unit, domain) | On every local save/run, before commit |
| Pull-request suite | Feature/application/authorization tests relevant to the change | On every pull request |
| Full regression suite | The complete automated test set | Before merge to main / on a schedule |
| Integration suite | Real MySQL/Redis/MinIO/Reverb interaction | Less frequently than the PR suite, given its cost |
| Security suite | Authorization, authentication, security-event tests | On every pull request touching a security-relevant area, and on a schedule |
| Performance suite | Load/stress/soak scenarios | On a schedule, or before a major release |
| Mobile suite | Flutter widget/integration tests | On every pull request touching `mobile/`, once it exists |
| Nightly suite | The full regression suite plus longer-running integration/performance checks | Nightly |
| Release-candidate suite | Every automated suite, plus release-gate checks | Before every release candidate |
| Pilot-readiness suite | A focused subset validating pilot-critical workflows | Before pilot entry, per [pilot-operational-and-stakeholder-validation.md](pilot-operational-and-stakeholder-validation.md) |

**No pipeline is created here** — this defines what a future CI/CD implementation (a Phase 0.8+ or later infrastructure-phase decision) must satisfy.

## 2. CI Quality Gates (Candidates)

| Gate | Purpose |
|---|---|
| Formatting | Pint (PHP) / Prettier (TypeScript) compliance, per the existing `composer.json`/`package.json` tooling |
| Static analysis | Larastan (PHP) / TypeScript compiler checks, per existing tooling |
| Unit tests | Pest unit-suite pass |
| Domain tests | Domain-layer test pass |
| Feature tests | Pest feature-suite pass |
| Authorization tests | Positive/negative authorization coverage pass |
| Architecture tests | Dependency-direction and module-boundary rules (e.g., Infrastructure never depending on Delivery) hold, per [../01-architecture/laravel-architecture.md](../01-architecture/laravel-architecture.md) |
| Frontend checks | ESLint / TypeScript checks, per existing `package.json` tooling |
| Flutter checks | `flutter analyze` and widget-test pass, once `mobile/` exists |
| Dependency review | No newly-introduced critical/high dependency vulnerability |
| Secret scanning | No secret detected in the diff |
| Coverage reporting | Coverage is reported (not gated on a single blocking percentage — restated from working rule 28) |
| Migration review later | Once physical schema exists, migration safety/reversibility is checked |
| Build verification | The application builds successfully |
| Artifact generation | Build artifacts are produced and available for the release-candidate suite |

**Exact gates and thresholds are open decisions** — restated per the phase's working instructions; this table names candidate gates, not a finalized, enforced pipeline configuration.

## 3. Relationship to Phase 0.6's Secure Development Lifecycle

This document operationalizes, and does not redefine, the fifteen SDLC phases and release-security gates already established in [../03-security/secure-development-lifecycle.md](../03-security/secure-development-lifecycle.md) and [../03-security/security-metrics-monitoring-and-reporting.md, Section 4](../03-security/security-metrics-monitoring-and-reporting.md#4-release-security-gates) — the quality-engineering gates in Section 2 above run alongside, not instead of, the security-specific gates from Phase 0.6.

## 4. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably CI platform selection (GitHub Actions is the natural fit given the confirmed GitHub direction, but not committed here since no CI configuration is created in this phase) and specific coverage-reporting thresholds per risk tier.
