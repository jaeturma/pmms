# PMMS Test Environment and Service Virtualization

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md) · [../03-security/retention-disposal-and-legal-hold-governance.md, "Test and Lower-Environment Data Governance"](../03-security/retention-disposal-and-legal-hold-governance.md#test-and-lower-environment-data-governance)

This document defines the test-environment strategy, environment isolation requirements, and service-virtualization/mocking rules. **No environment, mock server, or CI configuration is created here.**

---

## 1. Test Environment Strategy

| Environment | Purpose |
|---|---|
| Local | Individual developer workstation, fastest feedback loop |
| Automated test | Ephemeral, CI-driven, runs the fast unit/domain/feature suite |
| Integration | Real MySQL/Redis/MinIO/Reverb interaction, slower, runs less frequently |
| QA | A stable, shared environment for manual/exploratory testing |
| Staging | Production-like environment for pre-release validation |
| Pilot | The controlled-pilot-specific environment, per [pilot-operational-and-stakeholder-validation.md](pilot-operational-and-stakeholder-validation.md) |
| Performance | Dedicated environment for load/stress/soak testing, isolated from other test activity |
| Security | Dedicated environment for security/penetration testing |
| Disaster recovery | The DR environment itself, tested per [resilience-backup-recovery-and-continuity-testing.md, Section 4](resilience-backup-recovery-and-continuity-testing.md#4-disaster-recovery-testing) |

**Not every environment must exist immediately** — Local and Automated Test are needed from the first implementation commit; QA, Staging, Performance, Security, and DR are established progressively as the platform matures toward pilot and launch, per [../01-architecture/environment-and-configuration-model.md](../01-architecture/environment-and-configuration-model.md).

For each environment, the following must be defined before it's relied upon:

| Property | Question |
|---|---|
| Purpose | What is this environment specifically for? |
| Data | What data lives here, and is it synthetic (the default) or an approved masked exception? |
| Access | Who can access it, and under what governance? |
| Integrations | Which external services (if any) does it call, real or simulated? |
| Reset | How and how often is it reset to a known state? |
| Isolation | How is it isolated from other environments (credentials, network, data)? |
| Logging | What is logged here, and who can access those logs? |
| Evidence | What test evidence does this environment produce? |
| Retention | How long does data/evidence in this environment persist? |

## 2. Environment Isolation

Every environment beyond Local requires:

- Separate credentials — no environment shares a database/Redis/MinIO/Reverb credential with another.
- Separate databases — no environment reads or writes another's database.
- Separate Redis namespaces or instances — preventing cross-environment cache/queue/lock collisions.
- Separate MinIO buckets or instances — preventing cross-environment object-storage collisions.
- Separate Reverb configuration — preventing cross-environment broadcast leakage.
- Separate notification sinks — no lower environment sends a real email/SMS/push to a real recipient.
- Separate AI credentials — no lower environment uses a production AI-service credential.
- No production secrets — restated absolutely, per [../03-security/cryptography-key-and-secret-management.md, Section 3](../03-security/cryptography-key-and-secret-management.md#3-secret-management).
- No accidental real notifications — restated; email/SMS/push are suppressed or redirected to a safe test destination in every environment below Production.
- No public indexing — lower environments are not discoverable/indexable by search engines or the public.
- Restricted staging access — Staging, being production-like, follows access governance closer to Production than to QA.

## 3. Service Virtualization

Used for every external boundary PMMS depends on:

Email · SMS · push · AI · webhooks · external directories · malware scanner · timing devices · scoring devices · weather or mapping services · payment or finance integrations if later added.

Every virtualized service defines realistic:

- **Success behavior** — a normal, expected response.
- **Failure behavior** — an error response the real service could plausibly return.
- **Timeout behavior** — no response within an expected window.
- **Rate-limit behavior** — a throttling response.
- **Malformed-response behavior** — an unexpected/invalid response shape, testing PMMS's own resilience to a misbehaving dependency.

Since no external service is currently approved (per [../03-security/vendor-and-third-party-risk.md, Section 3](../03-security/vendor-and-third-party-risk.md#3-currently-approved-vendors)), every one of these is virtualized by necessity today, not merely by preference — there is no real service to call even if a test wanted to.

## 4. Mocking Rules

1. **Mock external boundaries** — the edges of PMMS's own system (third-party services, not-yet-approved integrations) are the appropriate mocking boundary.
2. **Avoid mocking domain logic** — a test that mocks away the very domain behavior it should be verifying tests nothing real.
3. **Avoid mocking everything in integration tests** — an "integration test" that mocks MySQL, Redis, and MinIO isn't testing integration at all; use real infrastructure at this level where practical.
4. **Prefer fakes for deterministic behavior** — an in-memory fake implementation of a boundary (rather than a dynamically-configured mock) often produces more readable, more reliable tests.
5. **Verify contracts** — a mock/fake's behavior is periodically checked against the real (or virtualized-realistically) boundary's actual contract, so mocks don't silently drift from reality (per [api-contract-and-integration-testing.md, Section 2](api-contract-and-integration-testing.md#2-contract-testing)).
6. **Do not let mocks hide authorization or transaction behavior** — a mocked dependency must not bypass the authorization check or transactional boundary the real dependency would participate in.
7. **Use real MySQL, Redis, and MinIO in selected integration suites where practical** — restated from rule 3, as the primary mechanism for keeping the mocking discipline honest.

## 5. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably which environments are provisioned via Docker Compose versus a shared hosted instance (a Phase 0.8+ infrastructure decision), and the specific service-virtualization tooling (e.g., a lightweight mock-server framework) selected for the boundaries in Section 3.
