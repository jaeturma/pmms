# PMMS Internal Messaging, Announcements, and Communication Boundaries

**Status:** Draft Complete — Pending Domain, Workflow, Security, Quality, Operations, and Engineering Validation
**Related:** [notification-and-recipient-resolution-architecture.md](notification-and-recipient-resolution-architecture.md) · [../06-design/committee-logistics-medical-finance-and-support-experience.md](../06-design/committee-logistics-medical-finance-and-support-experience.md)

**No messaging feature, chat UI, or conversation-storage code is created here.**

---

## 1. Messaging Architecture — Evaluated, Not Committed

Whether PMMS needs internal messaging beyond notifications is evaluated, not committed, in this phase. Potential use cases: committee coordination · delegation coordination · incident thread · venue coordination · support conversation.

**Messaging must not replace official workflow records** — restated absolutely as this section's governing rule, directly extending [notification-and-recipient-resolution-architecture.md, Section 14](notification-and-recipient-resolution-architecture.md#14-relationship-to-high-integrity-domains)'s "notification delivery is never the sole record of a decision" principle to messaging specifically. A decision discussed in a committee message thread is not itself the decision — the decision is the durable command/state-transition record in its owning context.

## 2. Messaging Boundaries

| Element | Direction |
|---|---|
| Who may start a conversation | Restricted to authenticated users with an active, relevant assignment (committee, delegation, venue, incident) — never an open, unrestricted messaging surface |
| Participant scope | Bound to the conversation's originating scope (e.g., a committee thread's participants are drawn from that committee's active assignment roster) |
| Meet scope | A conversation is meet-scoped by default — it does not silently persist or remain visible across meets |
| Retention | Placeholder pending the same retention discipline as every other record category, per [../02-data/retention-archival-and-disposal.md](../02-data/retention-archival-and-disposal.md) |
| Moderation | A candidate requirement for any messaging surface reachable by a broad participant set — no specific moderation mechanism is selected |
| Attachment classification | Any attachment inherits the classification rules of [../03-security/file-object-storage-and-malware-security.md](../03-security/file-object-storage-and-malware-security.md) — the same 15-stage upload lifecycle, no exception for message attachments |
| Export | Subject to the same export-classification and reason-capture discipline as any other record, per [../03-security/data-sharing-export-and-public-disclosure-controls.md](../03-security/data-sharing-export-and-public-disclosure-controls.md) |
| Search | Messaging content search, if implemented, respects the same scope/classification boundaries as any other search surface, per [../06-design/search-filter-import-export-and-file-experience.md, Section 1](../06-design/search-filter-import-export-and-file-experience.md#1-search-experience) |
| Deletion or archival | Follows the same correction-supersedes-never-destructively-overwrites discipline as any other record where the conversation touches a high-integrity decision reference |
| Audit | Every message send, edit, and deletion attempt is auditable |
| Public non-disclosure | Internal messaging content is never surfaced on any public surface, restated absolutely |
| Minor-data restrictions | A conversation must never become an unmonitored channel for discussing a minor athlete's sensitive data outside the classification and access rules already governing that data, per [../03-security/minor-athlete-and-guardian-data-governance.md](../03-security/minor-athlete-and-guardian-data-governance.md) |

**Avoid building a general-purpose social chat platform unless justified** — restated absolutely as this section's governing rule. PMMS's messaging need, if any, is operationally scoped (committee/delegation/incident/venue coordination), not a general messaging product.

## 3. Committee and Delegation Communications

Committee communications (Section 2's committee-scoped conversation) and delegation communications (delegation-scoped) share the same boundary model — a Food committee coordination thread never reaches a Finance committee participant, restated from the same committee-and-role-aware retrieval principle Phase 0.10 established for AI knowledge assistance ([../07-ai/helpdesk-and-committee-knowledge-assistants.md, "Committee Knowledge Assistant"](../07-ai/helpdesk-and-committee-knowledge-assistants.md#committee-knowledge-assistant-uc-10)), now applied to human-to-human messaging.

## 4. Direct Messages and Conversation Threads

A direct message (one-to-one) and a conversation thread (many-to-many, scoped) are evaluated as distinct candidate patterns — neither is committed to a specific implementation. Both, if built, inherit Section 2's boundary model in full.

## 5. Announcements

Distinguished from messaging: a public announcement, organization announcement, meet announcement, committee announcement, delegation announcement, venue announcement, and emergency announcement each require: audience · start time · expiry · classification · approval · publication state.

Announcements follow the existing publication pattern (`AnnouncementPublished`, BC-28 → BC-29, per [../01-architecture/domain-events-catalog.md](../01-architecture/domain-events-catalog.md)) — never a new, parallel publication mechanism. A public announcement is never confused with an internal messaging conversation; they are architecturally distinct surfaces with distinct audiences and distinct authorization models.

## 6. Attachment Controls

Any file attached to a message or announcement passes through the existing file-upload lifecycle (per [../03-security/file-object-storage-and-malware-security.md](../03-security/file-object-storage-and-malware-security.md)) before being presented as available — a file the malware scanner has not cleared is never presented as downloadable, restated from [../06-design/search-filter-import-export-and-file-experience.md, Section 5](../06-design/search-filter-import-export-and-file-experience.md#5-file-experience).

## 7. Open Questions

See [workflow-open-decisions.md](workflow-open-decisions.md) — notably WD-19 (whether internal messaging is built at all in the initial implementation, or whether notifications plus existing committee-operations tooling suffice).
