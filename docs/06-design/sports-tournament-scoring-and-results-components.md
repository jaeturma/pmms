# PMMS Sports, Tournament, Scoring, and Results Components

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/bounded-context-catalog.md](../01-architecture/bounded-context-catalog.md) · [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [high-integrity-approval-certification-and-publication-ux.md](high-integrity-approval-certification-and-publication-ux.md)

This document defines interface patterns for tournament brackets, heats/lanes, score entry, results, medal tally, athlete profiles, rosters, officials assignment, venues, and schedules. **No sports-specific rule, scoring formula, ranking rule, or official form is invented here** — restated absolutely per working rule 34; every sport-specific requirement below is explicitly marked as requiring validation.

---

## 1. Tournament Bracket Standards

Support for: single elimination · double elimination where approved · round robin · pools · classification rounds · advancement · match state · schedule · venue · competitors · winner · walkover or special-status placeholders.

**Provide accessible alternatives to purely visual bracket diagrams** — restated absolutely; a bracket's information (who plays whom, when, and the current progression) is also available as an accessible table/list, never exclusively as an unlabeled visual tree diagram.

## 2. Match Cards

A compact, reusable summary unit (competitors, scheduled time, venue, current state) used across schedules, brackets, and dashboards — its state indicator (per [status-feedback-error-offline-and-sync-patterns.md, Section 1](status-feedback-error-offline-and-sync-patterns.md#1-status-vocabulary-and-status-badges)) is never omitted regardless of context.

## 3. Heat and Lane Interfaces

Event · heat · lane · athlete or team · delegation · seed reference · start status · result status · changes (a heat/lane reassignment is clearly flagged as changed, not silently updated) · print support (per [public-portal-kiosk-scoreboard-and-display-experience.md, Section 4](public-portal-kiosk-scoreboard-and-display-experience.md#4-report-and-print-experience)) · mobile support · accessible table representation (restated from Section 1's bracket-accessibility principle, applied here too).

## 4. Score Entry Interfaces

Prioritize: assigned event context (a scorer always sees which event/heat/attempt they're entering for, never ambiguous) · competitor identity · current round or attempt · clear numeric input · unit · precision (per [../02-data/database-naming-and-design-standards.md, Section 6](../02-data/database-naming-and-design-standards.md#6-score-measurement-and-timing-standards)) · penalties · validation · save state · submit state · lock state (a locked/validated score is visually distinct from an editable draft) · correction history (per [high-integrity-approval-certification-and-publication-ux.md, Section 4](high-integrity-approval-certification-and-publication-ux.md#4-correction-and-supersession-ux)) · offline status · device identity · operator identity.

**Minimize distractions and accidental navigation** — restated absolutely; a score-entry screen has no adjacent, easily-mis-tapped destructive or navigational control near the primary entry field, extending working rule 40's spacing discipline specifically to this highest-time-pressure context.

## 5. Timing Displays and Measurement Entry

Timing displays present elapsed/split/final time with the same precision and unit discipline as score entry, per [../02-data/database-naming-and-design-standards.md, Section 6](../02-data/database-naming-and-design-standards.md#6-score-measurement-and-timing-standards). Measurement entry (distance, height, weight-class-relevant figures) follows the identical precision/unit/device-source/validation pattern as Section 4 — timing and measurement are treated as specializations of score entry, not a separately-invented pattern.

## 6. Result Boards

A result board presents certified (never merely provisional) results by default for any audience beyond the entering/validating officials themselves, per [high-integrity-approval-certification-and-publication-ux.md, Section 5](high-integrity-approval-certification-and-publication-ux.md#5-publication-ux) — restated absolutely from working rule 36.

## 7. Medal Tally Standards

Delegation or team name · gold · silver · bronze · total · ranking rule reference (cited, never invented, per working rule 34) · last update · provisional or certified state · public publication state · filters · sorting · historical snapshots (per [../02-data/temporal-history-and-versioning-model.md, Section 4](../02-data/temporal-history-and-versioning-model.md#4-versioning-and-supersession)) · correction indication.

**Do not assume final ranking rules without validation** — restated absolutely; any tie-breaking or ranking-order logic displayed is explicitly marked "pending sports-rule-source validation" until confirmed.

## 8. Team Standings

Follows the same data-freshness, certification-state, and ranking-rule-citation discipline as medal tally (Section 7) — team standings are a related but distinct aggregate, never silently conflated with medal tally in either data or interface.

## 9. Athlete Profile Experience

### Internal Profile

May include protected operational information based on the viewer's authorization (eligibility status detail, accreditation status, internal notes) — access-gated per [../02-data/information-classification-and-privacy.md](../02-data/information-classification-and-privacy.md).

### Public Profile

Includes only approved publishable fields: identity (name, delegation-appropriate detail) · sports · events · results · medals · eligibility status summary (status only, never evidence) · accreditation status (status only) · public media (only approved-for-publication images, per [typography-iconography-and-content-style.md, Section 3](typography-iconography-and-content-style.md#3-photography-and-media)).

**Never expose medical details, guardian data, eligibility evidence, or protected contact information publicly** — restated absolutely per working rule 26, directly extending [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) into the profile-display layer.

## 10. Team Rosters and Officials Assignment

Team rosters display delegation-scoped athlete/coach membership with the same public/internal distinction as athlete profiles (Section 9). Officials-assignment interfaces show an official's current, scoped assignments (per [../01-architecture/assignment-model.md](../01-architecture/assignment-model.md)) — never a static role label divorced from the specific, time-bound assignment granting actual authority, restated from [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md)'s Role ≠ Assignment distinction.

## 11. Venue Boards and Schedule Timelines

Venue boards summarize a venue's active events, incidents, and device status (cross-referencing [committee-logistics-medical-finance-and-support-experience.md](committee-logistics-medical-finance-and-support-experience.md) for incident detail). Schedule timelines present time-ordered event data with venue/sport filtering, sharing the same accessible-table-alternative principle as brackets (Section 1) — a timeline view is a visualization convenience, never the sole way to access the underlying schedule data.

## 12. Sport-Specific Requirement Validation

Every sport-specific interface element in this document — bracket format support, scoring precision, ranking rules, timing conventions — is marked as requiring validation against an approved DepEd/sports-governing-body rule source before being treated as final, per working rule 35. This document defines the *interface pattern* a validated rule fills in, never the rule itself.

## 13. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably which specific sports require dedicated bracket/heat interface variants (pending [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source) sports-rule-source resolution) and medal-tally tie-breaking display treatment.
