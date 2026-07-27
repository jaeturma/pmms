# Eligibility Documents & Manual Review

WP-02-09. PMMS records documents and human decisions — it never adjudicates eligibility
(automated rules are policy-dependent and deferred, per
`docs/11-backlog/phase-1-deferred-scope.md`).

## Data model

- `eligibility_documents` — per-athlete uploads via `FileUploadService`;
  `document_type` (`App\Enums\EligibilityDocumentType`: birth certificate, proof of
  enrollment, report card, parental consent, other). PDF/JPG/PNG, max 10 MB.
- `eligibility_reviews` — one per athlete per meet (unique), `status`
  (`App\Enums\EligibilityStatus`: `pending → approved | returned`), `reviewer_id`,
  `remarks`, `decided_at`. Decision fields are never mass-assignable.

## Flow

1. First document upload creates the review as **pending**.
2. Organizer/admin decides from the queue: **approve** (remarks optional) or **return**
   (remarks required — the officer must know what to fix).
3. Uploading to a **returned** review resubmits it: status back to pending, decision
   fields cleared, `eligibility.resubmitted` audited.
4. **Approved is terminal**: no further uploads, no re-decision, documents locked.

## Authorization (EligibilityReviewPolicy)

Viewers excluded (minor data). Officers upload/view only for their own delegation while
the meet's registration window is open (entries-style — the delegation need not be a
draft). Deciding is manager-only.

## Audit (integrity-critical + minor data)

`eligibility.document_uploaded|document_viewed|document_deleted|resubmitted|approved|
returned` — including **every document view**.

## Entries flag

Entry rows carry `eligibility_approved`; the entries page shows an "Eligibility pending"
badge next to athletes without an approved review. Per the WP this is a **flag, not a
block** — entries remain submittable.

## UI

`eligibility/index.tsx` — status-filterable, name-searchable queue (pending sorts
first), upload dialog (athlete + type + file), per-document authorized download links
(each view audited, now shown with an upload date) and pre-approval deletion,
approve/return dialogs with remarks. Three summary `StatCard`s (Pending review /
Approved / Returned) reflect the officer's whole scoped queue regardless of the
status/search filters applied to the list below them — the "at a glance" totals never
shift just because the visible rows are filtered. Sidebar entry: Eligibility.

### WP-08-06 visual alignment — restyle only, no automated rules

`docs/ui-ux/references/athlete-eligibility-checker.png` shows a fully automated
rule-checking flow (search an athlete, pick sport/event, click "Check Eligibility",
get an auto-computed PASS/FAIL per rule and an ELIGIBLE/INELIGIBLE verdict) that
directly conflicts with this page's real, deliberate manual-review workflow — the
"Out of scope" list above already named exactly this. WP-08-01's gap assessment
flagged the conflict and the owner was asked to choose a direction before WP-08-06
touched any code; the chosen scope was **restyle the real review queue only** — no
automated eligibility engine was built, and none of the reference's per-rule
checklist, PASS/FAIL logic, or auto-verdict exists anywhere in this app. What WP-08-06
did add, all backed by real data:

- A name search (`EligibilityController` now uses `SearchesAndPaginates`, searching
  `athlete.first_name`/`athlete.last_name` — the same pattern `EntryController`
  already used).
- The three summary `StatCard`s above (`counts`, computed once per request from a
  cloned base query before the status/search filters narrow it).
- Each uploaded document's row now shows its real upload date
  (`EligibilityDocument.created_at`) next to the existing download link.
- Status badges recolored with WP-08-02's semantic tokens (pending=warning,
  approved=success, returned=destructive) instead of the generic
  default/secondary/outline mapping.

## Out of scope (per WP)

Automated rules, age adjudication, medical clearance, protest workflows, OCR/AI.

**Division initiative:** the review queue's "school" column and the athlete
picker's label are sourced from `athlete.school` — the athlete's own home
school, decoupled from which delegation (school or municipality) registered
them. See `docs/delegations.md`.
