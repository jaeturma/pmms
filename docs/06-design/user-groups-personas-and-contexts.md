# PMMS User Groups, Proto-Personas, and Contexts

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md) · [experience-vision-and-design-principles.md, Section 5](experience-vision-and-design-principles.md#5-experience-contexts) · [product-surfaces-and-workspace-architecture.md](product-surfaces-and-workspace-architecture.md)

This document maps PMMS's 53 Phase 0.3 roles to 27 user groups with role-based experience needs. **These are proto-personas, not researched personas** — each is explicitly marked as requiring validation against real users before being treated as authoritative, per working rule 7 ("Do not create fictional personal profiles unless supported by research").

---

## 1. User Groups and Experience Needs

| User Group | Primary Tasks | Device | Connectivity Need | Skill Assumption | Key Experience Need |
|---|---|---|---|---|---|
| Platform administrators | Platform configuration, role/permission management | Desktop | Stable | High technical | Precision, audit visibility |
| Organization administrators | Organization/committee setup | Desktop | Stable | Moderate | Clarity, guided setup |
| Meet administrators | Meet configuration, oversight dashboards | Desktop | Stable | Moderate | Overview, readiness signals |
| Secretariat | Registration processing, document handling | Desktop | Stable | Moderate | Efficient data entry, bulk actions |
| Tournament managers | Draws, brackets, scheduling, official assignment | Desktop/tablet | Stable–Intermittent | Moderate–High (domain) | Fast reconfiguration, conflict visibility |
| Technical officials | Rule enforcement, event oversight | Tablet/mobile | Intermittent–Offline | Moderate (domain-expert) | Minimal distraction, fast confirmation |
| Scorers | Live score entry | Mobile/tablet | Intermittent–Offline | Low–Moderate | Speed, error prevention, offline safety |
| Timers | Timing capture | Mobile/tablet, timing device | Intermittent–Offline | Low–Moderate | Precision entry, minimal steps |
| Result validators | Score review before certification | Desktop/tablet | Stable–Intermittent | Moderate | Clear comparison, flagging tools |
| Result certifiers | Final result certification | Desktop | Stable | Moderate–High (authority) | Explicit consequence, confirmation |
| Tally team | Medal tally encoding/certification | Desktop | Stable | Moderate | Real-time recalculation visibility |
| Delegation heads | Roster management, entries | Desktop/mobile | Stable–Intermittent | Low–Moderate | Simple status tracking |
| Coaches | Entry review, schedule checking | Mobile | Intermittent | Low–Moderate | Mobile-first, at-a-glance status |
| Athletes | Profile, schedule, results viewing | Mobile/public web | Variable, often low-bandwidth | Low | Simplicity, low data usage |
| Parents and guardians | Result/schedule viewing, limited profile access | Mobile/public web | Variable, often low-bandwidth | Low | Simplicity, minor-data-appropriate content |
| Medical staff | Incident logging, clearance review | Mobile/tablet | Intermittent | Moderate (clinical, not necessarily technical) | Fast entry, strong privacy cues |
| Food committee | Meal entitlement tracking | Mobile/tablet | Intermittent–Offline | Low | Simple scan-and-confirm flows |
| Transportation | Trip/schedule coordination | Mobile | Intermittent | Low–Moderate | Clear schedule visualization |
| Billeting | Accommodation assignment tracking | Desktop/tablet | Stable–Intermittent | Low–Moderate | Roster clarity |
| Finance | Budget/expense processing | Desktop | Stable | Moderate | Precision, audit trail visibility |
| Security | Incident logging, access oversight | Mobile/tablet | Intermittent | Moderate | Fast incident capture |
| ICT | Device/system support | Desktop/mobile | Stable–Intermittent | High technical | Diagnostic detail, device status |
| Media | Public content drafting, publication coordination | Desktop | Stable | Low–Moderate | Preview-before-publish clarity |
| Accreditation officers | Credential issuance/management | Desktop | Stable | Moderate | Batch operations, status tracking |
| Scanner operators | QR/access validation at gates | Mobile/dedicated device | Intermittent–Offline | Low | Extreme simplicity, instant feedback |
| Auditors | Audit trail review | Desktop | Stable | Moderate–High | Complete traceability, export tools |
| Public users | Result/schedule/medal-tally viewing | Any device, public web | Variable, often low-bandwidth | Low | Fast load, mobile-friendly, no login |

## 2. Proto-Persona Format (Validation-Required)

Each user group above is a **role-based proto-persona**, not a researched persona — it describes what the Phase 0.3 role catalog and this phase's contextual analysis suggest about likely needs, not confirmed behavior from real users. Before any proto-persona is treated as design-authoritative, it requires:

- Validation against actual committee staff, technical officials, and athletes/guardians during the pilot (per [../04-quality/pilot-operational-and-stakeholder-validation.md](../04-quality/pilot-operational-and-stakeholder-validation.md)).
- Confirmation that the assumed device/connectivity/skill profile matches real deployment conditions.
- Revision where pilot findings contradict the proto-persona's assumptions.

## 3. Relationship to Phase 0.3 Roles

Every user group above maps to one or more of the 53 roles in [../01-architecture/role-catalog.md](../01-architecture/role-catalog.md) — this document does not redefine role boundaries, it groups roles by shared experience needs for design purposes. A single role may appear in more than one user group's "primary tasks" where its actual responsibilities span multiple experience contexts (e.g., a Meet Director spans both "meet administrators" and elements of "tournament managers").

## 4. Cross-Reference to Experience Contexts

Every user group's device/connectivity/skill profile is grounded in the nineteen experience contexts defined in [experience-vision-and-design-principles.md, Section 5](experience-vision-and-design-principles.md#5-experience-contexts) — a scanner operator's "QR scanning" context and a scorer's "live competition"/"score encoding" context directly inform the interaction design requirements in [accreditation-qr-device-and-shared-station-experience.md](accreditation-qr-device-and-shared-station-experience.md) and [sports-tournament-scoring-and-results-components.md](sports-tournament-scoring-and-results-components.md) respectively.

## 5. Open Questions

See [design-open-decisions.md](design-open-decisions.md) — notably which user groups receive dedicated pilot-time usability validation first (given limited pilot resources), and whether formal persona research (interviews, contextual inquiry) is conducted before or after the pilot.
