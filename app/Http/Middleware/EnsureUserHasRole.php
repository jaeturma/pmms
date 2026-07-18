<?php

namespace App\Http\Middleware;

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
            $user instanceof User && in_array($user->role->value, $roles, true),
            403,
        );

        return $next($request);
    }
}
