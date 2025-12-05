<?php

namespace RicardoVanAken\PestPluginE2ETests\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

/**
 * Central place to handle switching application environment concerns for tests:
 * storage (DB / queue / session) and, in future, mail / logging / etc.
 */
class TestingEnvironmentSwitcher
{
    /**
     * Switch all supported storage types to their testing variants.
     */
    public static function switchAll(): void
    {
        static::switchDatabaseConnection();
        static::switchQueueConnection();
        static::switchSessionConnection();
        static::switchCacheStore();
    }

    /**
     * Switch the database connection to the testing connection if it exists.
     */
    public static function switchDatabaseConnection(): void
    {
        $testingConnection = static::getTestingDatabaseConnection();

        if (config("database.connections.{$testingConnection}")) {
            config(['database.default' => $testingConnection]);
            Log::info(
                "[LaravelE2ETesting] Switched to testing database connection '{$testingConnection}'."
            );
            return;
        }

        Log::warning(
            "[LaravelIntegrationTesting] Testing database connection '{$testingConnection}' not found. ".
            'Skipping test storage switch. Ensure TestingDatabaseServiceProvider has created this connection.'
        );
    }

    /**
     * Switch the cache store to the testing store if it exists.
     */
    public static function switchCacheStore(): void
    {
        $testingStore = static::getTestingCacheStore();

        if (config("cache.stores.{$testingStore}")) {
            config(['cache.default' => $testingStore]);
            Log::info(
                "[LaravelE2ETesting] Switched to testing cache store '{$testingStore}'."
            );
            return;
        }

        Log::warning(
            "[LaravelIntegrationTesting] Testing cache store '{$testingStore}' not found. ".
            'Skipping test storage switch. Ensure TestingCacheServiceProvider has created this store.'
        );
    }

    /**
     * Switch the queue connection to the testing connection if it exists.
     */
    public static function switchQueueConnection(): void
    {
        $testingConnection = static::getTestingQueueConnection();

        if (config("queue.connections.{$testingConnection}")) {
            config(['queue.default' => $testingConnection]);
            Log::info(
                "[LaravelE2ETesting] Switched to testing queue connection '{$testingConnection}'."
            );
            return;
        }

        Log::warning(
            "[LaravelIntegrationTesting] Testing queue connection '{$testingConnection}' not found. ".
            'Skipping test storage switch. Ensure TestingQueueServiceProvider has created this connection.'
        );
    }

    /**
     * Switch the session connection to the testing connection if it exists.
     */
    public static function switchSessionConnection(): void
    {
        $driver = config('session.driver');

        switch ($driver) {
            case 'redis':
                $testingConnection = static::getTestingSessionConnection();

                if (config("database.redis.{$testingConnection}")) {
                    config(['session.connection' => $testingConnection]);

                    Log::info(
                        "[LaravelE2ETesting] Switched to testing Redis session connection '{$testingConnection}'."
                    );

                    return;
                }

                Log::warning(
                    "[LaravelIntegrationTesting] Testing Redis session connection '{$testingConnection}' not found. ".
                    'Skipping test storage switch. Ensure TestingSessionServiceProvider has created this connection.'
                );
                break;

            case 'array':
                // Array sessions are already isolated per process, no action needed
                break;

            default:
                Log::warning(
                    "[LaravelIntegrationTesting] Unknown or unsupported session driver '{$driver}'. ".
                    'Skipping test storage switch. Ensure TestingSessionServiceProvider has created this connection.'
                );
                break;
        }

    }

    public static function getTestingDatabaseConnection(): string
    {
        $baseConnection = config('database.default');
        $testingConnection = env('DB_CONNECTION_TESTING', $baseConnection . '_testing');

        return $testingConnection;
    }

    public static function getTestingQueueConnection(): string
    {
        $baseConnection = config('queue.default');
        $testingConnection = env('QUEUE_CONNECTION_TESTING', $baseConnection . '_testing');

        return $testingConnection;
    }

    public static function getTestingSessionConnection(): string
    {
        $baseConnection = config('session.connection') ?? 'default';
        $testingConnection = env('SESSION_CONNECTION_TESTING', $baseConnection . '_testing');

        return $testingConnection;
    }

    public static function getTestingCacheStore(): string
    {
        $baseStore = config('cache.default');
        $testingStore = env('CACHE_STORE_TESTING', $baseStore . '_testing');

        return $testingStore;
    }

    /**
     * Reset the session handler so it rebuilds with the updated config.
     * This is necessary because Laravel's SessionManager caches the handler instance.
     * 
     * This method works by:
     * 1. Clearing the SessionManager's internal driver cache (using reflection)
     * 2. Forgetting container instances so they rebuild with the new config
     * 3. Resetting auth guards so they pick up the new session store
     */
    public static function resetSessionHandler(): void
    {
        $manager = Session::getFacadeRoot();
        
        // Use reflection to clear the cached driver instances
        // This forces Laravel to rebuild them with the updated config
        $reflection = new \ReflectionClass($manager);
        
        // Clear the cached drivers array - this is where Laravel stores the driver instances
        // When we call driver() again, it will rebuild using the updated config
        if ($reflection->hasProperty('drivers')) {
            $driversProperty = $reflection->getProperty('drivers');
            $driversProperty->setAccessible(true);
            $driversProperty->setValue($manager, []);
        }
        
        // Forget container instances so they rebuild with the new session connection
        app()->forgetInstance('session');
        app()->forgetInstance('session.store');
        
        // Also reset auth guards so they pick up the new session store
        // This is critical - otherwise the guard might still reference the old session store
        $auth = app('auth');
        if (method_exists($auth, 'forgetGuards')) {
            $auth->forgetGuards();
        } else {
            // Fallback: forget the guard instances manually
            app()->forgetInstance('auth');
            foreach (['web', 'api'] as $guard) {
                app()->forgetInstance("auth.guard.{$guard}");
            }
        }
        
        // Force rebuild the driver by calling driver() which will use the updated config
        // This will create a new Store with a new handler using the updated connection/config
        $manager->driver();
    }


}