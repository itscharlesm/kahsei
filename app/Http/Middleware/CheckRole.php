<?php
// app/Http/Middleware/CheckRole.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Usage in routes: ->middleware('role:SUPERADMIN,ADMIN,MANAGER')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Not logged in at all — your app uses custom session vars,
        // not Laravel's auth guard, so check usr_id directly
        if (!session('usr_id')) {
            alert()->error('Session Expired', 'Please log in again');
            return redirect('/');
        }

        // Superadmin (branch_id == 1) always passes, per your existing convention
        // if (session('branch_id') == 1) {
        //     return $next($request);
        // }

        foreach ($roles as $role) {
            if (session($role) == '1') {
                return $next($request);
            }
        }

        abort(403, 'You are not authorized to access this module.');
    }
}