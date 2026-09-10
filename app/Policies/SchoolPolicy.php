<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

/**
 * The school catalog is reference master data. Everyone with a login may
 * read the registry; writing it is limited to a System Administrator, an
 * Active ICT / Central-ICT `ManagementTeam` member, or a member whose
 * `role_title` is "Meet Manager"/"Meet Director" — the single predicate
 * `User::canManageSchoolMasterData()`, which this policy is the
 * authorization surface for (WP-REALIGN-13). There is no per-record
 * owner-scoping: a School is not owned by any delegation or committee, so
 * every write ability resolves to the same capability check.
 *
 * `District`/`SchoolDistrict`/`Sport`/`Meet` deliberately have no policy
 * class — those stay behind the `role:admin` route group because no
 * capability or per-record concept applies to them. See docs/authorization.md.
 */
class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, School $school): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageSchoolMasterData();
    }

    /** Also gates archive/restore — a reversible status flip, not a delete. */
    public function update(User $user, School $school): bool
    {
        return $user->canManageSchoolMasterData();
    }

    public function delete(User $user, School $school): bool
    {
        return $user->canManageSchoolMasterData();
    }
}
