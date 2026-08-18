# Athlete validation authority alignment

## Existing behavior retained

- Coaches already register and edit athletes through a delegation-scoped `Personnel.user_id` identity and may upload private eligibility documents while registration is open.
- `EligibilityReview` remains the authoritative DSAC decision record; `EligibilityDocument` remains the private document record.
- `MedicalClearance` and Medical management-team membership remain the independent medical authority. Non-medical users receive only aggregate status; confidential conditions and notes remain hidden.
- Event entries, category rules, private storage, access auditing, accreditation cards, and eligibility history are reused.

## Conflicts corrected

- Every Organizer previously had eligibility decision authority. Approval/return/rejection now requires Admin or active membership of the meet's Division Screening and Accreditation Committee.
- Document verification previously reused one broad decision gate. School ID, PSA, and parental consent now require DSAC permission; Medical Certificate verification requires Medical Team permission.
- Coach registry queries were not explicitly scoped even though the policy was scoped. Coach list queries now follow the Coach's delegation.
- DSC/Team Manager monitoring previously had no durable assignment scope. `athlete_oversight_assignments` binds a user to meet, municipality, and optionally School District.

## Permission and scope model

The `Permission` enum is an application permission catalog. `User::hasPermission()` resolves authority from Admin status, active DSAC/Medical management-team membership, or active scoped oversight assignments. Policies still enforce record scope by Meet, Municipality, School District, School, and delegation.

DSC and Municipality Team Manager assignments grant monitoring only. Neither assignment grants document verification, DSAC decisions, medical mutation, or final eligibility mutation.

## Eligibility composition

`AthleteEligibilityChecker` now labels every rule with its authority: DSAC, Medical Team, System, or combined System/DSAC. It calculates `pending_dsac`, `returned_by_dsac`, `pending_medical`, `pending_requirements`, `eligible`, `ineligible`, and `restricted` from authoritative backend records. No frontend may set the final result.

## Dashboard

The authenticated `/readiness` page provides scope-limited aggregate counts, readiness by School District, and a needs-attention list. It exposes medical status only, never confidential medical detail.

## Remaining decisions

- A formal athlete submission timestamp/state does not yet exist independently of the current review lifecycle; `EligibilityReview.pending` represents submitted/pending DSAC.
- “Profile complete” needs an owner-approved required-field list beyond the database's mandatory fields.
- Dental evaluation has no dedicated structured record; it remains within Medical Team notes until a dental data model is approved.
- Fine-grained manual permission overrides are not implemented; permissions derive from authoritative assignments/memberships, avoiding a second ad hoc role system.
