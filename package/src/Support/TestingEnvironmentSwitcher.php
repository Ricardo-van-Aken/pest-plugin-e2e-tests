<?php

namespace RicardoVanAken\PestPluginE2ETests\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

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

        // Clear the session manager's handler cache so it rebuilds with the new connection
        // This is necessary because Laravel caches the handler instance
        // NOTE: This code should obe used when we use middleware to switch the storage
        // TestingEnvironmentSwitcher::resetSessionHandler();
    }

    /**
     * Switch the database connection to the testing connection if it exists.
     */
    public static function switchDatabaseConnection(): void
    {
        $testingConnection = TestingConnectionNaming::getTestingDatabaseConnection();

        if (config("database.connections.{$testingConnection}")) {
            config(['database.default' => $testingConnection]);
            
            Log::info(
                "[LaravelE2ETesting] Switched to testing database connection '{$testingConnection}'."
            );
        } else {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Testing database connection '{$testingConnection}' not found. " .
                "Ensure TestingDatabaseServiceProvider has created this connection."
            );
        }

        return;
    }

    /**
     * Switch the cache store to the testing store if it exists.
     */
    public static function switchCacheStore(): void
    {
        $testingStore = TestingConnectionNaming::getTestingCacheStore();

        if (config("cache.stores.{$testingStore}")) {
            config(['cache.default' => $testingStore]);
            
            Log::info(
                "[LaravelE2ETesting] Switched to testing cache store '{$testingStore}'."
            );
        } else {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Testing cache store '{$testingStore}' not found. " .
                "Ensure TestingCacheServiceProvider has created this store."
            );
        }

        return;
    }

    /**
     * Switch the queue connection to the testing connection if it exists.
     */
    public static function switchQueueConnection(): void
    {
        $testingConnection = TestingConnectionNaming::getTestingQueueConnection();

        if (config("queue.connections.{$testingConnection}")) {
            config(['queue.default' => $testingConnection]);
            Log::info(
                "[LaravelE2ETesting] Switched to testing queue connection '{$testingConnection}'."
            );
        } else {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Testing queue connection '{$testingConnection}' not found. " .
                "Ensure TestingQueueServiceProvider has created this connection."
            );
        }

        return;
    }

    /**
     * Switch the session connection to the testing connection if it exists.
     */
    public static function switchSessionConnection(): void
    {

        $driver = config('session.driver');

        switch ($driver) {
            case 'redis':
                $testingConnection = TestingConnectionNaming::getTestingSessionConnection('redis');

                if (config("database.redis.{$testingConnection}")) {
                    config(['session.connection' => $testingConnection]);
                    
                    Log::info("[LaravelE2ETesting] " . 
                        "Switched to testing Redis session connection '{$testingConnection}'."
                    );
                } else {
                    throw new \RuntimeException("[LaravelE2ETesting] " .
                        "Testing Redis session connection '{$testingConnection}' not found. " .
                        "Ensure TestingSessionServiceProvider has created this connection."
                    );
                }

                break;

            case 'database':
                $testingConnection = TestingConnectionNaming::getTestingSessionConnection('database');

                if (config("database.connections.{$testingConnection}")) {
                    config(['session.connection' => $testingConnection]);

                    Log::info("[LaravelE2ETesting] " . 
                        "Switched to testing database session connection '{$testingConnection}'."
                    );
                } else {
                    throw new \RuntimeException("[LaravelE2ETesting] " .
                        "Testing database session connection '{$testingConnection}' not found. " .
                        "Ensure TestingSessionServiceProvider has created this connection."
                    );
                }

                break;

            case 'array':
                // Array sessions are already isolated per process, no action needed
                break;

            default:
                throw new \RuntimeException("[LaravelE2ETesting] " .
                    "Unknown or unsupported session driver '{$driver}'. "
                );

                break;
        }

    }

    /**
     * Reset the session handler so it rebuilds with the updated config.
     * This is necessary because Laravel's SessionManager caches the handler instance.
     */
    public static function resetSessionHandler(): void
    {
        $manager = Session::getFacadeRoot();
        
        // Clear the cached drivers array - this is where Laravel stores the driver instances
        // When we call driver() again, it will rebuild using the updated config
        $reflection = new \ReflectionClass($manager);
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
    }


}