# PMMS Production Role-Permission Matrix

Roles define capability; assignments and active team memberships define scope. A role alone never expands a user beyond the meet, sport, category, municipality, district, school, venue, or team recorded by an active assignment.

| Role / capability set | Purpose and allowed actions | Required scope | Prohibited / sensitive boundary |
|---|---|---|---|
| Super Administrator | Users, roles, permissions, meets, configuration, provisioning, activation, password reset, reference data, assignments, maintenance | Approved production administrator account | All privileged actions audited; no generic/demo account; medical detail still accessed only for an authorized purpose |
| Central ICT Team | Provision/link/activate/deactivate accounts, reset passwords, maintain approved assignment mappings, technical settings and support | Active ICT team membership; meet scope where applicable | No implicit DSAC approval, medical clearance, or official-result authority |
| Top Management | Executive dashboard, readiness, progress, official results, medal tally, major incidents/issues and reports | Active Top Management membership | No implicit user/system administration or result editing; sensitive case detail restricted |
| Meet Manager | Meet operations, personnel assignment/replacement, venue/schedule monitoring and issue coordination | Active Meet Manager assignment for the meet | No implicit global authentication administration, DSAC, medical, or final-result authority |
| QA / Monitoring & Evaluation | View operations/compliance, record findings and evaluation reports, monitor resolution | Active QA/M&E membership | No result editing or confidential medical detail |
| Learners Rights & Protection | Safeguarding incident/referral handling | Active team membership and case authorization | Strict case privacy; no unrelated athlete/medical/result authority |
| Sports Line-Up & Placement | View sports/categories/placements and coordinate sequencing/deployment | Active team membership | No official-result finalization |
| Central Secretariat | Central documents, communications, minutes and records routing | Active Secretariat membership | Distinct from Tournament Secretary and Event Secretariat |
| Grievance | Controlled intake, routing, status and resolution | Active Grievance membership and assigned cases | Sensitive details restricted; no unrelated operational authority |
| Playing Venue | Venues, competition areas, readiness, conflicts, coordinators and venue issues | Active Playing Venue membership; assigned venues where configured | No official-result authority |
| Peace & Security | Security readiness, access/crowd incidents, liaison records | Active team membership; assigned venue/incident | Restricted security notes only to authorized members |
| Logistics | Equipment, supplies, movement, staging and requests | Active Logistics membership | No user, eligibility, medical, or result authority |
| Water, Light & Sanitation | Facility readiness/issues and resolution tracking | Active team membership; assigned venues | No user, eligibility, medical, or result authority |
| Information | Approved advisories, announcements, publications and portal information | Active Information membership | No system administration or confidential case access |
| Event Secretariat | Review submitted results, return with reason, validate, make official; highly restricted reopen | Active `EVENT_SECRETARIAT` membership for the meet | Cannot silently overwrite; every return/correction/reopen/finalization audited; no DSAC/medical authority |
| Medical Team | Evaluate and clear configured athletes and personnel; view protected clinical fields | Active Medical membership for the meet | Non-medical users see status only; diagnosis, notes and attachments remain restricted |
| Food / Meals | Meal schedule/distribution information and simple operational coordination | Active Food membership | No unrelated sensitive or approval authority |
| DSAC | Athlete profile/document review and eligibility approval | Active DSAC membership for the meet | No medical clearance or result authority |
| Municipal / Team Manager | Municipality/delegation readiness monitoring | Active municipality oversight assignment | Cannot approve DSAC, medical, or results |
| District Sports Coordinator | District school/coach/athlete/readiness monitoring | Active meet + school-district assignment | Cannot approve DSAC, medical, or results |
| Tournament Manager | Sport operations, schedules/venues, participant view, result submission/endorsement | Active MeetSport assignment; optional category/venue | No other sport and no direct official medals/finalization |
| Assistant Tournament Manager | Explicitly delegated sport operations | Active Assistant TM MeetSport assignment | No undelegated actions and no finalization |
| Tournament Secretary | Sport records, documentation, result preparation/submission support | Active MeetSport assignment; optional category/venue | No unrelated sport or official finalization |
| Tournament ICT | Sport-side scoring/device/network/display support and authorized encoding | Active MeetSport assignment; optional category/venue | Distinct from Central ICT; no account administration or finalization |
| Technical Official | Officiating, assigned technical records/encoding/attestation | Active MeetSport assignment; optional category/venue; verified accreditation where required | No unrelated sport, Results Committee power, DSAC, or medical authority |
| Game Coordinator | Assigned venue/game coordination | Active meet/sport/venue assignment | No official-result finalization unless separately authorized |
| Coach | Self-register, manage own profile, assigned team/athletes/documents, submit/correct returned records | Approved meet + municipality + school + sport/category scope | Cannot enroll outside approved scope or approve eligibility/medical/results |

## Critical enforcement rules

- Only `OFFICIAL` results may feed official standings, rankings, awards, and medal tally.
- Event Secretariat authority comes from active membership in source team `EVENT_SECRETARIAT`, not from generic `meet_management` membership.
- Medical and learner-protection clinical/case detail must not be serialized to unauthorized users; status-only views are the default.
- Assignment replacement ends the old row and creates a new row. Users and people are not deleted when an assignment ends.
- Password reset permission is explicit. The configured default is never displayed, persisted as plain text, or copied into audit metadata.
- Legacy `sports.tournament_manager_id` and `sport_user` are compatibility data, not the production scope model.

## Result Form and official submission permissions

| Capability | TM | Assistant TM | Tournament Secretary | Tournament ICT | Technical Official | Event Secretariat | Super Administrator |
|---|---:|---:|---:|---:|---:|---:|---:|
| Generate/print assigned Result Form | Yes | Yes | Yes | Yes | No, unless separately assigned | Review only | Yes |
| Upload current-version signed form | Yes | Yes | Yes | Yes | No, unless separately assigned | View/download | Yes |
| Submit assigned result | Yes | Yes | Yes | Yes | No, unless separately assigned | Review | Yes |
| Return with reason | No | No | No | No | No | Yes | Yes |
| Validate | No | No | No | No | No | Yes | Yes |
| Make official | No | No | No | No | No | Yes | Yes |
| Reopen official result | No | No | No | No | No | Separately controlled | Yes |

Every sport-personnel capability above requires an active assignment matching both `meet_id` and `sport_id`. Supporting documents remain authenticated/internal and are not implied by public official-result visibility.
