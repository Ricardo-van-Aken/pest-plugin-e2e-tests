<?php

namespace RicardoVanAken\PestPluginE2ETests;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\HandlerStack;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class E2ETestCase extends BaseTestCase
{
    protected Client $client;

    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // For each test, create a new client with refreshed cookiejar
        $this->client = new Client([
            'base_uri' => env('APP_URL', 'https://localhost'),
            'verify' => false,
            'cookies' => new CookieJar,
            'handler' => HandlerStack::create(),
        ]);

        // Get the current runtime connection
        $currentConnection = DB::getDefaultConnection();

        // Check if the current connection ends with _testing
        if (! str_ends_with($currentConnection, '_testing')) {
            $this->fail(
                "Integration Tests must use a database connection ending with '_testing', to make sure they are not ran on your default database.".
                "The current database connection is: {$currentConnection}. ".
                "Make sure phpunit.integration.xml sets DB_CONNECTION to a connection ending with '_testing' (e.g., mysql_testing, pgsql_testing)."
            );
        }

        // Check if the testing connection exists
        if (! config("database.connections.{$currentConnection}")) {
            $this->fail(
                "The testing database connection '{$currentConnection}' is not configured. ".
                'If you are using a different database than the default ones used by the LaravelIntegrationTesting package, '.
                'you need to configure the testing connection in config/database.php.'
            );
        }

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
