# Eligibility and accreditation gap analysis

PMMS already provided private eligibility-document uploads, manual athlete reviews, medical clearances, meet entries, sport assignments, audit logging, and issued accreditation cards. Those tables and workflows are retained.

The gaps were document-level verification metadata, four explicit athlete requirements, configurable cutoff/event limits, explainable rule evaluation and history, and a distinction between an issued meet ID card (`accreditations`) and a Technical Official's supporting credential. The latter is implemented as `technical_official_accreditations`; it uses the same private `file_uploads` storage and audited access pattern.

Eligibility is generated from normalized rule results rather than a boolean. A failed rule produces `ineligible`; unresolved requirements produce `pending_requirements`; only all-passed/not-applicable rules produce `eligible`.

## Rules requiring owner confirmation

- Exact age and grade ranges per SportCategory (fields exist but no values were invented).
- Whether rejected documents are terminal or may return to review; currently rejected is an ineligibility failure.
- Event limits per meet; no limit applies when unconfigured.
- Whether every meet requires personnel medical clearance; this follows the meet-level switch.
- DSAC/DSC granular permission names require a future permission system. Current policy preserves Admin/Organizer verification and delegation-scoped officer/coach visibility without granting overrides.
- Duplicate athlete registration is already prevented by globally unique LRN and athlete/event constraints; cross-record fuzzy identity detection remains a separate reviewed workflow.
