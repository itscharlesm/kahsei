<?php

namespace Tests\Feature;

use App\Services\MobileTokenStore;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MobileTokenStoreTest extends TestCase
{
    public function test_tokens_are_randomly_issued_and_revoked_from_file_cache(): void
    {
        $tokens = app(MobileTokenStore::class);
        $token = $tokens->issue(123);

        try {
            $this->assertSame(64, strlen($token));
            $this->assertSame(123, $tokens->userIdFor($token));

            $tokens->revoke($token);

            $this->assertNull($tokens->userIdFor($token));
        } finally {
            $tokens->revoke($token);
        }
    }

    public function test_mobile_session_routes_reject_missing_tokens_as_json(): void
    {
        $this->getJson('/api/mobile/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');

        $this->postJson('/api/mobile/logout')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_tokens_expire_after_thirty_days(): void
    {
        $tokens = app(MobileTokenStore::class);
        $token = $tokens->issue(456);

        try {
            Carbon::setTestNow(now()->addDays(31));

            $this->assertNull($tokens->userIdFor($token));
        } finally {
            Carbon::setTestNow();
            $tokens->revoke($token);
        }
    }
}