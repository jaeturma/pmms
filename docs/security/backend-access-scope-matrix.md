# Backend access scope matrix

PMMS effective access is the intersection of an action capability and a data assignment. Visibility never implies create, update, approve, validate, delete, or export authority.

| Role | Data scope | Visible modules | Manage/approval authority | Restrictions |
|---|---|---|---|---|
| System Admin / authorized Central ICT | System-wide, according to existing permissions | Administrative and competition modules | Existing system permissions | Central ICT is not a Tournament ICT assignment. |
| Organizer | Meet-wide competition administration | Meet and competition modules | Existing organizer policies | Does not replace DSAC or Medical decisions. |
| Tournament Manager | Assigned whole sport or assigned category/event; all delegations | Coaches, athletes, entries, teams, schedules, matches, results, sport personnel | Competition management and coach actions allowed by existing policies | No unrelated sport/category data. |
| Assistant Tournament Manager | Assigned whole sport or assigned category/event; all delegations | Same scoped operational records | Only actions explicitly allowed by policy | Visibility does not imply TM approval authority. |
| Tournament Secretary | Assigned whole sport or assigned category/event; all delegations | Scoped coaches, participants, entries, schedules, competitions, results/forms | Processing actions explicitly allowed by policy | No Central ICT authority. |
| Tournament ICT | Assigned whole sport or assigned category/event; all delegations | Scoped schedule, scoring support, results, coaches/participants needed for operations | Technical/support actions explicitly allowed by policy | Not system-wide and cannot inherit Central ICT rights. |
| Technical Official | Assigned whole sport or assigned category/event; all delegations | Scoped participants, schedule, matches, live scoring, technical records/results | Officiating/scoring actions explicitly allowed by policy | Cannot edit athlete master profiles or approve coaches by visibility alone. |
| Coach | Assigned delegation plus approved event assignment | Own athletes, entries, teams, schedule and results | Existing coach registration/entry actions | Cannot see a competing delegation, even in the same event. |
| Assistant Coach | Assigned delegation plus linked sport/event assignment | Same data boundary as the linked coaching assignment | Only explicitly delegated actions | Role alone grants no delegation or sport access. |
| DSAC | Current meet, all delegations and sports, athlete eligibility data | Athlete registry, profiles, eligibility queue/documents/reports | Eligibility actions allowed by DSAC permissions | Cannot administer schedules, matches, scoring, or results. |
| Medical Team | Assigned medical workflow scope | Clearance status and authorized medical workflow | Medical actions allowed by permission | Detailed notes/diagnoses are not exposed to tournament personnel. |
| Event Secretariat | Assigned meet/result workflow | Result forms and result-processing workflow | Existing secretariat actions | Does not gain athlete, schedule, or scoring administration. |

## Scope meanings

- **Meet-wide authority**: applies only to the named responsibility within the meet, such as DSAC athlete eligibility.
- **Sport/event authority**: includes all delegations, but only records whose event is within an active tournament assignment. A null `sport_category_id` means the entire sport; a populated category limits the assignment to events in that category.
- **Delegation authority**: requires the record's delegation and event to match the coach's approved assignment.

Every list query, direct record route, attachment, print view, and export must enforce the same boundary on the backend. Unauthorized direct access returns `403` (or `404` where the endpoint deliberately conceals record existence).

