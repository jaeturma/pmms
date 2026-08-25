# Coach registration and assignment alignment

## 1. Current registration workflow

Fortify creates one User and one `coach_onboarding_requests` row. Public registration currently requires a municipality and one or more Events, stores the first event directly, and syncs all selected events through a pivot. This gives the applicant control over operational scope and must change.

## 2. Current approval workflow

`CoachAssignmentRequestController` exposes onboarding and event-enrollment review. Tournament Secretary and Tournament ICT assignments can review; managers can view/accredit. Approval currently converts the onboarding event pivot into approved `coach_assignment_requests` and creates the Coach personnel identity.

## 3. Current Coach data model

The existing User, onboarding request, Personnel coach profile, and Accreditation records already provide the required one-person/one-account lifecycle. No duplicate Coach profile table is needed.

## 4. Current Sport/Event assignment model

`coach_assignment_requests` already identifies User, MeetSport, Delegation, School, optional Event, status, reviewer, review time, and notes. Approved rows are the effective assignment source used by policies and entry queries. It can support whole-sport, category, and event scopes by adding an optional category and explicit scope type.

## 5. Required changes

- Public onboarding stores MeetSport, Delegation, School, and Sport only.
- Public input is prohibited from supplying event/category scope.
- Approval requires one or more reviewer-selected scopes.
- Scope resolution expands whole-sport and category assignments into effective Event IDs.
- Assignments are deactivated rather than deleted.
- Registration and approval UI reflect their separate responsibilities.

## 6. Existing Coach migration concerns

Existing approved event rows remain valid event-level scopes. Existing onboarding event pivots remain readable for compatibility but no longer grant new access. Approved sport-only records are not automatically treated as whole-sport assignments. Rows with no approved assignment remain assignment-required; ambiguous intent is not guessed. A read-only alignment command/report can classify these records after deployment.

## 7. Backend authorization changes

`CoachAccessService` is the centralized resolver for sport, category, event, delegation, and athlete access. Only approved, non-ended assignment rows count. Coaches remain restricted to their assigned delegation. Tournament ICT review is restricted by its active MeetSport assignment, across all delegations in that sport.

## 8. UI changes

The public form shows delegation, school, and active sport only, with helper text explaining approval-time assignment. The Coach Approval screen shows the sport-level application and lets an authorized reviewer select one or more Events before approval. Existing event enrollment management remains available for post-approval changes.

## 9. Permission changes

Tournament ICT and Tournament Secretary keep operational approval authority within assigned MeetSport. Tournament Managers retain existing visibility/accreditation authority. Technical Officials remain view-only. Central account-management permissions are unchanged.

## 10. Tests

Tests cover sport-only registration, prohibited self-assignment fields, no pre-approval access, sport-scoped ICT queues and approval, assignment expansion, own-delegation enforcement, event isolation, later additions, inactive assignment access removal, multiple assignments, and legacy event-scope compatibility.
