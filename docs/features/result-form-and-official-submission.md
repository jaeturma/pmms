# Result Form and Official Submission

## Purpose

PMMS keeps one structured `EventResult`. A generated Result Form is a printable representation of that record; a scanned signed copy is a protected supporting attachment. Generating, printing, or uploading the form never makes a result official.

## Workflow

```text
ENCODED draft
  -> generate/print versioned Result Form
  -> upload signed Result Form for the same version
  -> SUBMITTED
  -> Event Secretariat VALIDATED or RETURNED
  -> Event Secretariat OFFICIAL
  -> public results and medal tally
```

Returned and reopened results increment `event_results.version`. A signed attachment records its `result_version`; older files remain stored and are never overwritten. Only the current signed form matching the current result version satisfies submission.

## Authorization

Form generation, upload, and submission require an active assignment for the result's exact MeetSport as one of:

- Tournament Manager
- Assistant Tournament Manager
- Tournament Secretary
- Tournament ICT

Technical Official assignment alone does not grant administrative Result Form access. Administrators retain emergency system authority.

Review, return, validation, official publication, and protected attachment review require active membership in the result meet's management team with source code `EVENT_SECRETARIAT`. Reopening additionally permits Super Administrator. Every check includes the result's meet and sport; a matching sport in another meet does not authorize access.

## Storage and attachment records

`result_attachments` references the existing `file_uploads` metadata and configured storage disk. It stores attachment type, result version, SHA-256 checksum, uploader, current flag, and notes. File bytes remain in filesystem/object storage, never a database BLOB.

Allowed signed-form formats are PDF, JPG, JPEG, and PNG, subject to `uploads.max_kb`. Downloads use a result-specific protected route. Signed documents are not exposed through public result routes.

For DdOPAA 2026, `config('pmms.results.signed_result_form_required')` defaults to true and can be controlled with:

```text
PMMS_SIGNED_RESULT_FORM_REQUIRED=true
PMMS_RESULT_DOCUMENTS_PUBLIC=false
```

The second setting is reserved for an explicit future public-document policy. Current implementation keeps every signed form internal regardless of the setting.

## Result identification and versions

Forms display a deterministic reference such as `DDOPAA2026-BASKETBALL-000123`, result version, and generation timestamp. Generation writes `form_generated_version` and `form_generated_at` and records `result_form.generated`. It does not change result status, official timestamps, standings, or medals.

The print view uses the existing result placements, event/sport/category, schedule, venue, athlete, school, and delegation information. This keeps the form synchronized with the structured result rather than creating a second editable record.

## Audit events

Implemented events include:

- `result_form.generated`
- `result_attachment.uploaded`
- `result_attachment.replaced`
- `result_attachment.viewed`
- `result.submitted`
- `result.resubmitted`
- `result.returned`
- `result.validated`
- `result.made_official`
- `result.reopened`

Audit context contains result ID/reference, sport, version, status, attachment ID where applicable, and required workflow reasons. File content and authentication secrets are never included.

## Official-only protection

Public portal queries and `MedalTallyService` select only `ResultStatus::Official`. Encoded, submitted, returned, validated-but-not-official, reopened, and cancelled records do not affect official standings, rankings, or medal counts.

Official results and their attachments are locked from normal sport-personnel modification. Correction begins through the audited reopen action with a required reason; the prior official form and attachment remain preserved.

