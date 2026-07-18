# PMMS Meet-Day, Venue, and Offline Operations

**Status:** Draft Complete — Pending DevOps, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/offline-sync-runtime-architecture.md](../01-architecture/offline-sync-runtime-architecture.md) · [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md) · [device-service-account-and-credential-operations.md](device-service-account-and-credential-operations.md)

This document defines the operational model for active competition days — the period when PMMS's reliability matters most acutely and differently from ordinary office-application operation. **No exact schedule is invented** — restated per the phase's own working instruction.

---

## 1. Meet-Day Operations

| Element | Direction |
|---|---|
| Command center | A designated coordination point (physical or virtual) for the ICT/operations team during the meet |
| Duty roster | Named shift coverage for the command center and key operational roles — roles, not specific individuals, defined here |
| System health checks | A pre-meet-day checklist confirming MySQL/Redis/MinIO/Reverb/Horizon/queue health, per [observability-logging-metrics-tracing-and-alerting.md, Section 1](observability-logging-metrics-tracing-and-alerting.md#1-health-checks) |
| Venue readiness | Per Section 2 below |
| Network readiness | Confirmed venue connectivity, with the offline fallback (Section 3) understood as the accepted mitigation, not a failure state |
| Scanner readiness | Per [device-service-account-and-credential-operations.md](device-service-account-and-credential-operations.md) |
| Score station readiness | Devices/terminals confirmed operational and correctly assigned before competition begins |
| Reverb readiness | Confirmed capacity for expected live-scoring and public-scoreboard connection volume |
| Queue readiness | Confirmed no pre-existing backlog before the day's high-volume period begins |
| Public portal readiness | Confirmed availability and, where the topology supports it, isolated capacity ahead of anticipated public traffic |
| Backup verification | The most recent backup's success is confirmed before the day begins — restated absolutely, never assumed |
| Incident channel | A dedicated, known communication channel for the day's operational issues |
| Escalation | Per [incident-problem-change-and-release-management.md, Section 1](incident-problem-change-and-release-management.md#1-incident-management) |
| Change freeze | Restated absolutely from working rule 45 — meet-day production changes are minimized and governed; no discretionary deployment during active competition |
| Emergency change process | The narrow exception to the change freeze — a genuinely urgent fix follows the emergency-change discipline in [incident-problem-change-and-release-management.md, Section 5](incident-problem-change-and-release-management.md#5-change-management), never an ungated shortcut |
| Hourly or milestone review | A periodic check-in during the day, cadence not fixed here |
| End-of-day reconciliation | Confirming the day's data (scores, results, access scans) is complete and consistent before closing out |
| Daily backup | Confirmed to have run and succeeded at day's end |
| Daily operational report | A summary of the day's operational health, issues, and resolutions |

**No exact schedule or cadence is invented** — restated per the phase's own working instruction; the elements above are a checklist structure, not a fixed timetable.

## 2. Venue Operations

| Element | Direction |
|---|---|
| Local ICT lead | A designated on-site technical point of contact per venue |
| Device inventory | A confirmed, current list of devices assigned to this venue |
| Network map | The venue's actual connectivity layout, known before the meet begins |
| Power backup readiness | Confirmed availability of backup power for critical venue equipment — an operational, not software, concern acknowledged here as a dependency |
| Scanner stations | Positioned and tested at gates/meal lines/venue entry points |
| Score stations | Positioned and tested at competition areas |
| Printer readiness | Where printed artifacts (schedules, credentials) are needed on-site |
| Local server if used | Per the Hybrid Venue Topology, [deployment-topology-and-runtime-units.md, Section 1](deployment-topology-and-runtime-units.md#1-deployment-topology) |
| Offline mode | Per Section 3 below |
| Synchronization | Confirmed working before relying on it during live operation |
| Incident logging | Venue-level issues are logged and escalated per [incident-problem-change-and-release-management.md](incident-problem-change-and-release-management.md) |
| Spare devices | A reserve pool for on-the-spot replacement, per [device-service-account-and-credential-operations.md](device-service-account-and-credential-operations.md) |
| Replacement process | A defined, quick procedure for swapping a failed device mid-competition |
| End-of-day reconciliation | Venue-level data confirmed complete and synced before the venue's operations close for the day |

## 3. Offline Venue Operations

| Element | Direction |
|---|---|
| Local authority snapshot | The bounded, expiring cached-authorization snapshot per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) |
| Device assignments | Confirmed current before relying on offline operation |
| Reference-data freshness | Sports catalog, schedules, and other reference data are synced recently enough to be trustworthy offline |
| Local credentials | Cached credential-validity data, per [../03-security/mobile-device-and-offline-security.md, Section 3](../03-security/mobile-device-and-offline-security.md#3-offline-security) |
| Operation limits | Restated absolutely — eligibility approval, result certification, protest resolution, medal tally certification, meet closure, privilege grants, permanent credential revocation, and high-risk overrides never finalize offline |
| Sync windows | Planned connectivity-return points where accumulated offline data uploads |
| Conflict escalation | Per [../02-data/offline-sync-and-conflict-data-model.md, Section 3](../02-data/offline-sync-and-conflict-data-model.md#3-conflict-resolution-data) — high-integrity conflicts route to human review |
| Lost device | Per [../03-security/mobile-device-and-offline-security.md, Section 2](../03-security/mobile-device-and-offline-security.md#2-device-and-scanner-security) |
| Local backup | Where a local venue server is used, its own local data is protected pending sync, per [device-service-account-and-credential-operations.md](device-service-account-and-credential-operations.md) |
| Security | Physical and credential security for offline-operating devices, restated from Phase 0.6 |
| Reconciliation after connectivity returns | Confirmed complete and correct before the offline period is considered closed |
| Manual fallback | Per [backup-restore-disaster-recovery-and-continuity.md, Section 5](backup-restore-disaster-recovery-and-continuity.md#5-business-continuity-and-resilience-operations) — the ultimate fallback if even offline-device operation isn't viable |

## 4. Open Questions

See [devops-open-decisions.md](devops-open-decisions.md) — notably the specific command-center staffing model and whether a local venue server is provisioned for the first pilot (mirrors [deployment-topology-and-runtime-units.md, Section 5](deployment-topology-and-runtime-units.md#5-open-questions)).
