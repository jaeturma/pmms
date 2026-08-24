<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict the route to users holding one of the given roles.
     *
     * Usage: ->middleware('role:admin,organizer')
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasRole(...collect($roles)
                ->map(fn (string $role): ?UserRole => UserRole::tryFrom($role))
                ->filter()
                ->all()),
            403,
        );

        return $next($request);
    }
}
