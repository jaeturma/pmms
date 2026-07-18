# WP-02-09 — Eligibility Documents & Manual Review

## Purpose
Complete this work package for the PMMS Division Edition. Keep implementation practical
for a Schools Division Office. Eligibility decisions are always made by a human reviewer
— PMMS records documents and decisions, it never adjudicates (policy automation is
deferred per docs/11-backlog/phase-1-deferred-scope.md).

## Tasks
- Inspect the repository before making changes.
- Implement only the scope of this work package.
- Preserve architecture and coding standards.
- Update documentation where necessary.
- Run backend and frontend quality checks.
- Do not commit or push changes.

## Scope
- Eligibility document upload per athlete (birth certificate, enrollment proof, etc.)
  reusing `FileUploadService` with a document-type field; officers upload for their own
  delegation's athletes.
- `eligibility_reviews` record per athlete per meet: status
  (`pending → approved | returned`), reviewer, remarks, decided_at. Returned items can be
  resubmitted.
- Organizer/admin review queue page (pending list, document viewer via authorized
  download, approve/return with remarks).
- Every upload, view, and decision audited (minor data + integrity-critical).
- Entries for an athlete may be flagged (not blocked) while eligibility is pending —
  simple visual indicator only.
- Tests: upload scoping, review authorization, status flow, resubmission, audit records.

## Out of Scope
Automated eligibility rules, age-computation adjudication, medical clearance, protest
workflows, document OCR/AI.

## Deliverables
- Updated source code and migrations
- Updated documentation
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
WP-02-10 — Registration Views & Search
