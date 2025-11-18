<?php

namespace RicardoVanAken\PestPluginE2ETests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class TestingSessionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Switch session to testing storage if header is present (receiving test requests)
        if ($this->isTestRequest()) {
            $this->switchSessionToTestingStorage();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Check if the current request is a test request.
     */
    protected function isTestRequest(): bool
    {
        return request()->hasHeader(config('e2e-testing.header_name', 'X-TESTING'));
    }

    /**
     * Switch the session configuration to use testing storage.
     */
    protected function switchSessionToTestingStorage(): void
    {
        $sessionDriver = config('session.driver');

        switch ($sessionDriver) {
            case 'redis':
                $this->switchRedisSessionToTesting();
                break;

            case 'database':
                $this->switchDatabaseSessionToTesting();
                break;

            case 'file':
                $this->switchFileSessionToTesting();
                break;

            case 'array':
                // Array sessions are already isolated per process, no action needed
                break;

            case 'memcached':
                // Memcached would require a separate server or namespace
                // For now, we'll use a testing prefix to isolate keys
                $this->switchMemcachedSessionToTesting();
                break;

            case 'dynamodb':
                // DynamoDB would use a separate table for testing
                $this->switchDynamoDBSessionToTesting();
                break;

            case 'cookie':
                // Cookie sessions are already isolated per request, no action needed
                break;

            default:
                // Unknown session driver, log a warning
                Log::warning(
                    "[LaravelIntegrationTesting] Unknown session driver '{$sessionDriver}'. " .
                    "Session isolation for testing may not work correctly."
                );
                break;
        }
    }

    /**
     * Switch Redis session to use the testing database.
     */
    protected function switchRedisSessionToTesting(): void
    {
        // Sessions can use either a direct connection or a cache store
        $sessionStore = config('session.store');
        $sessionConnection = config('session.connection', 'default');

        // If a store is specified, we need to switch the cache store's Redis connection
        if ($sessionStore) {
            $cacheStoreConfig = config("cache.stores.{$sessionStore}", []);
            
            // If the store uses Redis, switch its connection
            if (isset($cacheStoreConfig['driver']) && $cacheStoreConfig['driver'] === 'redis') {
                $cacheConnection = $cacheStoreConfig['connection'] ?? 'cache';
                $redisConfig = config("database.redis.{$cacheConnection}", []);

                if (!empty($redisConfig)) {
                    $currentSessionDb = $redisConfig['database'] ?? 1;
                    $testingSessionDb = (int) env('REDIS_SESSION_DB_TESTING', 14);

                    if ($testingSessionDb !== $currentSessionDb) {
                        config([
                            "database.redis.{$cacheConnection}.database" => $testingSessionDb,
                        ]);
                    }
                }
            }
            return;
        }

        // If no store is specified, use the direct connection
        $redisConfig = config("database.redis.{$sessionConnection}", []);

        if (empty($redisConfig)) {
            Log::warning(
                "[LaravelIntegrationTesting] Redis config not found for session connection '{$sessionConnection}'. " .
                "Session isolation for testing may not work correctly."
            );
            return;
        }

        $currentSessionDb = $redisConfig['database'] ?? 0;
        $testingSessionDb = (int) env('REDIS_SESSION_DB_TESTING', 14);

        // Check if the testing and application redis session databases are the same.
        if ($testingSessionDb === $currentSessionDb) {
            Log::warning(
                "[LaravelIntegrationTesting] Redis session database for testing is the same as the applications Redis " .
                "session database. This will cause tests to use the same sessions as the application. " .
                "\nCurrent database number: {$currentSessionDb}. " .
                "\nTesting database number: {$testingSessionDb}. " .
                "\nPlease set REDIS_SESSION_DB_TESTING to a different database number."
            );
            return;
        }

        // Switch the used redis session database to the testing session database.
        config([
            "database.redis.{$sessionConnection}.database" => $testingSessionDb,
        ]);
    }

    /**
     * Switch database session to use the testing database connection.
     */
    protected function switchDatabaseSessionToTesting(): void
    {
        // Get the current default connection and switch to its _testing version
        $testingConnection = config('database.default') . '_testing';

        // Ensure the testing connection exists
        if (config("database.connections.{$testingConnection}")) {
            // Update the session to use the testing database connection
            config([
                'session.connection' => $testingConnection,
            ]);
        }
    }

    /**
     * Switch file session to use a testing directory.
     */
    protected function switchFileSessionToTesting(): void
    {
        $testingPath = storage_path('framework/sessions/testing');

        // Update the session to use the testing directory
        config([
            'session.files' => $testingPath,
        ]);
    }

    /**
     * Switch Memcached session to use a testing prefix.
     */
    protected function switchMemcachedSessionToTesting(): void
    {
        // Memcached doesn't support separate databases, so we use a prefix
        // This requires the session prefix to be set differently for tests
        $testingPrefix = config('session.cookie', 'laravel_session') . '-testing-';
        config([
            'session.cookie' => $testingPrefix,
        ]);
    }

    /**
     * Switch DynamoDB session to use a testing table.
     */
    protected function switchDynamoDBSessionToTesting(): void
    {
        $testingTable = env('DYNAMODB_SESSION_TABLE', 'sessions') . '_testing';
        config([
            'session.table' => $testingTable,
        ]);
    }
}

