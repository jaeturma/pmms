# PMMS Phase 1 Work Package Catalog

**Status:** Draft Complete — Pending Backlog, Architecture, Product, Quality, and Engineering Review
**Related:** [phase-0.14-phase-1-implementation-backlog.md](phase-0.14-phase-1-implementation-backlog.md), [phase-1-epic-catalog.md](phase-1-epic-catalog.md)

Every Phase 1 work package, with ID, title, epic, purpose, complexity, priority, dependencies, architecture readiness, release group, pilot relevance, status, and a link to its full document. All 155 work packages begin at **Status: Planned — Not Started**. "Dependencies" here lists only hard predecessor work packages; full dependency classification (Hard/Soft/Deferred) is in each work package's own Section 6 and in [phase-1-dependency-map.md](phase-1-dependency-map.md).

## EPIC-01 — Engineering Foundation and Repository Baseline (Release A)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-01-01 | Repository and Framework Baseline Verification | Small | P1 | None | Ready for Implementation | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-01-repository-and-framework-baseline-verification.md) |
| WP-01-02 | Backend Quality Tool Verification | Small | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-02-backend-quality-tool-verification.md) |
| WP-01-03 | Frontend Quality Tool Verification | Small | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-03-frontend-quality-tool-verification.md) |
| WP-01-04 | Authentication Baseline Verification | Small | P1 | WP-01-01 | Ready with Constraints | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-04-authentication-baseline-verification.md) |
| WP-01-05 | Flutter Workspace and Quality Baseline Verification | Medium | P1 | WP-01-01 | Requires Decision (Flutter absent) | Required (pilot only) | [link](work-packages/EPIC-01-engineering-foundation/WP-01-05-flutter-workspace-and-quality-baseline-verification.md) |
| WP-01-06 | Environment Configuration and Secret Hygiene Baseline | Small | P1 | WP-01-01 | Ready with Constraints | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-06-environment-configuration-and-secret-hygiene-baseline.md) |
| WP-01-07 | Git Workflow and Repository Governance Baseline | Medium | P1 | WP-01-01 | Ready with Constraints | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-07-git-workflow-and-repository-governance-baseline.md) |
| WP-01-08 | Development Documentation and AI Workspace Alignment | Small | P1 | WP-01-01..07 | Ready for Implementation | Required | [link](work-packages/EPIC-01-engineering-foundation/WP-01-08-development-documentation-and-ai-workspace-alignment.md) |

## EPIC-02 — Modular Monolith and Application Architecture Foundation (Release A)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-02-01 | Modular Monolith Directory and Namespace Foundation | Medium | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-01-modular-monolith-directory-and-namespace-foundation.md) |
| WP-02-02 | Shared Kernel and Common Contracts Foundation | Medium | P1 | WP-02-01 | Ready for Implementation | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-02-shared-kernel-and-common-contracts-foundation.md) |
| WP-02-03 | Application Command and Query Conventions | Medium | P1 | WP-02-02 | Ready for Implementation | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-03-application-command-and-query-conventions.md) |
| WP-02-04 | Domain Event and Integration Contract Conventions | Medium | P1 | WP-02-02 | Ready with Constraints | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-04-domain-event-and-integration-contract-conventions.md) |
| WP-02-05 | Repository, Transaction, and Persistence Conventions | Medium | P1 | WP-02-02 | Ready with Constraints | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-05-repository-transaction-and-persistence-conventions.md) |
| WP-02-06 | Architecture Dependency Rules and Fitness-Test Readiness | Medium | P1 | WP-02-01 | Ready with Constraints | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-06-architecture-dependency-rules-and-fitness-test-readiness.md) |
| WP-02-07 | Error Handling and Exception Translation Foundation | Small | P1 | WP-02-02 | Ready for Implementation | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-07-error-handling-and-exception-translation-foundation.md) |
| WP-02-08 | Correlation and Request Context Foundation | Small | P1 | WP-02-02 | Ready for Implementation | Required | [link](work-packages/EPIC-02-modular-monolith-architecture/WP-02-08-correlation-and-request-context-foundation.md) |

## EPIC-03 — Identity, Authentication, and Session Foundation (Release B)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-03-01 | Core User Account Model Review and Normalization | Medium | P1 | WP-02-01, WP-02-05 | Ready with Constraints | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-01-core-user-account-model-review-and-normalization.md) |
| WP-03-02 | Authentication Flow Baseline | Medium | P1 | WP-03-01 | Ready with Constraints | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-02-authentication-flow-baseline.md) |
| WP-03-03 | Session Security and Login Protection | Medium | P1 | WP-03-02 | Ready with Constraints | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-03-session-security-and-login-protection.md) |
| WP-03-04 | Account Status and Revocation Foundation | Small | P1 | WP-03-01 | Ready with Constraints | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-04-account-status-and-revocation-foundation.md) |
| WP-03-05 | Password Reset and Verification Baseline | Small | P1 | WP-03-02 | Ready for Implementation | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-05-password-reset-and-verification-baseline.md) |
| WP-03-06 | Service and Device Identity Architectural Skeleton | Medium | P1 | WP-03-01 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-06-service-and-device-identity-architectural-skeleton.md) |
| WP-03-07 | Authentication Audit and Security Events | Medium | P1 | WP-03-02, WP-06-01, WP-06-03 | Ready with Constraints | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-07-authentication-audit-and-security-events.md) |
| WP-03-08 | Authentication Test Coverage | Medium | P1 | WP-03-01..07 | Ready for Implementation | Required | [link](work-packages/EPIC-03-identity-authentication-session/WP-03-08-authentication-test-coverage.md) |

## EPIC-04 — Organization, Meet Context, and Tenant-Ready Ownership Foundation (Release B)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-04-01 | Organization Hierarchy Foundation | Medium | P1 | WP-02-05 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-01-organization-hierarchy-foundation.md) |
| WP-04-02 | Meet Record and Lifecycle Foundation | Medium | P1 | WP-04-01 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-02-meet-record-and-lifecycle-foundation.md) |
| WP-04-03 | User Organization Membership Foundation | Medium | P1 | WP-03-01, WP-04-01 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-03-user-organization-membership-foundation.md) |
| WP-04-04 | User Meet Access Foundation | Medium | P1 | WP-04-02, WP-04-03 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-04-user-meet-access-foundation.md) |
| WP-04-05 | Trusted Context Resolution | Medium | P1 | WP-04-03, WP-04-04 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-05-trusted-context-resolution.md) |
| WP-04-06 | Context Propagation for HTTP and Inertia | Medium | P1 | WP-04-05 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-06-context-propagation-for-http-and-inertia.md) |
| WP-04-07 | Context Propagation for Jobs, Events, and Audit | Medium | P1 | WP-04-05, WP-09-02 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-07-context-propagation-for-jobs-events-and-audit.md) |
| WP-04-08 | Tenant-Ready Ownership and Isolation Conventions | Medium | P1 | WP-04-01, WP-04-02 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-08-tenant-ready-ownership-and-isolation-conventions.md) |
| WP-04-09 | Cross-Context Isolation Tests | Medium | P2 | WP-04-01..08 | Ready with Constraints | Required | [link](work-packages/EPIC-04-organization-meet-context/WP-04-09-cross-context-isolation-tests.md) |

## EPIC-05 — Role, Permission, Scope, and Assignment Foundation (Release B)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-05-01 | Role Catalog Foundation | Medium | P1 | WP-02-05 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-01-role-catalog-foundation.md) |
| WP-05-02 | Permission Catalog Foundation | Medium | P1 | WP-02-05 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-02-permission-catalog-foundation.md) |
| WP-05-03 | Role-Permission Assignment Foundation | Medium | P1 | WP-05-01, WP-05-02 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-03-role-permission-assignment-foundation.md) |
| WP-05-04 | Scope Model Foundation | Medium | P1 | WP-04-01, WP-04-02 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-04-scope-model-foundation.md) |
| WP-05-05 | Assignment Model Foundation | Large | P1 | WP-05-03, WP-05-04, WP-04-03 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-05-assignment-model-foundation.md) |
| WP-05-06 | Time-Valid Assignment Rules | Medium | P1 | WP-05-05 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-06-time-valid-assignment-rules.md) |
| WP-05-07 | Authorization Decision Service | Large | P1 | WP-05-05, WP-05-06 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-07-authorization-decision-service.md) |
| WP-05-08 | Laravel Policies and Application Authorization Conventions | Medium | P1 | WP-05-07 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-08-laravel-policies-and-application-authorization-conventions.md) |
| WP-05-09 | Explicit Denial and Security Hold Foundation | Medium | P1 | WP-05-07 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-09-explicit-denial-and-security-hold-foundation.md) |
| WP-05-10 | Separation-of-Duties Foundation | Medium | P1 | WP-05-05, WP-05-07 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-10-separation-of-duties-foundation.md) |
| WP-05-11 | Permission and Assignment Administration UI Foundation | Large | P2 | WP-05-07, WP-10-01..09, WP-11-01..10 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-11-permission-and-assignment-administration-ui-foundation.md) |
| WP-05-12 | Authorization Test Matrix | Large | P2 | WP-05-01..10 | Ready with Constraints | Required | [link](work-packages/EPIC-05-role-permission-scope-assignment/WP-05-12-authorization-test-matrix.md) |

## EPIC-06 — Audit, Activity History, and Security Event Foundation (Release C)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-06-01 | Audit Event Model Foundation | Medium | P1 | WP-02-05 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-01-audit-event-model-foundation.md) |
| WP-06-02 | Activity History Model Foundation | Medium | P1 | WP-02-05 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-02-activity-history-model-foundation.md) |
| WP-06-03 | Security Event Model Foundation | Medium | P1 | WP-02-05 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-03-security-event-model-foundation.md) |
| WP-06-04 | Actor, Effective User, Device, Context, and Correlation Metadata | Medium | P1 | WP-06-01, WP-02-08 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-04-actor-effective-user-device-context-and-correlation-metadata.md) |
| WP-06-05 | Sensitive View and Export Audit Conventions | Medium | P1 | WP-06-01 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-05-sensitive-view-and-export-audit-conventions.md) |
| WP-06-06 | Audit Recording Service | Large | P1 | WP-06-01..05 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-06-audit-recording-service.md) |
| WP-06-07 | Audit Query and Review Interface Foundation | Medium | P2 | WP-06-06, WP-10-01..09 | Ready for Implementation | Required (pilot only) | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-07-audit-query-and-review-interface-foundation.md) |
| WP-06-08 | Audit Retention and Access Rules | Small | P1 | WP-06-06 | Ready with Constraints | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-08-audit-retention-and-access-rules.md) |
| WP-06-09 | Audit Integrity and Coverage Tests | Medium | P2 | WP-06-01..08 | Ready for Implementation | Required | [link](work-packages/EPIC-06-audit-activity-security-events/WP-06-09-audit-integrity-and-coverage-tests.md) |

## EPIC-07 — Reference Data and Configuration Foundation (Release C)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-07-01 | Reference Data Architecture Foundation | Medium | P1 | WP-02-05 | Ready for Implementation | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-01-reference-data-architecture-foundation.md) |
| WP-07-02 | Organization Reference Types | Small | P1 | WP-07-01, WP-04-01 | Ready for Implementation | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-02-organization-reference-types.md) |
| WP-07-03 | Meet Reference Types | Small | P1 | WP-07-01, WP-04-02 | Ready for Implementation | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-03-meet-reference-types.md) |
| WP-07-04 | Sport and Event Reference Skeleton | Medium | P1 | WP-07-01 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-04-sport-and-event-reference-skeleton.md) |
| WP-07-05 | Venue Reference Skeleton | Small | P1 | WP-07-01 | Ready for Implementation | Required (pilot only) | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-05-venue-reference-skeleton.md) |
| WP-07-06 | Committee Reference Skeleton | Small | P1 | WP-07-01 | Ready for Implementation | Required (pilot only) | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-06-committee-reference-skeleton.md) |
| WP-07-07 | Reference Data Versioning and Activation | Medium | P1 | WP-07-01 | Ready for Implementation | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-07-reference-data-versioning-and-activation.md) |
| WP-07-08 | Configuration Classification and Validation | Medium | P1 | WP-02-02 | Ready for Implementation | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-08-configuration-classification-and-validation.md) |
| WP-07-09 | Feature Flag Readiness | Small | P1 | WP-07-08 | Ready with Constraints | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-09-feature-flag-readiness.md) |
| WP-07-10 | Reference Data Administration UI Foundation | Medium | P2 | WP-07-01..07, WP-10-01..09 | Ready for Implementation | Required (pilot only) | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-10-reference-data-administration-ui-foundation.md) |
| WP-07-11 | Reference Data Tests | Medium | P2 | WP-07-01..09 | Ready for Implementation | Required | [link](work-packages/EPIC-07-reference-data-configuration/WP-07-11-reference-data-tests.md) |

## EPIC-08 — File, Document, and Object Storage Foundation (Release C)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-08-01 | Storage Configuration Abstraction | Medium | P1 | WP-01-06 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-01-storage-configuration-abstraction.md) |
| WP-08-02 | File Metadata Model Foundation | Medium | P1 | WP-08-01, WP-02-05 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-02-file-metadata-model-foundation.md) |
| WP-08-03 | Temporary Upload Foundation | Medium | P1 | WP-08-02 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-03-temporary-upload-foundation.md) |
| WP-08-04 | File Validation and Classification | Medium | P1 | WP-08-02, WP-14-01 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-04-file-validation-and-classification.md) |
| WP-08-05 | Object Key and Tenant-Ready Ownership Conventions | Medium | P1 | WP-08-02, WP-04-08 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-05-object-key-and-tenant-ready-ownership-conventions.md) |
| WP-08-06 | Authorized Download and Signed Access | Medium | P1 | WP-08-05, WP-05-07 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-06-authorized-download-and-signed-access.md) |
| WP-08-07 | File Version and Replacement Foundation | Medium | P2 | WP-08-02 | Ready for Implementation | Required (pilot only) | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-07-file-version-and-replacement-foundation.md) |
| WP-08-08 | Quarantine and Malware-Scan Readiness | Medium | P2 | WP-08-04 | Requires Technical Spike | Deferred to pilot | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-08-quarantine-and-malware-scan-readiness.md) |
| WP-08-09 | Missing and Orphan Object Reconciliation Readiness | Small | P2 | WP-08-02 | Ready with Constraints | Deferred to pilot | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-09-missing-and-orphan-object-reconciliation-readiness.md) |
| WP-08-10 | File Audit Events | Small | P1 | WP-08-02, WP-06-06 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-10-file-audit-events.md) |
| WP-08-11 | File Security and Integration Tests | Medium | P2 | WP-08-01..10 | Ready for Implementation | Required | [link](work-packages/EPIC-08-file-document-object-storage/WP-08-11-file-security-and-integration-tests.md) |

## EPIC-09 — Queue, Scheduler, Notification, and Real-Time Foundation (Release D)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-09-01 | Redis Connection and Runtime Verification | Small | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-01-redis-connection-and-runtime-verification.md) |
| WP-09-02 | Queue and Horizon Baseline | Medium | P1 | WP-09-01 | Ready for Implementation | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-02-queue-and-horizon-baseline.md) |
| WP-09-03 | Queue Naming and Routing Conventions | Small | P1 | WP-09-02 | Ready for Implementation | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-03-queue-naming-and-routing-conventions.md) |
| WP-09-04 | Job Idempotency and Safe Payload Conventions | Medium | P1 | WP-09-02 | Ready with Constraints | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-04-job-idempotency-and-safe-payload-conventions.md) |
| WP-09-05 | Failed Job and Replay Governance Foundation | Medium | P2 | WP-09-02 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-05-failed-job-and-replay-governance-foundation.md) |
| WP-09-06 | Scheduler and Heartbeat Foundation | Small | P1 | WP-09-02 | Ready for Implementation | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-06-scheduler-and-heartbeat-foundation.md) |
| WP-09-07 | Notification Intent and In-App Notification Foundation | Medium | P1 | WP-09-02, WP-06-01 | Ready with Constraints | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-07-notification-intent-and-in-app-notification-foundation.md) |
| WP-09-08 | Email Delivery Development Baseline | Small | P1 | WP-09-07 | Ready for Implementation | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-08-email-delivery-development-baseline.md) |
| WP-09-09 | Reverb and Broadcast Baseline | Medium | P1 | WP-01-01 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-09-reverb-and-broadcast-baseline.md) |
| WP-09-10 | Private Channel Authorization Foundation | Medium | P1 | WP-09-09, WP-05-07 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-10-private-channel-authorization-foundation.md) |
| WP-09-11 | Reconnection and State Refresh Foundation | Medium | P2 | WP-09-10 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-11-reconnection-and-state-refresh-foundation.md) |
| WP-09-12 | Queue, Scheduler, Notification, and Reverb Tests | Large | P2 | WP-09-01..11 | Ready with Constraints | Required | [link](work-packages/EPIC-09-queue-scheduler-notification-realtime/WP-09-12-queue-scheduler-notification-and-reverb-tests.md) |

## EPIC-10 — Backend API, Inertia, and Frontend Application Foundation (Release D)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-10-01 | API Response and Error Contract Foundation | Medium | P1 | WP-02-07 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-01-api-response-and-error-contract-foundation.md) |
| WP-10-02 | Validation Error Contract Foundation | Small | P1 | WP-10-01 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-02-validation-error-contract-foundation.md) |
| WP-10-03 | Inertia Shared Props and Context Foundation | Medium | P1 | WP-04-06, WP-05-07 | Ready with Constraints | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-03-inertia-shared-props-and-context-foundation.md) |
| WP-10-04 | Sensitive Prop Minimization Rules | Small | P1 | WP-10-03, WP-14-01 | Ready with Constraints | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-04-sensitive-prop-minimization-rules.md) |
| WP-10-05 | Frontend Permission and Capability Contract | Medium | P1 | WP-10-03, WP-05-07 | Ready with Constraints | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-05-frontend-permission-and-capability-contract.md) |
| WP-10-06 | Inertia Navigation and Flash Message Foundation | Small | P1 | WP-10-03 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-06-inertia-navigation-and-flash-message-foundation.md) |
| WP-10-07 | Pagination, Filtering, and Sorting Contract | Medium | P1 | WP-10-01 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-07-pagination-filtering-and-sorting-contract.md) |
| WP-10-08 | Form Submission and Conflict Handling Foundation | Medium | P1 | WP-10-02 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-08-form-submission-and-conflict-handling-foundation.md) |
| WP-10-09 | Loading, Empty, Error, and Permission-Denied State Foundation | Medium | P1 | WP-10-05, WP-11-11 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-09-loading-empty-error-and-permission-denied-state-foundation.md) |
| WP-10-10 | Frontend Integration Tests | Medium | P2 | WP-10-01..09 | Ready for Implementation | Required | [link](work-packages/EPIC-10-api-inertia-frontend-foundation/WP-10-10-frontend-integration-tests.md) |

## EPIC-11 — PMMS Arena Web Design System Foundation (Release D)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-11-01 | Design Token Implementation Foundation | Medium | P1 | WP-01-03 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-01-design-token-implementation-foundation.md) |
| WP-11-02 | Light, Dark, and Semantic Theme Foundation | Medium | P1 | WP-11-01 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-02-light-dark-and-semantic-theme-foundation.md) |
| WP-11-03 | Typography and Iconography Foundation | Small | P1 | WP-11-01 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-03-typography-and-iconography-foundation.md) |
| WP-11-04 | Application Shell and Navigation Foundation | Large | P1 | WP-11-02, WP-11-03, WP-10-05 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-04-application-shell-and-navigation-foundation.md) |
| WP-11-05 | Organization and Meet Context Switcher Foundation | Medium | P1 | WP-11-04, WP-04-04 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-05-organization-and-meet-context-switcher-foundation.md) |
| WP-11-06 | Page Header and Content Layout Foundation | Medium | P1 | WP-11-04 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-06-page-header-and-content-layout-foundation.md) |
| WP-11-07 | Form Control Foundation | Medium | P1 | WP-11-01, WP-10-02 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-07-form-control-foundation.md) |
| WP-11-08 | Button, Badge, Alert, and Feedback Foundation | Medium | P1 | WP-11-01 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-08-button-badge-alert-and-feedback-foundation.md) |
| WP-11-09 | Table and Pagination Foundation | Medium | P1 | WP-11-01, WP-10-07 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-09-table-and-pagination-foundation.md) |
| WP-11-10 | Dialog, Drawer, and Confirmation Foundation | Medium | P1 | WP-11-01 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-10-dialog-drawer-and-confirmation-foundation.md) |
| WP-11-11 | Loading, Empty, Error, Offline, and Stale-State Components | Medium | P1 | WP-11-01 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-11-loading-empty-error-offline-and-stale-state-components.md) |
| WP-11-12 | Accessibility and Keyboard Baseline | Large | P1 | WP-11-04..11 | Requires Decision (DX-01) | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-12-accessibility-and-keyboard-baseline.md) |
| WP-11-13 | Design-System Documentation and Test Foundation | Medium | P2 | WP-11-01..12 | Ready with Constraints | Required | [link](work-packages/EPIC-11-web-design-system/WP-11-13-design-system-documentation-and-test-foundation.md) |

## EPIC-12 — Flutter Application Foundation (Release E)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-12-01 | Flutter Project Baseline Verification | Medium | P1 | WP-01-05 | Requires Decision (Flutter absent) | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-01-flutter-project-baseline-verification.md) |
| WP-12-02 | Flutter Architecture and Feature Folder Foundation | Medium | P1 | WP-12-01 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-02-flutter-architecture-and-feature-folder-foundation.md) |
| WP-12-03 | Flutter Environment and API Configuration Foundation | Medium | P1 | WP-12-02, WP-10-01 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-03-flutter-environment-and-api-configuration-foundation.md) |
| WP-12-04 | Secure Storage Foundation | Medium | P1 | WP-12-02 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-04-secure-storage-foundation.md) |
| WP-12-05 | Authentication Shell Foundation | Medium | P1 | WP-12-03, WP-12-04, WP-03-02 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-05-authentication-shell-foundation.md) |
| WP-12-06 | Assignment-Aware Mobile Home Skeleton | Medium | P2 | WP-12-05, WP-05-07 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-12-flutter-foundation/WP-12-06-assignment-aware-mobile-home-skeleton.md) |
| WP-12-07 | PMMS Arena Flutter Theme Foundation | Medium | P1 | WP-12-02, WP-11-01 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-07-pmms-arena-flutter-theme-foundation.md) |
| WP-12-08 | Mobile Network and Connectivity State Foundation | Medium | P1 | WP-12-02 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-08-mobile-network-and-connectivity-state-foundation.md) |
| WP-12-09 | Offline Data Store Readiness | Large | P2 | WP-12-08 | Requires Technical Spike | Required (pilot only) | [link](work-packages/EPIC-12-flutter-foundation/WP-12-09-offline-data-store-readiness.md) |
| WP-12-10 | Sync Queue Architectural Skeleton | Large | P2 | WP-12-09 | Requires Technical Spike | Required (pilot only) | [link](work-packages/EPIC-12-flutter-foundation/WP-12-10-sync-queue-architectural-skeleton.md) |
| WP-12-11 | Flutter Logging and Error Handling | Small | P1 | WP-12-02 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-11-flutter-logging-and-error-handling.md) |
| WP-12-12 | Flutter Test Foundation | Medium | P2 | WP-12-01..11 | Ready with Constraints | Required | [link](work-packages/EPIC-12-flutter-foundation/WP-12-12-flutter-test-foundation.md) |

## EPIC-13 — Observability, Health, and Operational Readiness Foundation (Release E)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-13-01 | Structured Logging Foundation | Medium | P1 | WP-02-08 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-01-structured-logging-foundation.md) |
| WP-13-02 | Request and Correlation Identifier Foundation | Small | P1 | WP-02-08 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-02-request-and-correlation-identifier-foundation.md) |
| WP-13-03 | Application Health Endpoint Foundation | Small | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-03-application-health-endpoint-foundation.md) |
| WP-13-04 | Readiness and Dependency Health Foundation | Medium | P1 | WP-13-03 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-04-readiness-and-dependency-health-foundation.md) |
| WP-13-05 | Queue and Scheduler Health Foundation | Small | P1 | WP-13-04, WP-09-02 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-05-queue-and-scheduler-health-foundation.md) |
| WP-13-06 | Reverb Health Foundation | Small | P1 | WP-13-04, WP-09-09 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-13-observability-health-operations/WP-13-06-reverb-health-foundation.md) |
| WP-13-07 | Storage Health Foundation | Small | P1 | WP-13-04, WP-08-01 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-07-storage-health-foundation.md) |
| WP-13-08 | Safe Error Reporting Foundation | Medium | P1 | WP-13-01, WP-14-03 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-08-safe-error-reporting-foundation.md) |
| WP-13-09 | Operational Diagnostics Interface | Medium | P2 | WP-13-03..08 | Ready for Implementation | Required (pilot only) | [link](work-packages/EPIC-13-observability-health-operations/WP-13-09-operational-diagnostics-interface.md) |
| WP-13-10 | Health and Observability Tests | Medium | P2 | WP-13-01..09 | Ready for Implementation | Required | [link](work-packages/EPIC-13-observability-health-operations/WP-13-10-health-and-observability-tests.md) |

## EPIC-14 — Data Protection, Privacy, and Secure Development Foundation (Release E)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-14-01 | Data Classification Constants and Contracts | Small | P1 | WP-02-02 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-01-data-classification-constants-and-contracts.md) |
| WP-14-02 | Sensitive Data Masking Foundation | Medium | P1 | WP-14-01 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-02-sensitive-data-masking-foundation.md) |
| WP-14-03 | Log Redaction Foundation | Medium | P1 | WP-14-01, WP-13-01 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-03-log-redaction-foundation.md) |
| WP-14-04 | Secure Export Readiness | Small | P2 | WP-14-01 | Ready with Constraints | Required (pilot only) | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-04-secure-export-readiness.md) |
| WP-14-05 | Rate Limiting Baseline | Small | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-05-rate-limiting-baseline.md) |
| WP-14-06 | Security Header and Cookie Review | Small | P1 | WP-01-01 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-06-security-header-and-cookie-review.md) |
| WP-14-07 | CSRF, Session, and Request Protection Review | Small | P1 | WP-03-03 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-07-csrf-session-and-request-protection-review.md) |
| WP-14-08 | Secret and Environment Hygiene Checks | Small | P1 | WP-01-06 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-08-secret-and-environment-hygiene-checks.md) |
| WP-14-09 | Support and Impersonation Restriction Foundation | Medium | P1 | WP-05-07, WP-06-06 | Ready with Constraints | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-09-support-and-impersonation-restriction-foundation.md) |
| WP-14-10 | Privacy and Security Regression Tests | Medium | P2 | WP-14-01..09 | Ready for Implementation | Required | [link](work-packages/EPIC-14-data-protection-secure-development/WP-14-10-privacy-and-security-regression-tests.md) |

## EPIC-15 — Foundation Integration, Validation, and Release Readiness (Release F)

| ID | Title | Complexity | Priority | Dependencies | Readiness | Pilot Relevance | Doc |
|---|---|---|---|---|---|---|---|
| WP-15-01 | Foundation Architecture Consistency Review | Medium | P2 | All EPIC-01..14 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-01-foundation-architecture-consistency-review.md) |
| WP-15-02 | Foundation Database and Migration Review | Medium | P2 | All EPIC-01..09 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-02-foundation-database-and-migration-review.md) |
| WP-15-03 | Foundation Authorization Review | Medium | P2 | WP-05-01..12 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-03-foundation-authorization-review.md) |
| WP-15-04 | Foundation Audit and Privacy Review | Medium | P2 | WP-06-01..09, WP-14-01..10 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-04-foundation-audit-and-privacy-review.md) |
| WP-15-05 | Foundation Frontend Accessibility Review | Medium | P2 | WP-11-01..13 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-05-foundation-frontend-accessibility-review.md) |
| WP-15-06 | Foundation Queue and Real-Time Review | Medium | P2 | WP-09-01..12 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-06-foundation-queue-and-real-time-review.md) |
| WP-15-07 | Foundation Flutter Review | Medium | P2 | WP-12-01..12 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-07-foundation-flutter-review.md) |
| WP-15-08 | Foundation Performance Baseline | Medium | P2 | WP-15-01..07 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-08-foundation-performance-baseline.md) |
| WP-15-09 | Foundation Security Review | Medium | P2 | WP-14-01..10, WP-03-01..08 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-09-foundation-security-review.md) |
| WP-15-10 | Foundation Documentation Review | Small | P2 | WP-01-08, WP-15-01..09 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-10-foundation-documentation-review.md) |
| WP-15-11 | Foundation UAT and Developer Acceptance | Large | P2 | WP-15-01..10 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-11-foundation-uat-and-developer-acceptance.md) |
| WP-15-12 | Phase 1 Foundation Sign-Off Package | Medium | P2 | WP-15-01..11 | Verification Only | Required | [link](work-packages/EPIC-15-foundation-integration-validation/WP-15-12-phase-1-foundation-sign-off-package.md) |

## Summary

155 work packages total. 0 Extra Large (per working rule 55 — all Extra Large candidates were split during epic decomposition, matching the candidate lists provided). Complexity distribution: approximately 30% Small, 55% Medium, 15% Large. Priority distribution: 2 P0-adjacent governance dependencies (tracked externally, not as work packages), ~110 P1, ~45 P2. No P3/P4/P5 work package exists in this backlog — those priorities describe deferred scope, tracked in [phase-1-deferred-scope.md](phase-1-deferred-scope.md), not Phase 1 work.
