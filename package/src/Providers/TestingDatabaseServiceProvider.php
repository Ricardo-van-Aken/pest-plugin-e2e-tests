<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionCreator;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

class TestingDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $testingConnection = TestingConnectionNaming::getTestingDatabaseConnection();

        // Create the testing database connection if it doesn't already exist
        if (! config("database.connections.{$testingConnection}")) {
            $baseConnection = TestingConnectionNaming::deriveBaseConnection($testingConnection);
            $baseConfig = config("database.connections.{$baseConnection}") ?? [];
            
            // Create the overrides for the database connection configuration
            $overrides = [];
            if (env('DB_DATABASE_TESTING') !== null || isset($baseConfig['database'])) {
                $overrides['database'] = env('DB_DATABASE_TESTING', $baseConfig['database'] . '_testing');
            }
            if (env('DB_USERNAME_TESTING') !== null) {
                $overrides['username'] = env('DB_USERNAME_TESTING');
            }
            if (env('DB_PASSWORD_TESTING') !== null) {
                $overrides['password'] = env('DB_PASSWORD_TESTING');
            }
            
            TestingConnectionCreator::createTestingDatabaseConnection($testingConnection, $overrides);
        }

        return;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

}

