<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionCreator;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

class TestingSessionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The session config does not allow for creation of new connections. We will have to change the config at
        // runtime(when a testing request is received). We can only make sure that the required database connections
        // or cache stores exists.
    
        $driver = config('session.driver');
        switch ($driver) {
            case 'redis':
                $testingConnection = TestingConnectionNaming::getTestingSessionConnection('redis');
                
                // Create the Redis database connection for sessions if it doesn't exist
                if (! config("database.redis." . $testingConnection)) {
                    TestingConnectionCreator::createTestingRedisConnection($testingConnection, [
                            'database' => env('REDIS_SESSION_DB_TESTING', 12),
                    ]);
                }
                
                break;

            case 'database':
                $testingConnection = TestingConnectionNaming::getTestingSessionConnection('database');
                
                // Create the database connection for sessions if it doesn't exist
                if (! config("database.connections." . $testingConnection)) {
                    $baseConnection = TestingConnectionNaming::deriveBaseConnection($testingConnection);
                    $baseConfig = config("database.connections.{$baseConnection}") ?? [];
                    
                    // Create the overrides for the database connection configuration
                    $overrides = [];
                    if (env('DB_SESSION_DATABASE_TESTING') !== null || isset($baseConfig['database'])) {
                        $overrides['database'] = env('DB_SESSION_DATABASE_TESTING', $baseConfig['database'] . '_testing');
                    }
                    if (env('DB_SESSION_USERNAME_TESTING') !== null) {
                        $overrides['username'] = env('DB_SESSION_USERNAME_TESTING');
                    }
                    if (env('DB_SESSION_PASSWORD_TESTING') !== null) {
                        $overrides['password'] = env('DB_SESSION_PASSWORD_TESTING');
                    }
                    
                    TestingConnectionCreator::createTestingDatabaseConnection($testingConnection, $overrides);
                }

                break;

            case 'array':
                // Array sessions are already isolated per process, no action needed
                break;
            default:
                break;
        }

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

}

