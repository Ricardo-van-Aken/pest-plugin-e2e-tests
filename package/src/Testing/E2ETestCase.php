<?php

namespace RicardoVanAken\PestPluginE2ETests\Testing;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;

abstract class E2ETestCase extends BaseTestCase
{
    protected Client $client;

    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the full testing environment (DB / queue / session, etc.) is configured.
        TestingEnvironmentSwitcher::switchAll();

        // For each test, create a new client with refreshed cookiejar
        $this->client = new Client([
            'base_uri' => env('APP_URL', 'https://localhost'),
            'verify' => false,
            'cookies' => new CookieJar,
            'handler' => HandlerStack::create(),
        ]);

        // Set up the database by migrating it once for all tests
        if (! static::$migrated) {
            // Migrate using the testing connection
            Artisan::call('migrate');
            static::$migrated = true;
        }
    }

    protected function tearDown(): void
    {
        // Clear cache to ensure test isolation (resets rate limiters, session data, etc.). Make sure
        // tests dont use the same cache as the application.
        Cache::flush();

        // NOTE: Think about clearing queues and sessions here too.

        parent::tearDown();
    }

    /**
     * HTTP request builder for integration tests.
     */
    protected function httpRequestBuilder(): HttpRequestBuilder
    {
        return new HttpRequestBuilder($this->client);
    }
}

