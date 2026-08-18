# Sport personnel and user provisioning

Tournament personnel are stored in `meet_sport_assignments` with both their canonical `person_id` and eventual `user_id`. Role, original designation, source sequence, source District text, Municipality, School District, and optional category/scope are retained.

Combined source functions become atomic scoped assignments to the same person:

- Tournament Secretary/ICT -> Tournament Secretary + Tournament ICT
- ICT Technical Official -> Tournament ICT + Technical Official

`account_provisions` is a pending activation queue. It contains a unique suggested username but no password, invented email, or plaintext token. Activation must use a separately implemented/admin-triggered invitation with a one-time hashed token or the existing password-establishment workflow. Linking sets `people.user_id`, updates pending assignments/memberships, and must not create another Person.

The combined Weightlifting/Kickboxing header is not automatically mapped. Basketball 3x3 remains in the sport catalog without inferred TM/TO assignments.

