# WP-02-08 — School and Delegation Management

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 2 — Core Administration and Setup

## Objective
Implement the school and delegation management foundation while preserving the difference between the athlete’s school and the official competing delegation.

Core rule:

```text
School = Athlete’s origin institution
Delegation = Official competing entity for a specific meet
```

For a Provincial Meet:

```text
Municipality = Delegation
School = Athlete’s origin
```

For a City Meet:

```text
School = Delegation
School = Athlete’s origin
```

## Required Reading

```text
.ai/project-rules.md
.ai/current-phase.md
.ai/work-package-runner.md
docs/phases/phase-02-core-administration/README.md
docs/phases/phase-02-core-administration/WP-02-02-organization-structure.md
docs/phases/phase-02-core-administration/WP-02-08-school-and-delegation-management.md
```

Also read the relevant Phase 1 and Phase 2 completion reports.

## Scope

### Delegation Record

A delegation belongs to one meet and represents one organization.

Support practical fields such as:

- Meet
- Delegation name
- Delegation type
- Represented organization
- Delegation code
- Short name
- Status
- Contact person
- Contact details
- Optional logo and colors
- Date registered
- Review remarks
- Submission and approval timestamps

### Delegation Types

Support at least:

- School
- Municipality
- Division
- Region
- Other

Version 1 prioritizes School and Municipality.

### Delegation Schools

For a municipality delegation, associate only schools from the same municipality.

Example:

```text
Delegation: Nabunturan
Schools:
- Nabunturan National Comprehensive High School
- Manat National High School
- Mainit National High School
```

For a school delegation, the represented school should normally be the only school attached.

### Future Athlete Relationship

Prepare Phase 3 to record:

```text
Athlete
- meet_id
- delegation_id
- school_id
```

This preserves both the competing delegation and school of origin.

### Delegation Officials

Allow basic assignment of:

- Delegation Head
- Assistant Delegation Head
- School Coordinator
- Coach
- Chaperone
- Other authorized delegation personnel

### Status Workflow

Use a simple workflow:

- Draft
- Submitted
- Under Review
- Approved
- Returned
- Withdrawn
- Inactive

Returned records must include remarks.

## Business Rules

1. A delegation belongs to one meet.
2. A delegation represents one approved organization.
3. A represented organization should have only one active delegation of the same type in the same meet.
4. Municipality delegations may include only schools from that municipality.
5. School delegations must represent the attached school.
6. A school may participate in multiple meets under different delegations.
7. Athlete school origin must never be replaced by the delegation.
8. Results and medals are credited to the delegation.
9. School reports may still aggregate by school.
10. Delegation codes must be unique within a meet.
11. Approval requires an authorized role.
12. Returned records require remarks.
13. Historical delegation records must remain traceable.
14. Removing a school with linked athletes must be blocked or handled through a controlled reassignment process later.

## Recommended Data Model

Inspect the repository first.

Possible tables:

```text
delegations
delegation_schools
delegation_members
```

Recommended `delegations` fields:

```text
id
meet_id
delegation_type
represented_school_id nullable
represented_municipality_id nullable
represented_division_id nullable
represented_region_id nullable
name
short_name nullable
code
status
contact_name nullable
contact_email nullable
contact_number nullable
logo_path nullable
primary_color nullable
secondary_color nullable
submitted_at nullable
approved_at nullable
approved_by nullable
remarks nullable
created_at
updated_at
```

Recommended `delegation_schools` fields:

```text
id
delegation_id
school_id
is_primary
status
created_at
updated_at
```

Recommended `delegation_members` fields:

```text
id
delegation_id
user_id nullable
person_name nullable
role_type
school_id nullable
contact_email nullable
contact_number nullable
status
created_at
updated_at
```

Use an approved polymorphic relationship only when the repository already supports it safely. Do not introduce polymorphism only for theoretical flexibility.

## Backend Requirements

- Create services or actions for create, update, submit, review, approve, return, withdraw, and reactivate.
- Use Form Requests.
- Keep controllers small.
- Validate delegation type against represented organization.
- Validate school-to-municipality ownership.
- Prevent duplicate active delegations.
- Prevent invalid school inclusion.
- Enforce backend authorization.
- Record important status and assignment changes in the audit trail.
- Prepare relationships for Phase 3 athlete and coach registration.

## Frontend Requirements

Create responsive Inertia pages for:

### Delegation List

Include:

- Meet filter
- Delegation type filter
- Status filter
- Search
- Represented organization
- Number of included schools
- Permission-based actions

### Delegation Form

Include:

- Meet
- Delegation type
- Represented organization
- Delegation name
- Code
- Contact information
- Optional branding
- School assignment
- Official assignment

### Delegation Review

Include:

- Summary
- Schools
- Officials
- Status history
- Remarks
- Approve
- Return
- Withdraw
- Reactivate where allowed

### School View

Show:

- School profile
- Municipality or city
- Current delegation participation
- Historical meet participation

Do not implement athlete registration.

## Authorization

Typical permissions:

```text
delegations.view
delegations.create
delegations.update
delegations.submit
delegations.review
delegations.approve
delegations.return
delegations.withdraw
delegations.reactivate
delegations.assign-schools
delegations.assign-officials
```

Use the project’s actual naming convention.

Suggested role behavior:

- System Administrator — full management
- Secretariat — review and approve
- Sports Coordinator — view and review
- School Coordinator — manage permitted records
- Delegation Head — manage own delegation where supported
- Authorized Viewer — read-only

## Audit Requirements

Record:

- Delegation created
- Delegation updated
- School added or removed
- Official assigned or removed
- Delegation submitted
- Delegation approved
- Delegation returned
- Delegation withdrawn
- Delegation reactivated

Include actor, meet, delegation, action, timestamp, and remarks where applicable.

## Testing

Add focused tests for:

- Municipality delegation creation
- School delegation creation
- Duplicate delegation prevention
- Invalid represented organization
- Invalid school municipality
- Adding valid schools to municipality delegation
- Blocking schools from another municipality
- School delegation using only its own school
- Submission
- Approval
- Return with remarks
- Withdrawal
- Unauthorized access
- School Coordinator scope
- Delegation code uniqueness
- Audit recording
- Historical relationship preservation

Run all established backend and frontend quality checks.

## Documentation

Create or update:

```text
docs/reports/phase-02/WP-02-08-completion.md
docs/database/delegation-model.md
docs/user-manual/delegation-management.md
.ai/current-phase.md
.ai/project-context.md
```

Update Phase 3 documents that depend on delegation behavior.

## Acceptance Criteria

- Delegations can be created for a meet.
- Municipality delegations can include only schools from the same municipality.
- Schools from another municipality are rejected.
- School delegations represent the correct school.
- School remains the athlete’s origin institution.
- Delegation remains the official competing entity.
- Duplicate active delegations are prevented.
- Status workflow works.
- Approval and return require authorization.
- Important changes are audited.
- Responsive pages work on desktop, tablet, and mobile browsers.
- Phase 3 can link athletes to both school and delegation.
- Medal tally can aggregate by delegation.
- School reports can aggregate by school.
- Tests and quality checks are completed.
- Documentation is updated.
- No athlete registration, results, medal tally, Flutter, AI, SaaS, or enterprise feature is implemented.
- No commit or push is performed.

## Completion Report

Create:

```text
docs/reports/phase-02/WP-02-08-completion.md
```

Report:

1. Repository findings
2. Files created
3. Files modified
4. Database changes
5. Delegation model
6. School and delegation relationship
7. Municipality delegation rules
8. School delegation rules
9. Backend changes
10. Frontend changes
11. Authorization and audit
12. Tests and quality results
13. Remaining issues
14. Documentation updates
15. Git status

Recommended next work package:

```text
WP-02-09 — System Settings
```

Do not begin it.
