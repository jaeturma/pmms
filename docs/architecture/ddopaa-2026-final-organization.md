# DdOPAA 2026 final organization

The official delegation is a Municipality (`districts` in the established Province-division schema). A School is the athlete/coach origin and never replaces the delegation.

```text
Meet
|- Municipality delegation
|  |- School District -> DSC assignments
|  `- School -> approved coaches -> athletes
|- Meet Sport -> scoped tournament personnel assignments
`- Management/TWG Unit -> memberships and role titles
```

Canonical `people` identities exist before accounts. A person can hold many TWG and MeetSport roles but can link to only one user. Geographic assignment data uses the official District and SchoolDistrict masters; no Congressional District is inferred from the workbook.

DSAC owns eligibility decisions, Medical owns medical clearance, and Results Committee owns final result confirmation. Monitoring roles can see readiness/status in their scope but not exercise those authorities.

