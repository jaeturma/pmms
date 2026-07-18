# Phase 3 Design Notes

Core flow (registration steps shipped in Phase 2; Phase 3 starts at scheduling and
accreditation):

```text
Meet Setup ✔ (WP-02-04)
→ Delegation Registration ✔ (WP-02-05)
→ Athlete and Coach Registration ✔ (WP-02-06/07)
→ Eligibility Validation ✔ (WP-02-09)
→ Event Entry ✔ (WP-02-08)
→ Venues & Scheduling (WP-03-01/02)
→ Accreditation (WP-03-03)
→ Tournament Operations (WP-03-04)
→ Results Validation (WP-03-05)
→ Medal Tally (WP-03-06)
→ Protests & Incidents (WP-03-07)
→ Final Reports & Dashboard (WP-03-08/09)
```

Important rules:

- MySQL is the source of truth.
- Results affect medal tally only after validation.
- Finalized results are not silently edited.
- Corrections require a reason and audit record.
- Public publication belongs to Phase 4.
- Executive dashboards are expanded in Phase 5.
- The web UI must be mobile responsive.
