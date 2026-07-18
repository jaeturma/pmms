# PMMS Cryptography, Key, and Secret Management

**Status:** Draft Complete — Pending Security, Privacy, Legal, Audit, Data Governance, and Engineering Validation
**Related:** [../02-data/audit-and-security-data-architecture.md, Section 3](../02-data/audit-and-security-data-architecture.md#3-encryption-and-sensitive-data) · [../02-data/data-open-decisions.md](../02-data/data-open-decisions.md)

This document defines cryptographic control requirements, key-management principles, and secret-management principles. **No cryptographic algorithm, key-management vendor, or secret-management platform is selected here**, per working rule 32 — every control below is a requirement a future implementation phase satisfies, not an implementation.

---

## 1. Cryptographic Architecture

| Need | Direction |
|---|---|
| TLS | All network traffic in transit is encrypted — the specific TLS version/cipher-suite policy is an infrastructure-phase decision, bounded by "no outdated/deprecated protocol version" |
| Password hashing | One-way, salted, adaptive hashing (the Laravel-default bcrypt algorithm, currently configured at `BCRYPT_ROUNDS=12`, is the confirmed starter-kit foundation) — passwords are never stored reversibly |
| Token hashing | API/session tokens are stored hashed, never in plaintext, mirroring password-hashing discipline |
| Field encryption | A candidate control for the highest-sensitivity fields (Highly Restricted tier), pending [../02-data/data-open-decisions.md, PD-06](../02-data/data-open-decisions.md#pd-06--field-level-encryption-candidate-list) — no specific field list or algorithm is finalized here |
| Object encryption | Server-side encryption at rest for MinIO-stored objects, per [infrastructure-runtime-and-network-security.md, Section 3](infrastructure-runtime-and-network-security.md#3-minio-and-object-storage-security) |
| Backup encryption | Every backup (database, object storage) is encrypted at rest, per [../02-data/backup-restore-and-data-recovery.md, Section 2](../02-data/backup-restore-and-data-recovery.md#2-backup-requirements) |
| Mobile storage encryption | Restated from [mobile-device-and-offline-security.md, Section 1](mobile-device-and-offline-security.md#1-flutter-security) |
| Signed URLs | Time-boxed, cryptographically signed MinIO access URLs, per [infrastructure-runtime-and-network-security.md, Section 3](infrastructure-runtime-and-network-security.md#3-minio-and-object-storage-security) |
| Webhook signatures | Per [application-api-and-client-security.md, Section 3](application-api-and-client-security.md#3-webhook-security) |
| QR token protection | Access-validation QR tokens are generated with sufficient entropy to resist guessing/enumeration; token design (opaque reference vs. signed payload) is an implementation-phase decision |
| Audit integrity | Hash-chaining or digital-signature evaluation for tamper-evidence, per [audit-and-security-event-architecture.md, Section 3](audit-and-security-event-architecture.md#3-audit-integrity) — evaluated, not committed |
| Export encryption | Sensitive exports are encrypted at rest and, where transmitted, in transit |
| Key rotation | Every cryptographic key has a defined rotation capability, even before a specific cadence is fixed |
| Key separation | Encryption keys are stored and managed separately from the data they protect, per Section 2 |
| Algorithm agility | The architecture does not hard-code assumptions that would make a future algorithm change (e.g., in response to a discovered weakness) prohibitively difficult |

**Explicitly not decided here:** the specific field-level encryption algorithm, the specific hashing algorithm beyond the already-framework-confirmed bcrypt, and any specific key-management product.

## 2. Key Management

| Requirement | Direction |
|---|---|
| Key categories | Application key (Laravel `APP_KEY`), database encryption keys (if field-level encryption is adopted), object-storage encryption keys, signing keys (webhooks, tokens), backup encryption keys |
| Key ownership | Every key category has a designated owning role (Security owner or Infrastructure owner, per [security-architecture.md, Section 3](security-architecture.md#3-security-governance-model)) |
| Environment separation | Every environment (Local/Development/Staging/Production) uses its own distinct keys — never a shared key across environments |
| Key generation | Generated using cryptographically secure randomness; never a predictable or reused value |
| Storage | Keys are never stored alongside the data they protect, nor committed to source control |
| Rotation | Every key category has a rotation capability; specific cadence is an open decision |
| Revocation | A compromised key can be revoked/replaced without an extended platform outage |
| Backup | Keys are backed up separately from data backups, with access to the key backup itself tightly restricted |
| Recovery | A documented (future-phase) recovery process exists for key loss that does not depend on a single point of failure |
| Access control | Key access is restricted to the narrowest set of roles/services genuinely requiring it |
| Dual control for critical keys | The most critical keys (e.g., a master encryption key) are candidates for a dual-control/split-knowledge scheme — evaluated, not committed |
| Audit | Every key access, rotation, or revocation event is audit-relevant |
| Key compromise response | A suspected key compromise triggers immediate rotation and a review of all data/operations the key protected, mirroring the service-compromise response in [../01-architecture/device-and-service-identity-model.md, Section 8](../01-architecture/device-and-service-identity-model.md#8-service-compromise) |
| Key-version tracking | Rotated keys retain a version identity so historically-encrypted data remains decryptable during a transition period |
| Data re-encryption readiness | A rotated key's predecessor data is re-encryptable under the new key over time — a candidate migration capability, not committed to a specific mechanism |

## 3. Secret Management

### Secrets Inventory

Application key · database credentials · Redis credentials · MinIO credentials · Reverb credentials · mail (email/SMS) credentials · push-notification credentials · AI-service credentials · webhook secrets · device credentials · API client secrets · encryption keys.

### Rules

1. **Never commit secrets** — no secret value is ever placed in source control, including in example/template files (the current `.env.example` correctly contains only blank/placeholder values — this must remain true for every future secret added).
2. **Never place privileged secrets in client applications** — no server-side secret is ever embedded in the React/Inertia bundle or the Flutter app binary, restated from [application-api-and-client-security.md, Section 5](application-api-and-client-security.md#5-react-and-inertia-security) and [mobile-device-and-offline-security.md, Section 1](mobile-device-and-offline-security.md#1-flutter-security).
3. **Use environment-specific secrets** — no secret is shared across Local/Development/Staging/Production.
4. **Rotate** — every secret has a rotation capability.
5. **Revoke** — a suspected-compromised secret is immediately revocable.
6. **Audit access** — access to a secret-management system (once one exists) is itself audit-relevant.
7. **Avoid sharing through chat or documentation** — no secret value is ever pasted into a chat tool, ticket, or documentation file, including this one.
8. **Use a secret-management platform later** — a dedicated secret-management tool (vault-style) is anticipated for Staging/Production; not yet selected or implemented — a later infrastructure-phase decision.
9. **Scan repositories for accidental disclosure** — automated secret-scanning is a candidate control for the CI/CD pipeline once one exists, per [secure-development-lifecycle.md, Section 4](secure-development-lifecycle.md#4-dependency-and-supply-chain-security).

### Verification Performed This Phase

Per working rule and the Initial Repository Inspection, `.env.example` was reviewed and contains no real secret values (all AWS/database/mail credentials are blank placeholders); `.gitignore` correctly excludes `.env`, `/auth.json`, and `storage/*.key`. No accidentally-committed secret was found in this inspection. This finding is current as of this phase's inspection date and should be re-verified at each future phase, not assumed to remain true indefinitely.

## 4. Open Questions

See [security-open-decisions.md](security-open-decisions.md) — notably field-level encryption scope and algorithm (mirrors [../02-data/data-open-decisions.md, PD-06/PD-07](../02-data/data-open-decisions.md#pd-06--field-level-encryption-candidate-list)), key-rotation cadence, secret-management platform selection, and whether audit-integrity hash-chaining is adopted.
