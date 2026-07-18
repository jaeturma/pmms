# Phase 3 Design Notes

Core flow:

```text
Meet Setup
→ Delegation Registration
→ Athlete and Coach Registration
→ Eligibility Validation
→ Accreditation
→ Event Entry
→ Scheduling
→ Tournament Operations
→ Results Validation
→ Medal Tally
→ Final Reports
```

Important rules:

- MySQL is the source of truth.
- Results affect medal tally only after validation.
- Finalized results are not silently edited.
- Corrections require a reason and audit record.
- Public publication belongs to Phase 4.
- Executive dashboards are expanded in Phase 5.
- The web UI must be mobile responsive.
