<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClientAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('usr_id')) {
            alert()->error('Session Expired', 'Please log in again');
            return redirect('/');
        }

        if (session('utyp_id') != 3) {
            abort(403, 'You are not authorized to access this module.');
        }

        return $next($request);
    }
}