<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;

class TestingSessionServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The session config does not allow for creation of new connections. We will have to change the config at
        // runtime(when a testing request is received). We can make sure that the required database connections or
        // cache stores exists.
    
        $driver = config('session.driver');
        switch ($driver) {
            case 'redis':
                $testingConnection = TestingEnvironmentSwitcher::getTestingSessionConnection();
                
                // Create the Redis database connection for sessions
                if (! config("database.redis." . $testingConnection)) {
                    Log::info(
                        "[LaravelE2ETesting] Testing Redis session connection '{$testingConnection}' does not " . 
                        "exist. Attempting automatic creation."
                    );
                    $this->createDatabaseTestingConnectionRedis($testingConnection);
                } else {
                    Log::info(
                        "[LaravelE2ETesting] Testing Redis session connection '{$testingConnection}' already " . 
                        "exists. Skipping automatic creation."
                    );
                }
                
                break;
            // case 'database':
            //     $baseConnection = config('session.connection') ?? config('database.default');
            //     $testingConnection = env('SESSION_CONNECTION_TESTING', $baseConnection . '_testing');
                
            //     // Create the database connection for sessions
            //     if (! config("database.connections." . $testingConnection)) {
            //         $this->createDatabaseTestingConnection($testingConnection);
            //     } else {
            //         Log::info(
            //             "[LaravelE2ETesting] Testing database session connection '{$testingConnection}' already " . 
            //             "exists. Skipping automatic creation."
            //         );
            //     }
            //     break;
            case 'array':
                // Array sessions are already isolated per process, no action needed
                break;
            default:
                Log::warning(
                    "[LaravelE2ETesting] Unknown or unsupported session driver '{$driver}'. Session isolation for " . 
                    "testing may not work correctly."
                );
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

    /**
     * Create a testing Redis connection for session usage.
     *
     * The base connection is used as a template for the testing connection configuration.
     *
     * @param string $testingConnection The name of the testing Redis connection to create
     * @return bool True if the connection was created, false otherwise
     */
    protected function createDatabaseTestingConnectionRedis(string $testingConnection): bool
    {
        // Derive the base connection name by removing _testing from the end of the testing connection name
        if (! str_ends_with($testingConnection, '_testing')) {
            Log::warning(
                "[LaravelE2ETesting] Cannot determine base connection for testing session connection '{$testingConnection}'. " .
                "For automatic test connection creation, the testing connection name must follow the pattern " .
                "'{baseConnection}_testing' (e.g., 'default_testing'). Alternatively, define the connection manually " .
                "in the config: 'database.redis.{$testingConnection}'."
            );

            return false;
        }

        $baseConnection = substr($testingConnection, 0, -8); // Remove '_testing' (8 characters) from the end

        // Get the base connection configuration
        $baseConfig = config("database.redis.{$baseConnection}");
        if (! $baseConfig) {
            Log::warning(
                "[LaravelE2ETesting] Base Redis connection '{$baseConnection}' not found. " .
                "Cannot create testing session connection '{$testingConnection}'. " .
                "Make sure the base connection is defined in the config: 'database.redis.{$baseConnection}'."
            );

            return false;
        }

        // Build testing config from the base config
        $testingConfig = $baseConfig;

        // Override the database number if the environment variable is set
        $testingConfig['database'] = env('REDIS_SESSION_DB_TESTING', 12);

        config([
            "database.redis.{$testingConnection}" => $testingConfig,
        ]);

        Log::info(
            "[LaravelE2ETesting] Created testing Redis session connection '{$testingConnection}'."
        );

        return true;
    }
}

