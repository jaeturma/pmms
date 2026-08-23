# Production role and permission matrix

## Authorization model

PMMS uses one user account per person. `UserRole` is the broad account capability; active meet-sport assignments, athlete-oversight assignments, coach assignments, and management-team memberships determine scope. A person may therefore hold several duties without duplicate user records. Backend policies, gates, and scoped queries are authoritative; navigation is only a convenience layer.

Legend: **Scoped** means current meet plus the assigned sport/category, delegation, district, or management unit. **R/O** means read-only.

| Role / production unit | Purpose | Data scope | Visible modules | View | Create / update | Approve / validate | Submit | User management | Restrictions |
|---|---|---|---|---|---|---|---|---|---|
| Superadmin / Administrator | System ownership | Organization-wide | All | Yes | Yes | Yes | Yes | Full | Sensitive actions audited |
| Meet Organizer (baseline) | Meet-wide coordination and monitoring | Current meet | Meet setup, registration, competition, and meet operations | R/O | No | No | No | No | An explicit active functional assignment is required for every mutation |
| Central ICT | Account and technical support | Organization-wide; assignments retain meet scope | Users, provisions, schools, assignments, schedules, monitoring | Yes | Accounts, school master data, assignments, schedule support | Coach registration support | No competition decisions | Provision, invite, reset, assign | No eligibility, medical, result, or grievance decision authority |
| Top Management | Executive monitoring | Current meet | Management reports and published operational data | R/O | No | No | No | No | Monitoring only |
| Information | Public information | Current meet announcements | Announcements and published information | Yes | Announcements | Publish/unpublish announcements | No | No | Not Central ICT; no accounts or competition mutations |
| DSAC | Athlete eligibility | Current meet athletes | Athletes, eligibility | Yes | Review remarks | Eligibility only | No | No | No entries/results/medical approvals |
| Supply / LOGISTICS | Equipment custody | Current meet supply records | Equipment/inventory | Yes | Supply records | Supply workflow only | Yes | No | No athlete medical or results data |
| Food / FOOD_MEALS / kitchen personnel | Meals operations | Current meet food records | Food | Yes | Meal records | Food workflow only | Yes | No | No unrelated modules |
| Billeting | Accommodation | Current meet billeting records | Billeting | Yes | Billeting records | Billeting workflow only | Yes | No | No unrelated modules |
| Medical | Medical clearance | Current meet medical records | Medical | Yes, including protected clinical detail | Medical records | Medical clearance only | Yes | No | Clinical detail restricted to medical authority |
| DRRM / INCIDENT_COMMAND | Incident response | Current meet incidents and plans | DRRM | Yes | Incidents/plans | DRRM workflow only | Yes | No | No medical/eligibility/result authority |
| Tournament Manager | Competition administration | Assigned sport/category, all delegations | Athletes, entries, schedule, matches, results, coaches | Yes | Scoped competition records | Confirm scoped entries | Scoped | No | Cannot access other sports/categories |
| Assistant Tournament Manager | Tournament support | Assigned sport/category, all delegations | Same scoped competition modules | Yes | Scoped support | Confirm scoped entries | Scoped | No | Cannot access other sports/categories |
| Tournament Secretary | Tournament records | Assigned sport/category, all delegations | Same scoped competition modules | Yes | Scoped records | Confirm scoped entries | Scoped | No | Cannot access other sports/categories |
| Tournament ICT | Tournament technical support | Assigned sport/category, all delegations | Same scoped competition modules | Yes | Scoped technical records | Confirm scoped entries | Scoped | No | Not Central ICT; cannot manage global accounts |
| Technical Official | Officiating and scoring | Assigned sport/category, all delegations | Schedule, matches, scoring, results | Yes | Scores/results in scope | Official result workflow in scope | Scoped | No | Cannot access another assignment |
| Event Secretariat | Result consolidation | Current meet; result workflow | Entries, schedules, matches, results | Yes | Result records as policy permits | Result workflow only | Scoped | No | No eligibility or medical decisions |
| Coach / Assistant Coach | Manage team competitors | Assigned delegation and sport/event | Owned athletes, entries, schedule/results relevant to assignment | Yes | Owned athletes and eligible entries | No | Entries in scope | No | Cannot see private athletes outside ownership; confirmed entries require approved eligibility |
| District Sports Coordinator | District oversight | Assigned district/current meet | District athletes and delegation information | Yes | As policy permits | No | Scoped | No | District only |
| Municipality / Team Manager | Delegation oversight | Assigned municipality/delegation/current meet | Delegation athletes and entries | Yes | Delegation records as policy permits | No | Scoped | No | Delegation only |
| Quality Assurance Monitoring Evaluation | Compliance monitoring | Current meet | Published/operational monitoring | R/O | No | No | No | No | No competition mutations |
| Learners Rights and Protection Desk Committee | Learner protection | Current meet authorized cases | Assigned protection workflow | Scoped | Case workflow only | As policy permits | Scoped | No | No broad medical/results access |
| Sports Lines Up and Placement | Sports coordination | Current meet operational data | Sports lineup and published competition information | R/O unless assigned another role | No | No | No | No | Assignment required for mutations |
| Secretariat | Meet records support | Current meet | Published/management monitoring | R/O | No | No | No | No | Assignment required for mutations |
| Grievance | Protest resolution | Current meet grievance records | Protests/grievances | Yes | Grievance workflow | Grievance decisions only | Yes | No | No eligibility/medical authority |
| Playing Venue | Venue coordination | Current meet venues/schedules | Venues and schedules | R/O | No | No | No | No | Assignment required for schedule mutation |
| Peace and Security | Safety monitoring | Current meet operational data | Published/management monitoring | R/O | No | No | No | No | No incident mutation unless DRRM assignment |
| Water Light Sanitation | Facility monitoring | Current meet operational data | Published/management monitoring | R/O | No | No | No | No | No unrelated mutation |
| Finance | Financial support | Current meet authorized records | Management monitoring | R/O | No | No | No | No | No competition mutation |
| Opening/Closing Program, Decoration, Usherettes, Clean Green, Announcers, Support Staff | Meet operations | Current meet | Published/management monitoring | R/O | No | No | No | No | Default monitoring access only |

## Non-negotiable separation of duties

- Information is not Central ICT.
- The Organizer base role is read-only. Organizer accounts gain write authority only from an active Information, ICT, equipment/supply, incident/DRRM, medical, DSAC, tournament-manager, tournament-secretary, tournament-ICT, technical-official, or other explicitly modeled functional assignment.
- Tournament ICT is scoped to its assigned sport/category and is not Central ICT.
- DSAC alone decides eligibility; Medical alone decides medical clearance.
- A confirmed entry requires approved athlete eligibility.
- Result/scoring authority comes from an active sport/category assignment, never merely from a visible menu.
- Missing, inactive, declined, ended, or mismatched assignments grant no scoped authority.
