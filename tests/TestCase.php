<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // During testing ensure a Passport personal access client exists so
        // that calls to createToken() do not throw "Personal access client not found"
        if (app()->environment('testing')) {
            // Omit errors if the table doesn't exist yet (RefreshDatabase may run migrations later)
            try {
                if (!\DB::table('oauth_clients')->where('personal_access_client', 1)->exists()) {
                    $clientRepo = new \Laravel\Passport\ClientRepository();
                    $clientRepo->createPersonalAccessClient(null, 'Laravel Personal Access Client', 'http://localhost');
                }
            } catch (\Exception $e) {
                // ignore: migrations may not have run yet; tests that need clients will create them later
            }
        }
    }
}
