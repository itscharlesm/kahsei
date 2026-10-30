<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class MobileTokenStore
{
    private const CACHE_STORE = 'file';
    private const CACHE_KEY_PREFIX = 'mobile_auth_token:';
    private const TOKEN_LIFETIME_DAYS = 30;

    public function issue(int $userId): string
    {
        $token = bin2hex(random_bytes(32));

        Cache::store(self::CACHE_STORE)->put(
            $this->cacheKey($token),
            $userId,
            now()->addDays(self::TOKEN_LIFETIME_DAYS),
        );

        return $token;
    }

    public function userIdFor(?string $token): ?int
    {
        if ($token === null || $token === '') {
            return null;
        }

        $userId = Cache::store(self::CACHE_STORE)->get($this->cacheKey($token));

        return is_numeric($userId) ? (int) $userId : null;
    }

    public function revoke(?string $token): void
    {
        if ($token === null || $token === '') {
            return;
        }

        Cache::store(self::CACHE_STORE)->forget($this->cacheKey($token));
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_KEY_PREFIX . hash('sha256', $token);
    }
}