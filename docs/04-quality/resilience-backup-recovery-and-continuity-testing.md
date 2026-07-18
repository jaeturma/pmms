# PMMS Resilience, Backup, Recovery, and Continuity Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../02-data/backup-restore-and-data-recovery.md](../02-data/backup-restore-and-data-recovery.md) · [../01-architecture/resilience-performance-and-scaling.md](../01-architecture/resilience-performance-and-scaling.md) · [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md)

This document defines resilience, failure-injection, backup/restore, disaster-recovery, and business-continuity testing. **No destructive test is ever performed against production or real data**, per working rule 16.

---

## 1. Resilience Testing

| Scenario | What to Verify |
|---|---|
| MySQL interruption | The application fails safely (denies/queues rather than corrupts) when MySQL is unreachable |
| Redis interruption | Per [queue-realtime-cache-and-storage-testing.md, Section 3](queue-realtime-cache-and-storage-testing.md#3-redis-testing) — no authoritative-state corruption |
| Reverb interruption | Clients fall back to normal polling/query, per [../01-architecture/realtime-architecture.md](../01-architecture/realtime-architecture.md) |
| MinIO interruption | Uploads/downloads fail safely; no metadata/object inconsistency results |
| Queue-worker failure | In-flight jobs are recoverable, not silently lost |
| Horizon restart | Restated from [queue-realtime-cache-and-storage-testing.md, Section 1](queue-realtime-cache-and-storage-testing.md#1-queue-and-horizon-testing) |
| Email provider outage | Notification delivery queues/retries rather than failing the triggering business action |
| SMS provider outage | Same discipline |
| Push provider outage | Same discipline |
| AI provider outage | The affected AI-assisted feature disables gracefully; its ordinary non-AI workflow path remains fully functional, per [../03-security/ai-security-privacy-and-governance.md, Section 2](../03-security/ai-security-privacy-and-governance.md#2-ai-privacy-and-governance) |
| Malware scanner outage | Uploads correctly queue in quarantine rather than bypassing scanning |
| Slow external service | A slow (not down) dependency doesn't cascade into an application-wide timeout/failure |
| Partial network loss | The application handles a degraded, not fully severed, network connection gracefully |
| Venue connectivity loss | Offline-capable workflows (scoring, access validation) continue functioning locally, per [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md) |
| Client reconnect | A reconnecting client correctly resumes normal operation without manual intervention |
| Scheduler failure | A missed scheduled job is detected and doesn't silently skip a critical periodic task |

## 2. Failure-Injection Testing

Controlled failure scenarios, tested only against non-production environments:

Timeout · connection reset · duplicate message · lost message · delayed message · corrupt file · stale cache · missing object · deadlock · lock timeout · disk pressure · queue backlog · revoked credential · expired token · clock drift.

Each scenario is deliberately, controllably induced (e.g., a simulated service boundary configured to time out, per [test-environment-and-service-virtualization.md](test-environment-and-service-virtualization.md)) to confirm the system's documented failure-handling behavior actually holds. **Do not perform destructive failure injection against production** — restated absolutely; every failure-injection scenario runs in a dedicated, isolated environment.

## 3. Backup and Restore Testing

| Target | What to Verify |
|---|---|
| Backup completion | Scheduled backups complete successfully and are logged |
| Backup integrity | A backup, once taken, is verified restorable (not merely "the job exited 0") |
| Backup encryption | Backups are encrypted at rest, per [../03-security/cryptography-key-and-secret-management.md, Section 1](../03-security/cryptography-key-and-secret-management.md#1-cryptographic-architecture) |
| Restore | A restore operation correctly reconstructs the backed-up state |
| Point-in-time recovery readiness | Where point-in-time recovery is eventually adopted (per [../02-data/backup-restore-and-data-recovery.md, Section 2](../02-data/backup-restore-and-data-recovery.md#2-backup-requirements)), its recovery-point accuracy is verified |
| MySQL and MinIO consistency | A joint restore of database and object storage produces internally consistent state, per the lock-step backup principle |
| Audit continuity | Restored audit data remains complete and correctly ordered |
| Public projection rebuild | Public projections correctly rebuild from restored authoritative data |
| Search rebuild | If a search index exists, it correctly rebuilds from restored data |
| Queue replay | In-flight queued work at backup time is correctly handled post-restore (replayed, safely dropped, or flagged, per its idempotency design) |
| Sync reconciliation | Devices reconnecting after a restore correctly reconcile without duplicating or losing data |
| Credential revocation state | Restored data correctly reflects the actual, current revocation state, not a stale pre-incident state |
| Official result state | Restored Official Results and Medal Tally reflect the correct, most-recent certified state |
| Recovery documentation | The restore process itself is documented clearly enough to be executed under pressure, and that documentation is validated during the test, not merely written and assumed correct |

## 4. Disaster-Recovery Testing

| Activity | What to Verify |
|---|---|
| Recovery exercise | A scheduled DR exercise (not just a backup restore) confirms end-to-end recovery capability |
| Decision roles | The roles named in [../03-security/incident-response-and-breach-readiness.md, Section 3](../03-security/incident-response-and-breach-readiness.md#3-roles-and-escalation) correctly execute their responsibilities during the exercise |
| Access controls | DR-environment access follows the same governance as production access, per [../03-security/access-review-production-and-support-controls.md](../03-security/access-review-production-and-support-controls.md) |
| Key recovery | Encryption keys are recoverable per [../03-security/cryptography-key-and-secret-management.md, Section 2](../03-security/cryptography-key-and-secret-management.md#2-key-management) |
| Secret recovery | Application secrets are recoverable without being exposed in the process |
| Infrastructure restoration | The DR environment can be stood up from documented infrastructure state |
| Data restoration | Per Section 3 |
| Projection rebuild | Per Section 3 |
| Device and token review | Post-DR-event, device/token validity is reviewed before being trusted as current |
| Public communication | A DR event's public-facing communication (if needed) follows the incident-response communication process |
| Security validation | The recovered environment is confirmed to have the same security posture as the environment it replaced |
| Business validation | Domain owners confirm the recovered system's business-critical data is correct |
| Evidence | The exercise produces documented evidence of what worked and what didn't |
| Lessons learned | Findings feed back into [../03-security/incident-response-and-breach-readiness.md](../03-security/incident-response-and-breach-readiness.md) and [defect-triage-root-cause-and-quality-debt.md](defect-triage-root-cause-and-quality-debt.md) |

## 5. Business-Continuity Testing

| Scenario | What to Verify |
|---|---|
| Manual fallback | A documented manual fallback procedure (e.g., paper score sheets) is validated as actually workable, not merely written |
| Read-only mode | If a read-only degraded mode is implemented, it correctly serves already-published data without accepting writes |
| Offline venue operation | Restated from [resilience testing, "Venue connectivity loss"](#1-resilience-testing) |
| Printed schedules | Printable schedule/roster artifacts (a named constraint per [../00-product/assumptions-constraints-risks.md](../00-product/assumptions-constraints-risks.md)) generate correctly |
| Manual score capture fallback | The paper-to-system reconciliation process (entering manually-captured scores after connectivity returns) is validated |
| Delayed publication | A delayed (not lost) publication correctly catches up once normal operation resumes |
| Emergency communication | Emergency communication channels function independently of the primary system where required |
| Credential validation fallback | A manual/offline credential-validation fallback (e.g., a printed roster) is validated for accuracy against system state |
| Reconciliation after recovery | Data entered during a fallback period correctly reconciles into the system once restored |
| Data-entry backlog | A backlog of fallback-period data doesn't overwhelm normal operation when re-entered |
| Committee continuity | A committee can continue essential functions during a partial system outage |

## 6. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — notably DR-exercise cadence and whether a dedicated DR environment exists before or only after the first pilot meet (mirrors [../02-data/backup-restore-and-data-recovery.md, Section 6](../02-data/backup-restore-and-data-recovery.md#6-open-questions)).
