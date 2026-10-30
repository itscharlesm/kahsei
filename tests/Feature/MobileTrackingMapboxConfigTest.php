<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileTrackingMapboxConfigTest extends TestCase
{
    public function test_route_uses_configured_token_instead_of_runtime_environment(): void
    {
        config(['services.mapbox.token' => 'test-config-only-token']);
        Http::preventStrayRequests();
        Http::fake([
            'api.mapbox.com/*' => Http::response([
                'routes' => [[
                    'geometry' => 'test-road-geometry',
                    'distance' => 1200,
                    'duration' => 180,
                ]],
            ]),
        ]);

        $this->postJson('/api/mobile/technician/route-to-client', [
            'startLatitude' => 10.30,
            'startLongitude' => 123.90,
            'clientLatitude' => 10.32,
            'clientLongitude' => 123.92,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('geometry', 'test-road-geometry');

        Http::assertSent(fn ($request) =>
            $request['access_token'] === 'test-config-only-token'
            && $request['geometries'] === 'polyline6'
            && $request['overview'] === 'full'
        );
    }

    public function test_missing_configured_token_returns_clear_error_without_mapbox_request(): void
    {
        config(['services.mapbox.token' => null]);
        Http::fake();

        $this->postJson('/api/mobile/technician/route-to-client', [
            'startLatitude' => 10.30,
            'startLongitude' => 123.90,
            'clientLatitude' => 10.32,
            'clientLongitude' => 123.92,
        ])->assertStatus(500)
            ->assertJsonPath('message', 'Mapbox access token is not configured.');

        Http::assertNothingSent();
    }
}
