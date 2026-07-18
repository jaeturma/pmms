# WP-03-03 — Accreditation & ID Printing

## Purpose
Complete this work package for the PMMS Division Edition. Keep implementation practical
for a Schools Division Office. Build only what is required for a maintainable
production-quality system.

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Accreditation status per athlete and personnel for a meet. Gate (server-enforced):
  delegation approved; athletes additionally need an approved eligibility review.
- Accredit / revoke are manager-only decisions, audited (`accreditation.*`), with a
  per-delegation accreditation view showing who is eligible-but-not-yet-accredited.
- Printable ID cards (print CSS, no PDF library): photo, name, school, meet, role or
  athlete grade/division, accreditation number — batch print per delegation and single
  print. Card views restricted like roster data (managers + assigned officers) and
  audited as sensitive views. No QR codes at MVP.
- Tests: gate enforcement, authorization scoping, audit records, card data.

## Out of Scope
QR/barcode validation, scanning, lanyard/badge stock formats beyond one clean card
layout, committee/media credentialing (no such registries yet).

## Deliverables
- Updated source code
- Updated documentation (docs/accreditation.md)
- Completion report
- Git status summary

## Acceptance Criteria
- Repository inspected first.
- No unrelated features added.
- Tests and quality checks completed.
- Documentation updated.
- No secrets exposed.
- No commit or push performed.

## Completion Report
Include:
1. Repository findings
2. Files created
3. Files modified
4. Test results
5. Quality results
6. Remaining issues
7. Recommended next work package

Next:
WP-03-04 — Tournament & Match Management
