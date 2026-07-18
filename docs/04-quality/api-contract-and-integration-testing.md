# PMMS API, Contract, and Integration Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md) · [../03-security/application-api-and-client-security.md](../03-security/application-api-and-client-security.md) · [../01-architecture/internal-integration-architecture.md](../01-architecture/internal-integration-architecture.md)

This document defines API, contract, and integration-level testing requirements. **No test code or integration client is created here.**

---

## 1. API Testing

| Target | What to Verify |
|---|---|
| Authentication | Every API category (mobile, device, public, admin integration, webhook, sync) authenticates correctly per [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md) |
| Authorization | Restated from [security-privacy-audit-and-compliance-assurance.md](security-privacy-audit-and-compliance-assurance.md) |
| Object-level access | A request for a specific object ID the requester is not authorized for is denied, regardless of the object existing (the OWASP "BOLA" risk) |
| Function-level access | A request for an action the requester's role doesn't permit is denied, regardless of authentication succeeding (the OWASP "BFLA" risk) |
| Validation | Malformed/invalid request bodies are correctly rejected with a structured error |
| Pagination | List endpoints correctly paginate and never return an unbounded result set |
| Filtering | Query filters correctly constrain results without leaking unauthorized data through filter side channels |
| Rate limits | Requests beyond the configured rate limit are correctly throttled |
| Idempotency | A request carrying an idempotency key does not duplicate its effect on retry |
| Replay protection | A replayed/stale signed request is correctly rejected |
| Correlation IDs | Every request/response pair carries a traceable correlation ID |
| Error contracts | Error responses follow a consistent, documented shape |
| Version compatibility | A versioned API correctly serves its declared version's contract |
| Deprecation | A deprecated endpoint correctly signals its deprecation status |
| Request limits | Oversized request bodies/payloads are rejected |
| Sensitive-data exposure | No Restricted/Highly Restricted field appears in a response the requester isn't authorized to see, and no sensitive value ever appears in a URL |
| Cross-tenant isolation | If multi-organization support is ever adopted, no response leaks data across organizational boundaries |
| Cross-meet isolation | A request scoped to Meet A never returns Meet B's data |

## 2. Contract Testing

Contracts are defined and tested for every system boundary in [../01-architecture/api-and-client-boundaries.md](../01-architecture/api-and-client-boundaries.md) and [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md):

| Boundary | Contract Scope |
|---|---|
| Mobile API | Flutter client ↔ server request/response schemas |
| Device API | Scanner/encoder device ↔ server schemas |
| Public API | External public-facing read schemas |
| External integrations | Currently none approved — contract testing applies once any is approved |
| Webhooks | Currently none approved — signature, payload schema, and idempotency contract applies once any is approved, per [../03-security/application-api-and-client-security.md, Section 3](../03-security/application-api-and-client-security.md#3-webhook-security) |
| Notification providers | Email/SMS/push delivery-adapter contracts, once a provider is approved per [../03-security/vendor-and-third-party-risk.md](../03-security/vendor-and-third-party-risk.md) |
| AI providers | Request/response contract for any approved AI service, bounded by [../03-security/ai-security-privacy-and-governance.md](../03-security/ai-security-privacy-and-governance.md) |
| Reverb payloads | Broadcast event schema per channel type |
| Import templates | Per [../02-data/import-export-and-data-exchange.md, Section 1](../02-data/import-export-and-data-exchange.md#1-import-architecture) |
| Export formats | Per [../02-data/import-export-and-data-exchange.md, Section 2](../02-data/import-export-and-data-exchange.md#2-export-architecture) |

### Contract Test Dimensions

- **Schema compatibility** — the contract's declared shape matches actual request/response payloads.
- **Required fields** — missing required fields are correctly rejected.
- **Optional fields** — absent optional fields don't break processing.
- **Version changes** — a contract version change is detected and its compatibility impact assessed.
- **Unknown fields** — an unexpected additional field is handled per the contract's declared strictness (ignored vs. rejected).
- **Error behavior** — contract violations produce the documented error shape.
- **Backward compatibility** — a new contract version doesn't silently break existing consumers without a deliberate deprecation process.
- **Idempotency** — restated where the contract involves a retryable operation.
- **Security expectations** — the contract's authentication/authorization requirements are enforced, not merely documented.

## 3. Integration Testing

Real (not mocked) interaction is tested for:

| Component | What to Verify |
|---|---|
| MySQL | Per [data-database-migration-and-quality-testing.md](data-database-migration-and-quality-testing.md) |
| Redis | Per [queue-realtime-cache-and-storage-testing.md](queue-realtime-cache-and-storage-testing.md) |
| Horizon | Per [queue-realtime-cache-and-storage-testing.md](queue-realtime-cache-and-storage-testing.md) |
| Reverb | Per [queue-realtime-cache-and-storage-testing.md](queue-realtime-cache-and-storage-testing.md) |
| MinIO | Per [queue-realtime-cache-and-storage-testing.md](queue-realtime-cache-and-storage-testing.md) |
| Email provider adapter | Simulated (per [test-environment-and-service-virtualization.md](test-environment-and-service-virtualization.md)) success/failure/timeout behavior — no real email sent in any lower environment |
| SMS provider adapter | Same simulation discipline as email |
| Push provider adapter | Same simulation discipline |
| AI adapter | Simulated request/response, bounded by [../03-security/ai-security-privacy-and-governance.md](../03-security/ai-security-privacy-and-governance.md) — no real AI-provider data exposure in test environments beyond what's explicitly approved |
| External references | Any future external system reference (e.g., a DepEd registry lookup) is contract-tested against a simulated boundary, never live |
| Webhooks | Per Section 2 |
| Search adapters | If/when a dedicated search engine is introduced (per [../02-data/public-reporting-and-projection-data.md, Section 3](../02-data/public-reporting-and-projection-data.md#3-search-indexes)), its integration is tested |
| File scanning adapter | Per [queue-realtime-cache-and-storage-testing.md, Section 6](queue-realtime-cache-and-storage-testing.md#6-malware-flow-testing) |
| Backup and restore tooling | Later, per [resilience-backup-recovery-and-continuity-testing.md](resilience-backup-recovery-and-continuity-testing.md) |

## 4. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably which integration tests run against real infrastructure (Docker-composed MySQL/Redis/MinIO) versus an in-memory/simulated equivalent, a decision dependent on the still-undecided CI/CD and infrastructure-phase tooling.
