# Coach self-registration

Public registration can request Coach onboarding, but the new account remains a Viewer and receives a pending `coach_onboarding_requests` row. This prevents self-granted roster authority.

After an authorized reviewer verifies the profile and changes the account to Coach, the Coach is linked to a delegation Personnel row and may submit one or more `coach_assignment_requests`. Every request binds User + MeetSport + Municipality delegation + School. The server rejects a school outside the delegation municipality and a sport from another meet.

Approved scope is the prerequisite for sport-specific enrollment. Existing policies continue to prevent Coaches from DSAC decisions, Medical decisions, entry confirmation, and delegation administration. Athlete creation independently enforces that the origin School belongs to the official Municipality delegation.

