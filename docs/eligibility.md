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

`eligibility/index.tsx` — status-filterable queue (pending sorts first), upload dialog
(athlete + type + file), per-document authorized download links (each view audited) and
pre-approval deletion, approve/return dialogs with remarks. Sidebar entry: Eligibility.

## Out of scope (per WP)

Automated rules, age adjudication, medical clearance, protest workflows, OCR/AI.
