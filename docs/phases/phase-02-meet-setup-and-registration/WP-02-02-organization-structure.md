# WP-02-02 — Organization Structure

## Project
Provincial Meet Management System (PMMS) — Division Edition

## Phase
Phase 2 — Core Administration and Setup

## Objective
Implement the organization structure required by PMMS while clearly separating the athlete’s school from the competing delegation.

Core rule:

```text
School = Athlete’s origin institution
Delegation = Official competing entity for a specific meet
```

For the current Provincial Meet:

```text
Municipality = Delegation
Athletes come from schools within that municipality
```

For a City Meet:

```text
School = Delegation
Athletes come from that school
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

Also read previous Phase 1 and Phase 2 completion reports.

## Scope

Implement the minimum organization structure needed for PMMS:

- Province
- City or municipality
- District, when applicable
- School
- Meet level
- Active/inactive status
- Codes and identifiers
- Parent-child relationships

Support meet levels such as:

- City
- District
- Provincial
- Regional
- National

Version 1 prioritizes Provincial Meet. Regional and National workflows remain deferred.

## Required Relationships

Recommended hierarchy:

```text
Province
└── City or Municipality
    ├── District, optional
    └── School
```

Each school must belong to one city or municipality.

A school may participate in different meets under different delegations.

A municipality or school must not automatically become a delegation until it is registered as a competing entity for a meet.

## Business Rules

1. A city or municipality belongs to one province.
2. A school belongs to one city or municipality.
3. A district is optional unless required by the source data.
4. A school remains the athlete’s origin institution.
5. A delegation is meet-specific.
6. Provincial Meet medals are credited to the municipality delegation.
7. School-level reports may still use the athlete’s school.
8. City Meet delegations may be schools.
9. The school table must not hardcode Provincial Meet assumptions.
10. Historical organization records must remain traceable.

## Database Direction

Inspect the existing schema first.

Possible tables:

```text
provinces
municipalities
districts
schools
meet_levels
```

Use:

- Foreign keys
- Unique constraints
- Practical indexes
- Timestamps
- Soft deletes only when justified

Avoid:

- Enterprise organization engines
- Deep multi-tenant hierarchies
- Unnecessary polymorphism
- Tables for future levels that are not needed yet

## Backend Requirements

- Use clear Eloquent relationships.
- Use Form Requests or the approved validation pattern.
- Prevent duplicate records.
- Validate parent relationships.
- Keep controllers small.
- Apply backend authorization.
- Record important changes in the audit trail where available.

## Frontend Requirements

Provide responsive administration pages for:

- Provinces
- Cities or municipalities
- Districts, if applicable
- Schools
- Meet levels

Include:

- Search
- Filters
- Add and edit forms
- Status controls
- Loading, empty, validation, and permission-denied states

Do not implement delegation registration here.

## Authorization

Use the project’s permission naming convention. Typical permissions:

```text
organizations.view
organizations.manage
schools.view
schools.manage
meet-levels.view
meet-levels.manage
```

## Testing

Add focused tests for:

- Province creation
- Municipality or city creation
- School creation
- Duplicate prevention
- Invalid parent relationships
- Unauthorized access
- Active/inactive filtering
- School-to-municipality ownership
- Meet-level validation

Run all established backend and frontend quality checks.

## Documentation

Create or update:

```text
docs/reports/phase-02/WP-02-02-completion.md
docs/database/organization-structure.md
.ai/current-phase.md
.ai/project-context.md
```

## Acceptance Criteria

- Organization records can be managed.
- Every school belongs to the correct city or municipality.
- Duplicate organization records are prevented.
- Meet levels are supported without implementing future meet workflows.
- School and delegation are not treated as the same concept.
- The structure supports municipality delegations for Provincial Meet.
- The structure supports school delegations for City Meet.
- Authorization is enforced.
- Tests and quality checks are completed.
- Documentation is updated.
- No Regional, National, Flutter, AI, SaaS, or enterprise feature is implemented.
- No commit or push is performed.

## Completion Report

Create:

```text
docs/reports/phase-02/WP-02-02-completion.md
```

Report:

1. Repository findings
2. Files created
3. Files modified
4. Database changes
5. Organization hierarchy
6. School ownership rules
7. Meet-level support
8. Authorization
9. Tests and quality results
10. Remaining issues
11. Documentation updates
12. Git status

Recommended next work package:

```text
WP-02-03 — Role-Based Access Control
```

Do not begin it.
