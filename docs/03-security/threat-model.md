# PMMS Threat Model

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [security-architecture.md](security-architecture.md) · [trust-boundaries-and-attack-surface.md](trust-boundaries-and-attack-surface.md) · [security-risk-register.md](security-risk-register.md)

This document scopes PMMS's threat actors, protected assets, and threat-modeling method. **This is not a formal certified security assessment** — it is an architecture-level threat model intended to guide control prioritization, per working rule 7 (Threat-Model Method: "Do not claim a formal certification assessment").

---

## 1. Threat Actors

| Actor | Motivation / Capability |
|---|---|
| Anonymous external attacker | Opportunistic exploitation of public-facing surfaces (public portal, public API) |
| Credential thief | Phishing, credential-stuffing, or password reuse targeting any PMMS account |
| Malicious insider | An authorized user (staff, official, committee member) deliberately abusing legitimate access |
| Curious insider | An authorized user accessing data outside their legitimate need-to-know without malicious intent to harm |
| Overprivileged administrator | A Platform/Security Administrator whose broad access is misused or accidentally exposes data |
| Compromised support account | A support-role credential taken over by an external actor |
| Compromised mobile device | A lost, stolen, or malware-infected Flutter device used for scoring/access-validation |
| Compromised scanner or venue device | A QR scanner, kiosk, or venue device physically or logically compromised |
| Malicious participant or delegation user | An athlete, coach, or delegation representative attempting to manipulate their own eligibility, entries, or results |
| Rogue technical official | An official abusing scoring or result-certification authority |
| Third-party service compromise | Compromise of an approved external service (email/SMS provider, future integration) |
| Supply-chain attacker | A compromised Composer/npm/Flutter dependency or GitHub Action |
| Automated bot | Scripted abuse of public registration, search, or API endpoints |
| API abuse actor | Excessive or malformed API usage against any exposed endpoint category |
| Data scraper | Bulk automated harvesting of public projection data |
| Ransomware actor | Attempting to encrypt or hold platform data hostage |
| Accidental user | A legitimate user causing harm through error, not malice (e.g., publishing an unapproved result) |
| Misconfigured service | A platform component (Redis, MinIO, Reverb) exposed or configured insecurely, creating unintentional risk |
| AI misuse or prompt-injection actor | An actor attempting to manipulate an AI-assisted feature into leaking data or bypassing controls |

## 2. Protected Assets

| Asset | Why It Matters |
|---|---|
| User credentials | Compromise enables impersonation of any authority the account holds |
| Athlete identities | Foundational personal data across nearly every workflow |
| Guardian details | Sensitive contact/relationship data for minors |
| Eligibility evidence | Restricted-tier documents underpinning a Critical high-integrity decision |
| Medical records | Highly Restricted-tier health data |
| Official scores | Critical high-integrity data — the input to every certified outcome |
| Certified results | Critical high-integrity data — the platform's core institutional-trust output |
| Protest evidence | Critical high-integrity data supporting adjudication |
| Medal tally | Critical, publicly visible high-integrity data |
| Accreditation credentials | Security-relevant — controls physical venue access |
| Access-control records | Evidence of who was where, when |
| Financial records | Institutional-accountability data |
| Audit events | The accountability mechanism for every other asset |
| Encryption keys | Compromise undermines every encryption-dependent control |
| Service credentials | Compromise enables lateral movement between platform components |
| Backups | A secondary copy of every asset above — an equally attractive target |
| Public reputation | DepEd's institutional trust in the platform itself |
| System availability | The platform's ability to function during a live, time-sensitive meet |

## 3. Threat-Modeling Method

### STRIDE Categories Applied to PMMS

| Category | PMMS-Specific Examples |
|---|---|
| Spoofing | Impersonating a user account, a device (scanner/encoder), or a service identity |
| Tampering | Altering a score, result, eligibility decision, or audit event outside the approved correction path |
| Repudiation | An actor denying they performed an action the platform cannot conclusively attribute to them |
| Information disclosure | Exposing medical, eligibility, guardian, or financial data to an unauthorized viewer |
| Denial of service | Overwhelming the public portal during a result announcement, degrading live scoring capacity |
| Elevation of privilege | A lower-privileged account gaining Platform/Security Administrator-equivalent access |

### Additional Considerations Beyond STRIDE

- **Privacy threats** — re-identification of masked data, excessive data collection, purpose creep, unauthorized secondary use.
- **Business-process abuse** — gaming the eligibility, entry, or protest process for competitive advantage.
- **Fraud** — falsified financial claims, falsified eligibility evidence, falsified medical clearance.
- **Insider misuse** — an authorized user acting within their technical access but outside their legitimate purpose.
- **Data poisoning** — deliberately submitting false data (scores, eligibility evidence) to corrupt downstream decisions.
- **AI-specific risks** — prompt injection, indirect prompt injection via untrusted document content, sensitive-data leakage through AI outputs, hallucinated rules presented as authoritative.
- **Offline synchronization abuse** — a compromised or malicious offline device submitting falsified provisional records, or exploiting the revocation-lag window.
- **Supply-chain compromise** — a compromised dependency introducing a backdoor or vulnerability into the codebase.

## 4. High-Priority Threat Scenarios (Illustrative, Not Exhaustive)

| Scenario | Actor | Primary Asset | Category |
|---|---|---|---|
| A stolen QR scanner is used to falsely validate access after credential revocation | Compromised scanner / device thief | Access-control records, venue security | Spoofing, tampering |
| A technical official alters a score after submission but before validation, exploiting a missing SoD check | Rogue technical official | Official scores | Tampering, elevation of privilege |
| A phishing attack compromises a Meet Director account, granting broad meet-scoped authority | Credential thief | Multiple (eligibility, results, assignments) | Spoofing, elevation of privilege |
| A public API endpoint is scraped in bulk to compile a database of minor athletes' personal data | Data scraper | Athlete identities, guardian details | Information disclosure |
| A compromised dependency introduces a backdoor during a routine `composer update` | Supply-chain attacker | Entire platform | Tampering, elevation of privilege |
| An AI-assisted eligibility-review feature is prompt-injected via a malicious uploaded document to leak other athletes' Restricted data | AI misuse actor | Eligibility evidence | Information disclosure |
| A support account with impersonation capability is compromised and used to approve a business transaction while impersonating another user | Compromised support account | Multiple high-integrity domains | Elevation of privilege, repudiation |
| An offline scoring device operating during a revocation-lag window is used by a since-revoked official to submit a provisional score | Malicious insider (revoked) | Official scores | Spoofing (stale trust) |

These scenarios illustrate why Sections 11–37 of the main document (authentication, authorization, privileged access, device security, audit) exist — each scenario maps to specific controls documented in this package.

## 5. Out of Scope for This Threat Model

- Physical venue security beyond device/credential controls (a DepEd/venue operational concern, not a PMMS software control).
- Formal penetration testing or red-team exercises (a future, evidence-generating activity — see [security-testing-and-assurance.md, Section "Penetration Testing Readiness"](security-testing-and-assurance.md#penetration-testing-readiness)).
- Certification against any specific standard (ISO/IEC 27001, etc.) — tracked as a candidate reference only, per [compliance-control-framework.md](compliance-control-framework.md).

## 6. Open Questions

See [security-open-decisions.md](security-open-decisions.md) for unresolved threat-model-dependent questions, including whether a formal, facilitated threat-modeling workshop (e.g., a structured STRIDE session per bounded context) is warranted before Phase 0.7.
