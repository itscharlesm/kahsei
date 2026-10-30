<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\MobileTokenStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileCacheToken
{
    public function __construct(private MobileTokenStore $tokens)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $userId = $this->tokens->userIdFor($token);

        if ($userId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = User::query()
            ->where('usr_id', $userId)
            ->where('usr_active', 1)
            ->first();

        if ($user === null) {
            $this->tokens->revoke($token);

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $request->setUserResolver(static fn ($guard = null) => $user);

        return $next($request);
    }
}