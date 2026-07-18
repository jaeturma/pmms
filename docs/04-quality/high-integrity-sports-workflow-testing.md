# PMMS High-Integrity Sports Workflow Testing

**Status:** Draft Complete — Pending Quality, Engineering, Security, Domain, and Stakeholder Validation
**Related:** [../02-data/high-integrity-data-model.md](../02-data/high-integrity-data-model.md) · [../01-architecture/high-integrity-domain-rules.md](../01-architecture/high-integrity-domain-rules.md) · [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md)

This document defines a dedicated testing strategy for PMMS's highest-stakes workflows: eligibility, competition entries, draws/brackets/heats/lanes, scheduling, officials assignments, scoring, advancement, official results, protests, appeals, medal awards, team standings, and publication. **No sport-specific rule, scoring formula, eligibility threshold, deadline, or medal rule is invented anywhere in this document** — every expected outcome is marked as requiring an approved rule source, per working rules 18–19.

---

## 1. Governing Principle

Every scenario in this document that names a specific numeric outcome (a score value, a placement, a medal assignment) is **illustrative of the test's structure, not a source of truth for the actual sport rule**. Before any such test is implemented, its expected outcome must be validated against an approved rule source (DepEd/sports-governing-body documentation, tracked in [../03-security/policy-source-registry.md](../03-security/policy-source-registry.md)) by the Sports-rule validator role (per [quality-governance-and-ownership.md](quality-governance-and-ownership.md)) — never authored independently by an engineer or by this documentation.

## 2. Eligibility Testing

| Scenario | What to Verify |
|---|---|
| Requirement-set version | The correct eligibility requirement-set version is applied to a given athlete's review, per [../02-data/temporal-history-and-versioning-model.md, Section 5](../02-data/temporal-history-and-versioning-model.md#5-temporal-and-effective-dated-data) |
| Missing requirement | An incomplete submission is correctly held/rejected, not silently approved |
| Invalid evidence | Malformed or insufficient evidence is correctly flagged |
| Duplicate athlete | A duplicate athlete identity is correctly detected, per [../02-data/identity-resolution-and-duplicate-management.md](../02-data/identity-resolution-and-duplicate-management.md) |
| Conflicting identity | Conflicting identity claims are correctly routed to manual review, never auto-resolved |
| Return for correction | A case returned for correction correctly preserves its history and re-enters review upon resubmission |
| Approval | Approval requires the correct approver authority, per [../01-architecture/separation-of-duties-matrix.md, SOD-01](../01-architecture/separation-of-duties-matrix.md) |
| Rejection | A rejection is correctly recorded with its required reason |
| Reopen | Reopening a decided case is a distinctly-flagged, elevated-audit event |
| Override | An override (if the eventual policy permits one) is correctly restricted and audited |
| Appeal where applicable | An eligibility appeal path, if the eventual policy defines one, correctly routes to the appropriate authority |
| Expired assignment | A reviewer/approver whose assignment has expired cannot act on a case |
| Reviewer-approver separation | SOD-01 is structurally enforced — the same individual cannot both submit/review and approve the same case |
| Sensitive-data access | Evidence access is correctly restricted to the review chain |
| Public-status limit | The public projection shows only an eligibility status, never evidence or deliberation detail |
| Audit history | The full decision history (submission → review → decision → any reopen) is correctly preserved and traceable |
| AI suggestion without autonomous decision | An AI-assisted flag (e.g., "incomplete submission") never becomes an approval/rejection without human action, per [../03-security/ai-security-privacy-and-governance.md, Section 3](../03-security/ai-security-privacy-and-governance.md#3-ai-action-boundaries-absolute-prohibitions) |

This section governs BC-09 (Eligibility and Clearance), blocked pending [../00-product/open-decisions.md, OD-07](../00-product/open-decisions.md#od-07--eligibility-authority) for the underlying policy — test scenarios are structured now; specific decision-authority values remain pending that resolution.

## 3. Scoring Testing

| Scenario | What to Verify |
|---|---|
| Valid score | A correctly-formed score submission is accepted |
| Invalid score | A malformed/out-of-range score is rejected |
| Boundary score | Minimum/maximum valid values behave correctly at the edge |
| Precision | Score precision/rounding matches the sport's defined measurement standard, per [../02-data/database-naming-and-design-standards.md, Section 6](../02-data/database-naming-and-design-standards.md#6-score-measurement-and-timing-standards) |
| Device source | The originating device/source is correctly recorded with the score |
| Offline draft | A score captured offline is correctly treated as Provisional until server-validated |
| Duplicate submission | A retried/duplicate submission doesn't create a duplicate score record |
| Stale submission | A submission referencing an outdated tournament/entry state is correctly rejected |
| Correction | A score correction creates a new version referencing the original — never a destructive overwrite |
| Validation | The validation step correctly checks the score against expected structural/business rules |
| Locking | Entry/score locking at the appropriate tournament stage behaves correctly |
| Concurrent encoder | Two encoders submitting for the same event/heat are correctly serialized, not silently overwriting one another |
| Unauthorized sport | A scorer without an active assignment for the specific sport cannot submit |
| Unauthorized event | Same, scoped to the specific event within a sport |
| Superseded score | A superseded score is correctly excluded from downstream calculation while remaining in history |
| Audit | Every submission and correction is audit-relevant |
| No direct destructive overwrite | Restated absolutely — no code path allows a score to be silently replaced without a version trail |

This section governs BC-15 (Scoring), Critical tier — SOD-02 (entering scorer ≠ validator) is structurally tested, never merely policy-documented.

## 4. Official Result Testing

| Scenario | What to Verify |
|---|---|
| Generation from valid scores | A result is only generated from a validated score set |
| Validation | The validation step correctly confirms score-set completeness/correctness before certification is possible |
| Certification | Certification requires the correct certifier authority and produces an immutable version |
| Hold | A protest-triggered hold correctly blocks publication/further progression |
| Supersession | A corrected result creates a new version referencing the original |
| Publication | Publication requires the correct Publication-tier authority, per [../01-architecture/phase-0.3-access-and-assignment-architecture.md, Section 19](../01-architecture/phase-0.3-access-and-assignment-architecture.md#19-approval-authority-levels) |
| Unpublication | A withdrawn publication correctly updates the public projection and cache |
| Correction | A post-publication correction correctly triggers republication of the corrected version |
| Protest interaction | A held result correctly blocks publication until the protest resolves |
| Public projection update | The public projection reflects only certified, published results |
| Cache invalidation | A correction/unpublication correctly invalidates any cached public view |
| Version history | The full certification/correction history remains traceable |
| Separation of certifier and publisher | Where the eventual policy distinguishes these roles, the separation is structurally tested |
| Audit | Every certification, hold, correction, and publication action is audit-relevant |

This section governs BC-16 (Official Results), Critical tier, blocked pending [../00-product/open-decisions.md, OD-08](../00-product/open-decisions.md#od-08--official-result-approval-chain).

## 5. Protest and Appeal Testing

| Scenario | What to Verify |
|---|---|
| Filing | A protest is correctly filed against a specific result, with required evidence |
| Deadline rule placeholder | Any deadline check references a policy-sourced value, never an invented one — marked pending until [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) resolves |
| Evidence | Evidence attachment/handling follows the standard file-upload/classification lifecycle |
| Conflict of interest | The certifier of the disputed result is correctly recused from resolving the protest against it, per [../01-architecture/separation-of-duties-matrix.md, SOD-03](../01-architecture/separation-of-duties-matrix.md) |
| Result hold | Filing correctly places the disputed result on hold |
| Review assignment | The protest is correctly routed to an authorized, non-conflicted reviewer |
| Decision | The decision is correctly recorded with its reasoning |
| Appeal | Where the eventual policy defines an appeal tier, escalation behaves correctly |
| Release of hold | Resolution correctly releases or confirms the hold |
| Result correction | A protest upholding a correction correctly triggers the Official Result correction path (Section 4) |
| Public status | The public projection reflects "under review" or resolved status appropriately, never exposing internal deliberation |
| Audit | Every filing, assignment, decision, and hold-release is audit-relevant |
| Unauthorized access | Only authorized parties can view protest evidence/deliberation |

**Do not invent deadlines or authorities** — restated absolutely; every test scenario referencing a specific deadline or decision-authority is explicitly marked pending [OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority) resolution. This section governs BC-17 (Protest and Appeals), Critical tier.

## 6. Medal-Tally Testing

| Scenario | What to Verify |
|---|---|
| Certified-results-only input | Medal tally calculation reads exclusively from certified, published Official Results — never raw scores, per [../02-data/transaction-concurrency-and-locking.md, Section 3](../02-data/transaction-concurrency-and-locking.md#3-result-and-tally-integrity-persistence-expression) |
| Gold, silver, and bronze mapping | Placement-to-medal mapping is verified against an approved rule source, never invented |
| Ties where approved | Tie-handling behavior is verified only against an approved rule; where no rule is confirmed, the test is marked pending |
| Recalculation | A source-result correction correctly triggers tally recalculation |
| Correction | A tally correction creates a new version referencing the original |
| Certification | Certification requires the correct certifier authority, distinct from the encoder (SOD-04) |
| Publication | Publication follows the same authority/audit discipline as Official Results |
| Snapshot | A published tally snapshot remains reproducible even after later corrections |
| Superseded result | A superseded source result is correctly excluded from current tally, retained in history |
| Held result | A protest-held result is correctly excluded from tally until resolved |
| Team standing | Team-standing aggregation correctly derives from individual/event medal results |
| Concurrent update | Simultaneous recalculation triggers are correctly serialized |
| Public projection | The public medal-tally view reflects only certified, published data |
| Audit | Every recalculation, correction, and certification is audit-relevant |

**Do not invent medal rules** — restated absolutely; this section governs BC-18 (Medal Tally and Team Standings), Critical/derived tier, blocked pending [../00-product/open-decisions.md, OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules) and SOD-04.

## 7. Accreditation and QR Testing

| Scenario | What to Verify |
|---|---|
| Eligibility for issuance | A credential is only issued to a participant meeting the (policy-sourced) issuance criteria |
| Credential generation | Credential data is correctly generated and linked to the participant |
| QR token | The QR token carries no sensitive meaning and is not predictable/enumerable, per [../02-data/identifier-and-reference-strategy.md, Section 3](../02-data/identifier-and-reference-strategy.md#3-public-identifier-rules) |
| Print | Print generation produces correct, complete credential output |
| Replacement | A replacement credential correctly invalidates the prior one |
| Revocation | Revocation correctly and immediately invalidates the credential for validation purposes |
| Expiry | An expired credential is correctly rejected at validation |
| Offline validation | Validation against a cached credential-validity snapshot behaves correctly within its bounded offline window, per [../01-architecture/offline-authorization-model.md](../01-architecture/offline-authorization-model.md) |
| Duplicate scan | A repeated scan is handled per the specific workflow's single-use/multi-use policy |
| Replay | A replayed scan token is correctly rejected where single-use semantics apply |
| Wrong venue | A credential scanned at an unauthorized venue is correctly rejected |
| Wrong time | A credential scanned outside its authorized time window is correctly rejected |
| Wrong entitlement | A credential lacking the required entitlement (e.g., meal access) is correctly rejected for that specific gate |
| Lost credential | A reported-lost credential is correctly revoked and, where policy allows, replaced |
| Device revocation | A revoked scanner device cannot validate any credential |
| Access override | An override (if implemented) is correctly restricted to authorized roles and fully audited, per SOD-05 |
| Audit | Every issuance, revocation, and validation attempt (success and failure) is audit-relevant |
| Public-ID protection | No internal sequential ID is exposed as or usable as a credential reference |

This section governs BC-19 (Accreditation) and BC-20 (Access Validation), both Critical tier.

## 8. Medical Testing

| Scenario | What to Verify |
|---|---|
| Summary access | The minimal clearance-status flag is correctly the only data crossing into Eligibility |
| Detailed access | Full medical detail is correctly restricted to Medical Team roles only |
| Emergency access | An emergency-access invocation is correctly restricted, reasoned, and reviewed |
| Encounter creation | A medical encounter record is correctly created and classified Highly Restricted |
| Restricted attachment | Medical document attachments follow the full file-upload/classification lifecycle |
| Offline incident | An offline-captured medical incident correctly syncs and is treated as Provisional pending review where applicable |
| Sync | Medical data sync respects the minimal-offline-replication rule — only an emergency-relevant flag ever replicates |
| Export denial | Ordinary export paths correctly deny medical-data export outside the elevated authorization process |
| Support-access denial | Support roles cannot access medical detail through the ordinary support-access path |
| Break-glass review | Any emergency-access invocation into medical data receives mandatory post-use review, if break-glass is implemented |
| Audit | Every access beyond the minimal exposure is audit-relevant |
| Public non-disclosure | Medical data never appears in any public projection |
| AI restriction | Medical data is correctly excluded from AI-assisted processing pending [OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions) |

**Do not validate clinical protocols** — restated absolutely; this document tests data handling, access control, and audit behavior only, never medical correctness or clinical appropriateness, which is outside PMMS's and this documentation's scope. This section governs BC-21 (Medical Operations), Critical/Highly Restricted tier, blocked pending [OD-15](../00-product/open-decisions.md#od-15--medical-data-handling).

## 9. Finance Testing

| Scenario | What to Verify |
|---|---|
| Amount precision | Monetary values use fixed-precision decimal storage, never floating-point, per [../02-data/database-naming-and-design-standards.md, Section 5](../02-data/database-naming-and-design-standards.md#5-monetary-value-standards) |
| Currency | Currency is explicit and consistent |
| Encoder and approver separation | SOD-06 is structurally tested — the recorder and approver are never the same individual |
| Supporting documents | Financial attachments follow the standard file-upload/classification lifecycle |
| Adjustment | An adjustment creates a new version referencing the original — never a destructive overwrite |
| Reversal | A reversal is correctly recorded as its own auditable transaction, not a silent deletion |
| Approval | Approval requires the correct approver authority and produces an audit event |
| Export | Financial exports require the elevated authorization named in Phase 0.6 |
| Restricted access | Financial data access is correctly restricted to Finance Committee roles and relevant oversight |
| Audit | Every recording, adjustment, reversal, and approval is audit-relevant |
| No public disclosure except approved summary | Only committee-approved aggregate summaries, if any, are ever candidates for public exposure |

This section governs BC-26 (Finance Operations).

## 10. Draws, Brackets, Heats, Lanes, and Scheduling Testing

| Scenario | What to Verify |
|---|---|
| Draw generation | A draw/bracket is generated per the configured tournament format, correctly seeding entries |
| Advancement | Advancement between rounds correctly follows certified results |
| Schedule conflicts | Overlapping venue/official/athlete assignments are correctly detected |
| Officials assignment | An assignment respects the assigned official's availability and authorization for the specific sport/event |
| Reschedule | A schedule change correctly propagates to affected participants/officials and public projections |

Sport-specific format rules governing draws/brackets/heats/lanes/seeding are validated against approved sources by the Sports-rule validator, per Section 1 — never invented by this documentation or by engineering.

## 11. Open Questions

See [quality-open-decisions.md](quality-open-decisions.md) — every scenario above referencing an unresolved Phase 0.1/0.2/0.3 open decision (OD-07, OD-08, OD-09, OD-12, OD-15, OD-29) remains structurally defined but not executable with real expected values until that decision resolves.
