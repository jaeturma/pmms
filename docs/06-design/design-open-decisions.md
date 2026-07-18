# PMMS Design System, UX, Accessibility, and Cross-Platform Experience — Open Decisions

**Status:** Draft Complete — Pending Product, UX, Accessibility, Domain, and Engineering Validation
**Related:** [experience-vision-and-design-principles.md](experience-vision-and-design-principles.md) · [../05-devops/devops-open-decisions.md](../05-devops/devops-open-decisions.md) · [../04-quality/quality-open-decisions.md](../04-quality/quality-open-decisions.md)

This document tracks every unresolved Phase 0.9 decision using Decision ID prefix `DX-` (Design eXperience), distinct from Phase 0.1's `OD-`, Phase 0.2's `DD-`, Phase 0.3's `AD-`, Phase 0.4's `RD-`, Phase 0.5's `PD-`, Phase 0.6's `SD-`, Phase 0.7's `QD-`, and Phase 0.8's `DV-` series. Each entry follows the established format: Question / Areas Affected / Why It Matters / Options / Recommended Direction / Evidence Required / Decision Owner / Target Phase / Status.

---

### DX-01 — WCAG Conformance Target

- **Question:** Does PMMS commit to WCAG 2.1/2.2 Level A, AA, or AAA?
- **Areas affected:** [accessibility-architecture.md](accessibility-architecture.md), every component-level document
- **Why it matters:** Determines the specific bar every design-quality gate (per [ux-research-validation-and-quality-gates.md, Section 5](ux-research-validation-and-quality-gates.md#5-design-quality-gates-in-the-release-pipeline)) checks against.
- **Options:** Level A (minimum) · Level AA (common institutional/government baseline) · Level AAA (highest, often impractical for full-application coverage).
- **Recommended direction:** Level AA, consistent with common government-platform practice — not asserted as achieved, only recommended as the target.
- **Evidence required:** Accessibility-specialist and DepEd-stakeholder confirmation.
- **Decision owner:** Accessibility specialist + Product owner
- **Target phase:** 0.10+
- **Status:** Open

### DX-02 — Branding Palette Approval

- **Question:** Is the candidate navy/maroon/gold palette direction approved as PMMS's actual brand identity?
- **Areas affected:** [color-theme-and-surface-system.md, Section 2](color-theme-and-surface-system.md#2-branding-direction-candidate-requires-validation)
- **Why it matters:** Blocks finalizing `--primary` and every brand-carrying token in the existing `app.css` foundation.
- **Options:** Approve the candidate direction · propose an alternative · defer to a formal branding exercise.
- **Recommended direction:** None — explicitly a candidate pending DepEd/stakeholder branding validation, not a design-team decision alone.
- **Evidence required:** DepEd communications/branding stakeholder sign-off.
- **Decision owner:** Product owner + DepEd Leadership
- **Target phase:** 0.10+
- **Status:** Open

### DX-03 — Responsive Breakpoint Finalization

- **Question:** What are the final pixel breakpoints for each responsive layout range?
- **Areas affected:** [responsive-touch-keyboard-and-device-behavior.md, Section 1](responsive-touch-keyboard-and-device-behavior.md#1-responsive-architecture)
- **Why it matters:** Every adaptive component (tables, forms, navigation) depends on agreed breakpoints.
- **Options:** Adopt Tailwind 4's defaults unmodified · customize based on real-device testing.
- **Recommended direction:** Start with Tailwind defaults, adjust only where real-device/pilot testing demonstrates a specific need.
- **Evidence required:** Device inventory from [../05-devops/device-service-account-and-credential-operations.md](../05-devops/device-service-account-and-credential-operations.md) and pilot testing.
- **Decision owner:** React design-system engineer
- **Target phase:** 0.10+
- **Status:** Open

### DX-04 — Browser and Android Version Support Baseline

- **Question:** What is the minimum supported browser and Android OS version?
- **Areas affected:** [public-portal-kiosk-scoreboard-and-display-experience.md, Section 5](public-portal-kiosk-scoreboard-and-display-experience.md#5-device-and-browser-compatibility)
- **Why it matters:** Affects both web feature availability and Flutter build configuration.
- **Options:** Evergreen-browsers-only (simpler, excludes some older devices) · extended support for older Android versions (broader reach, more testing burden).
- **Recommended direction:** Evergreen browsers as the baseline, with Android minimum-version determined once device procurement (per [../05-devops/device-service-account-and-credential-operations.md](../05-devops/device-service-account-and-credential-operations.md)) is known.
- **Evidence required:** Device procurement decisions, pilot device inventory.
- **Decision owner:** Flutter design-system engineer
- **Target phase:** 0.10+
- **Status:** Open

### DX-05 — Charting Library Selection (React and Flutter)

- **Question:** Which charting library implements [dashboard-table-chart-and-data-visualization-standards.md, Section 2](dashboard-table-chart-and-data-visualization-standards.md#2-chart-standards)?
- **Areas affected:** [dashboard-table-chart-and-data-visualization-standards.md](dashboard-table-chart-and-data-visualization-standards.md)
- **Why it matters:** Must support the existing `--chart-*` token structure and accessible-alternative requirement.
- **Options:** Not evaluated in this phase.
- **Recommended direction:** None — an implementation-phase decision.
- **Evidence required:** Accessibility support comparison across candidate libraries.
- **Decision owner:** React design-system engineer
- **Target phase:** 0.10+
- **Status:** Open

### DX-06 — Storybook / Component-Preview Tooling Adoption

- **Question:** Is Storybook (or an equivalent) adopted for component documentation and preview?
- **Areas affected:** [design-system-governance-documentation-and-versioning.md, Section 4](design-system-governance-documentation-and-versioning.md#4-design-system-documentation-requirements)
- **Why it matters:** Not created in this documentation-only phase, per working rule 10 — a future tooling decision.
- **Options:** Storybook · a lighter-weight documentation-only approach · none initially.
- **Recommended direction:** Deferred until component volume justifies dedicated tooling investment.
- **Evidence required:** Component-count growth over early implementation.
- **Decision owner:** React design-system engineer
- **Target phase:** 0.10+
- **Status:** Open

### DX-07 — High-Contrast Theme: Separate Theme or Overlay

- **Question:** Is High-Contrast a fully separate theme or a set of contrast-boosting overrides on Light/Dark?
- **Areas affected:** [color-theme-and-surface-system.md, Section 4](color-theme-and-surface-system.md#4-theme-architecture)
- **Why it matters:** Affects token architecture complexity.
- **Options:** Separate theme (cleaner separation, more tokens to maintain) · override layer (simpler, less flexible).
- **Recommended direction:** Override layer initially, given the existing token structure's simplicity — revisit if accessibility testing reveals a need for more differentiated treatment.
- **Evidence required:** Accessibility testing findings.
- **Decision owner:** Accessibility specialist
- **Target phase:** 0.10+
- **Status:** Open

### DX-08 — Screenshot-Prevention Adoption for Highly Restricted Mobile Screens

- **Question:** Is OS-level screenshot prevention implemented for medical/Highly Restricted mobile screens?
- **Areas affected:** [privacy-security-and-sensitive-data-experience.md, Section 1](privacy-security-and-sensitive-data-experience.md#1-privacy-aware-experience)
- **Why it matters:** Mirrors [../03-security/mobile-device-and-offline-security.md, Section 1](../03-security/mobile-device-and-offline-security.md#1-flutter-security)'s "evaluated, not committed" status.
- **Options:** Implement · rely on policy/training alone.
- **Recommended direction:** Evaluate during Flutter implementation, given the platform-specific technical complexity of enforcing this reliably.
- **Evidence required:** Flutter platform-capability assessment.
- **Decision owner:** Flutter design-system engineer + Security owner
- **Target phase:** 0.10+
- **Status:** Open

### DX-09 — Digital Acknowledgment / E-Signature Mechanism for Certification

- **Question:** Is a formal digital-acknowledgment mechanism adopted for certification actions?
- **Areas affected:** [high-integrity-approval-certification-and-publication-ux.md, Section 2](high-integrity-approval-certification-and-publication-ux.md#2-approval-and-certification-interfaces)
- **Why it matters:** Would strengthen non-repudiation readiness (per [../03-security/security-architecture.md, Section 1](../03-security/security-architecture.md#1-security-objectives)) beyond an ordinary confirmation click.
- **Options:** Ordinary confirmation dialog · a dedicated e-signature-style mechanism.
- **Recommended direction:** Ordinary confirmation as the launch baseline; revisit if a specific institutional requirement for stronger non-repudiation emerges.
- **Evidence required:** DepEd institutional-record requirements.
- **Decision owner:** Security owner + Product owner
- **Target phase:** 0.10+
- **Status:** Open

### DX-10 — SoD-Conflict Interface Treatment

- **Question:** When a user has a separation-of-duties conflict, does the interface hard-block the action or show a warning with an audited override?
- **Areas affected:** [high-integrity-approval-certification-and-publication-ux.md, Section 2](high-integrity-approval-certification-and-publication-ux.md#2-approval-and-certification-interfaces)
- **Why it matters:** [../01-architecture/separation-of-duties-matrix.md](../01-architecture/separation-of-duties-matrix.md) treats most SoD entries as hard rules with no exception — the interface should reflect this.
- **Options:** Hard block (no override) · block with audited override for a narrow subset.
- **Recommended direction:** Hard block for SOD-01 through SOD-11 exactly as documented — no override, consistent with "None identified" appearing in most SoD entries' Possible Exception column.
- **Evidence required:** None — restates existing Phase 0.3 documentation.
- **Decision owner:** Security owner
- **Target phase:** 0.10+
- **Status:** Open (implementation-sequencing only, direction already clear)

### DX-11 — Draft Collaboration and Co-Editing Rules

- **Question:** Can two users co-edit the same draft record, and if so, how are conflicts shown?
- **Areas affected:** [form-validation-draft-and-workflow-experience.md, Section 4](form-validation-draft-and-workflow-experience.md#4-draft-and-autosave-experience)
- **Why it matters:** Affects committee-report and long-form-workflow design specifically.
- **Options:** Single-editor-at-a-time (simpler) · true co-editing (more complex, higher value for some workflows).
- **Recommended direction:** Single-editor-at-a-time initially, given implementation complexity of real-time co-editing.
- **Evidence required:** Pilot feedback on whether co-editing is genuinely needed.
- **Decision owner:** UX lead
- **Target phase:** Post-pilot
- **Status:** Open

### DX-12 — Localization Commitment Timing

- **Question:** Does PMMS commit to an additional language (e.g., Filipino) as an active near-term requirement, or remain readiness-only?
- **Areas affected:** [form-validation-draft-and-workflow-experience.md, Section 8](form-validation-draft-and-workflow-experience.md#8-localization-readiness)
- **Why it matters:** Affects content-authoring workload and component internationalization support.
- **Options:** Readiness-only (current direction) · active near-term commitment.
- **Recommended direction:** Readiness-only, mirroring Phase 0.8's "readiness, not commitment" treatment of multi-organization support.
- **Evidence required:** DepEd/stakeholder requirement confirmation.
- **Decision owner:** Product owner
- **Target phase:** Post-pilot
- **Status:** Open

### DX-13 — AI Use-Case Approval for Initial Implementation (Cross-Reference)

- **Question:** Which specific AI-assisted interface patterns (per [ai-assisted-experience-architecture.md, Section 1](ai-assisted-experience-architecture.md#1-allowed-ai-assisted-interface-patterns)) are built first?
- **Areas affected:** [ai-assisted-experience-architecture.md](ai-assisted-experience-architecture.md)
- **Why it matters:** Blocked directly on [../03-security/security-open-decisions.md, SD-20](../03-security/security-open-decisions.md#sd-20--ai-use-case-approval-for-initial-implementation) and [Phase 0.1 OD-29](../00-product/open-decisions.md#od-29--ai-service-restrictions).
- **Options:** None yet defined.
- **Recommended direction:** Mirrors SD-20's recommendation — start with the lowest-risk candidates once OD-29 resolves.
- **Evidence required:** OD-29 resolution.
- **Decision owner:** Product owner + Security owner
- **Target phase:** 0.10+, blocked
- **Status:** Open — blocked, mirrors SD-20/OD-29

### DX-14 — Sport-Specific Bracket/Heat Interface Validation (Cross-Reference)

- **Question:** Which sports require a dedicated (non-generic) bracket/heat/lane interface variant?
- **Areas affected:** [sports-tournament-scoring-and-results-components.md, Section 12](sports-tournament-scoring-and-results-components.md#12-sport-specific-requirement-validation)
- **Why it matters:** Blocked on [Phase 0.1 OD-10](../00-product/open-decisions.md#od-10--sports-rule-source) sports-rule-source resolution.
- **Options:** None yet defined.
- **Recommended direction:** None — genuinely dependent on OD-10.
- **Evidence required:** OD-10 resolution, sports-specialist review.
- **Decision owner:** Sports-rule validator (role TBD) + UX lead
- **Target phase:** 0.10+, blocked
- **Status:** Open — blocked, mirrors OD-10

### DX-15 — Medal-Tally Tie-Breaking Display Treatment

- **Question:** How does the interface display a tie-breaking outcome once medal-tally rules are confirmed?
- **Areas affected:** [sports-tournament-scoring-and-results-components.md, Section 7](sports-tournament-scoring-and-results-components.md#7-medal-tally-standards)
- **Why it matters:** Blocked on [Phase 0.1 OD-12](../00-product/open-decisions.md#od-12--medal-tally-rules).
- **Options:** None yet defined.
- **Recommended direction:** None — genuinely dependent on OD-12.
- **Evidence required:** OD-12 resolution.
- **Decision owner:** Sports-rule validator (role TBD)
- **Target phase:** 0.10+, blocked
- **Status:** Open — blocked, mirrors OD-12

### DX-16 — Protest Deadline and Authority Display (Cross-Reference)

- **Question:** What deadline and authority information does the protest-filing interface display?
- **Areas affected:** [high-integrity-approval-certification-and-publication-ux.md, Section 6](high-integrity-approval-certification-and-publication-ux.md#6-protest-and-appeal-ux)
- **Why it matters:** Blocked on [Phase 0.1 OD-09](../00-product/open-decisions.md#od-09--protest-and-appeal-authority).
- **Options:** None yet defined.
- **Recommended direction:** None — genuinely dependent on OD-09.
- **Evidence required:** OD-09 resolution.
- **Decision owner:** Product owner
- **Target phase:** 0.10+, blocked
- **Status:** Open — blocked, mirrors OD-09

### DX-17 — Command Palette Adoption

- **Question:** Is a command palette (quick-action search) adopted for the administrative portal?
- **Areas affected:** [information-architecture-and-navigation.md, Section 3](information-architecture-and-navigation.md#3-navigation-architecture), [react-web-experience-architecture.md](react-web-experience-architecture.md)
- **Why it matters:** A candidate power-user accelerator, not essential to core functionality.
- **Options:** Adopt · defer.
- **Recommended direction:** Defer until core workflows are implemented and power-user demand is demonstrated.
- **Evidence required:** Pilot/early-usage feedback.
- **Decision owner:** UX lead
- **Target phase:** Post-pilot
- **Status:** Open

### DX-18 — `mobile/` Scaffolding Timing (Cross-Reference)

- **Question:** When is the Flutter `mobile/` directory actually scaffolded?
- **Areas affected:** [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md)
- **Why it matters:** An implementation-phase decision entirely outside this documentation-only phase's scope.
- **Options:** N/A.
- **Recommended direction:** N/A — a Phase 0.10+ implementation decision.
- **Evidence required:** Implementation roadmap.
- **Decision owner:** Technical lead
- **Target phase:** 0.10+
- **Status:** Open

### DX-19 — Offline Event-Signing UI Impact (Cross-Reference)

- **Question:** If offline event-signing (per [../03-security/security-open-decisions.md, SD-19](../03-security/security-open-decisions.md#sd-19--offline-event-signing-adoption)) is adopted, does the offline-state vocabulary in [status-feedback-error-offline-and-sync-patterns.md, Section 6](status-feedback-error-offline-and-sync-patterns.md#6-offline-and-sync-experience) need a new state?
- **Areas affected:** [status-feedback-error-offline-and-sync-patterns.md](status-feedback-error-offline-and-sync-patterns.md), [flutter-mobile-experience-architecture.md](flutter-mobile-experience-architecture.md)
- **Why it matters:** Directly dependent on SD-19's resolution.
- **Options:** None yet defined.
- **Recommended direction:** None — dependent on SD-19.
- **Evidence required:** SD-19 resolution.
- **Decision owner:** Security owner + UX lead
- **Target phase:** Dependent on SD-19
- **Status:** Open — mirrors SD-19

### DX-20 — Kiosk Hardware Specification

- **Question:** Is dedicated kiosk hardware procured, or does "kiosk mode" run on general-purpose tablets/displays?
- **Areas affected:** [public-portal-kiosk-scoreboard-and-display-experience.md, Section 2](public-portal-kiosk-scoreboard-and-display-experience.md#2-kiosk-experience)
- **Why it matters:** Affects achievable touch-target size, display quality, and physical security assumptions.
- **Options:** Dedicated kiosk hardware · general-purpose devices in kiosk mode.
- **Recommended direction:** None — a procurement decision outside this phase's scope.
- **Evidence required:** DepEd procurement planning.
- **Decision owner:** DepEd Leadership + Infrastructure owner
- **Target phase:** Pre-pilot
- **Status:** Open

### DX-21 — Scanner Audio/Haptic Feedback Patterns

- **Question:** What specific audio tone/vibration pattern distinguishes Granted/Denied/Warning/Offline scan results?
- **Areas affected:** [accreditation-qr-device-and-shared-station-experience.md, Section 2](accreditation-qr-device-and-shared-station-experience.md#2-qr-scanner-experience)
- **Why it matters:** Field-tested feedback patterns matter for high-throughput, high-noise gate environments.
- **Options:** Not evaluated in this phase.
- **Recommended direction:** None — requires field testing with actual scanner hardware.
- **Evidence required:** Pilot field testing.
- **Decision owner:** UX lead + ICT
- **Target phase:** Pre-pilot
- **Status:** Open

### DX-22 — Design-System Version-Numbering Scheme

- **Question:** How is the PMMS Arena Design System's own version tracked relative to the application release version?
- **Areas affected:** [design-system-governance-documentation-and-versioning.md, Section 5](design-system-governance-documentation-and-versioning.md#5-versioning)
- **Why it matters:** Determines how consuming teams track design-system compatibility.
- **Options:** Independent semantic versioning · tied to application release version.
- **Recommended direction:** Independent semantic versioning, mirroring the same independence principle already established for API contracts and rule-sets in [../05-devops/source-control-branching-and-release-workflow.md, Section 4](../05-devops/source-control-branching-and-release-workflow.md#4-release-versioning).
- **Evidence required:** None — a low-risk, reversible convention choice.
- **Decision owner:** Design-system owner
- **Target phase:** 0.10+
- **Status:** Open

### DX-23 — Pilot Usability Session Prioritization

- **Question:** Given limited pilot resources, which user groups receive dedicated usability-testing sessions first?
- **Areas affected:** [ux-research-validation-and-quality-gates.md, Section 2](ux-research-validation-and-quality-gates.md#2-ux-validation-and-usability-testing)
- **Why it matters:** Not every one of the 27 user groups can be studied with equal depth during a single pilot.
- **Options:** Prioritize highest-risk/highest-error-cost groups (scanner operators, scorers, medical) · prioritize highest-volume groups (athletes, guardians, public).
- **Recommended direction:** Prioritize highest-risk/highest-error-cost groups first, consistent with this project's risk-based testing discipline from Phase 0.7.
- **Evidence required:** Pilot planning and resource allocation.
- **Decision owner:** UAT coordinator (per Phase 0.7) + UX lead
- **Target phase:** Pre-pilot
- **Status:** Open

### DX-24 — Cross-Platform Consistency Audit Cadence

- **Question:** How often is React/Flutter consistency (per [ux-research-validation-and-quality-gates.md, Section 4](ux-research-validation-and-quality-gates.md#4-cross-platform-consistency-validation)) formally audited?
- **Areas affected:** [ux-research-validation-and-quality-gates.md](ux-research-validation-and-quality-gates.md)
- **Why it matters:** Without a defined cadence, drift accumulates silently.
- **Options:** Per-release · quarterly · ad hoc only.
- **Recommended direction:** Per-release, tying it to the existing release-management cadence in [../05-devops/incident-problem-change-and-release-management.md, Section 7](../05-devops/incident-problem-change-and-release-management.md#7-release-management).
- **Evidence required:** None — a process convention.
- **Decision owner:** Design-system owner
- **Target phase:** 0.10+
- **Status:** Open

---

## Summary of Blocking / High-Priority Design Decisions

| Decision | Why It Blocks |
|---|---|
| **DX-02** | Branding-palette approval blocks finalizing every brand-carrying visual token |
| **DX-13** | AI use-case approval is blocked directly on the still-unresolved Phase 0.1 OD-29 |
| **DX-14 / DX-15 / DX-16** | Sport-specific interface details remain blocked on OD-10/OD-12/OD-09 exactly as every prior phase's sport-dependent decisions have been |
| **DX-01** | WCAG conformance target underlies every accessibility-related design-quality gate |
