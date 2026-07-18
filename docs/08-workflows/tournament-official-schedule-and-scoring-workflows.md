# PMMS Tournament, Technical Official, Schedule, and Scoring Workflows

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [../01-architecture/workflow-and-command-catalog.md](../01-architecture/workflow-and-command-catalog.md) (WF-07, WF-08, WF-09, WF-10, WF-11)

This document adds the event, notification, state-machine, and automation layer to the existing Phase 0.2 workflow definitions — it does not redefine WF-07 through WF-11's steps, actors, or preconditions.

---

## 1. Tournament and Scheduling Workflow (WF-07/WF-09, BC-12/BC-14)

Setup → format validation → entries assigned → draw or seeding preparation → review → schedule draft → conflict check → approval → publication → change → hold → completion → archive.

- `DrawCompleted`, `MatchScheduled`, `VenueScheduleChanged` are the catalog's existing notification-worthy events.
- Schedule publication follows the same Publication distinction as Official Results (Section, [result-protest-medal-and-publication-workflows.md, Section 4](result-protest-medal-and-publication-workflows.md#4-publication-workflow-cross-cutting)) — a draft/review-stage schedule is never broadcast to the public channel, restated from [realtime-broadcast-and-reverb-message-architecture.md, Section 4](realtime-broadcast-and-reverb-message-architecture.md#4-public-channel-restrictions).
- Conflict check is deterministic-first, consistent with Phase 0.10's schedule-conflict-detection candidate capability ([../07-ai/schedule-conflict-and-tournament-recommendation-architecture.md](../07-ai/schedule-conflict-and-tournament-recommendation-architecture.md)) — AI assistance, if ever approved, augments but never replaces the deterministic check.
- No sport-specific competition-format rule is invented here — format validation defers to [OD-10](../00-product/open-decisions.md#od-10--sports-rule-source), restated per working rule 56.

## 2. Technical Official Workflow (WF-08, BC-13)

Qualification-record reference → assignment → conflict declaration → acceptance → venue-and-event allocation → check-in → duty completion → incident → replacement → evaluation where approved → audit.

- `OfficialAssigned` is the catalog's existing notification-worthy event.
- Conflict declaration is a named human-task action (per [human-tasks-approvals-reviews-and-certifications.md, Section 4](human-tasks-approvals-reviews-and-certifications.md#4-conflict-of-interest)) — an official who declares a conflict is reassigned, never silently retained on a compromised assignment.
- Replacement (an official withdrawing or being reassigned mid-meet) triggers re-notification to the venue/tournament coordination channel, per [internal-messaging-announcements-and-communication-boundaries.md, Section 3](internal-messaging-announcements-and-communication-boundaries.md#3-committee-and-delegation-communications).

## 3. Scoring Workflow (WF-10/WF-11, BC-15, High-Integrity)

Score-entry readiness → entry → save draft where applicable → validation → submission → lock → correction request → correction → result generation → audit.

**The scoring workflow remains synchronous for authoritative validation** — restated absolutely per working rule 20/29 and [../01-architecture/laravel-architecture.md, Section 7](../01-architecture/laravel-architecture.md#7-synchronous-versus-asynchronous-decisions): score validation is a high-integrity state transition, never dependent solely on an unconfirmed background job.

- SOD-02 applies: the entering official must not validate their own score entry, restated from [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md).
- `ScoreRecorded` carries minimal payload (a score-record ID, not the full participant biographical record), restated from [event-taxonomy-ownership-and-contracts.md, Section 3](event-taxonomy-ownership-and-contracts.md#3-event-payload-rules).
- A save-draft state is explicitly not a submission — restated from [../06-design/form-validation-draft-and-workflow-experience.md, Section 4](../06-design/form-validation-draft-and-workflow-experience.md#4-draft-and-autosave-experience).
- Correction requires the same rigor as the original entry, never a lesser-scrutinized shortcut, per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession) — the prior score row is never overwritten, only superseded.
- Live scoreboard broadcast (Reverb) is provisional-only and visually distinct from certified results, restated from [realtime-broadcast-and-reverb-message-architecture.md, Section 6](realtime-broadcast-and-reverb-message-architecture.md#6-provisional-versus-published-distinction).
- Score capture is a named critical-offline-priority workflow (per [../01-architecture/offline-and-synchronization-boundaries.md](../01-architecture/offline-and-synchronization-boundaries.md)) — offline score capture is always Provisional pending server validation, restated from [offline-mobile-device-public-and-ai-workflow-boundaries.md, Section 1](offline-mobile-device-public-and-ai-workflow-boundaries.md#1-offline-workflow-boundaries).

## 4. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably the sport-specific competition-format cluster, blocked jointly on OD-10 exactly as every prior phase's sport-dependent decisions have been.
