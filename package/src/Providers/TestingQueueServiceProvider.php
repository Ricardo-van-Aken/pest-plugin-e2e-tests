<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;

class TestingQueueServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $testingConnection = TestingEnvironmentSwitcher::getTestingQueueConnection();

        // Create the testing connection if it doesn't already exist
        if (! config("queue.connections.{$testingConnection}")) {
            $this->createTestingConnection($testingConnection);
        } else {
            Log::info(
                "[LaravelE2ETesting] Testing queue connection '{$testingConnection}' already exists. Skipping " . 
                "automatic creation."
            );
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
     * Create a testing queue connection by deriving the base connection from the testing connection name.
     * The base connection is used as a template for the testing connection configuration.
     *
     * @param string $testingConnection The name of the testing connection to create
     * @return bool True if the connection was created, false otherwise
     */
    protected function createTestingConnection(string $testingConnection): bool
    {
        // Derive the base connection name by removing _testing from the end of the testing connection name
        if (! str_ends_with($testingConnection, '_testing')) {
            Log::warning(
                "[LaravelE2ETesting] Cannot determine base connection for testing queue connection '{$testingConnection}'. " .
                "For automatic test connection creation, the testing connection name must follow the pattern " .
                "'{baseConnection}_testing' (e.g., 'redis_testing'). Alternatively, define the connection manually " .
                "in the config: 'queue.connections.{$testingConnection}'."
            );

            return false;
        }
        
        $baseConnection = substr($testingConnection, 0, -8); // Remove '_testing' (8 characters) from the end
        
        // Get the base connection configuration
        $baseConfig = config("queue.connections.{$baseConnection}");
        if (! $baseConfig) {
            Log::warning(
                "[LaravelE2ETesting] Base queue connection '{$baseConnection}' not found. " .
                "Cannot create testing connection '{$testingConnection}'. " .
                "Make sure the base connection is defined in the config: 'queue.connections.{$baseConnection}'."
            );

            return false;
        }
        
        // Configure testing-specific queue connection
        $driver = $baseConfig['driver'] ?? null;
        switch ($driver) {
            case 'sync':
                // Sync queue is already isolated per process, no action needed
                break;
            case 'redis':
                $this->createTestingConnectionRedis($baseConnection, $testingConnection);
                break;
            default:
                Log::warning(
                    "[LaravelE2ETesting] Unknown or unsupported queue driver '{$driver}'. Queue isolation for " . 
                    "testing may not work correctly."
                );
                break;
        }

        return true;
    }

    /**
     * Configure Redis queue connection for testing.
     */
    protected function createTestingConnectionRedis(string $baseConnection, string $testingConnection): void
    {
        $baseConfig = config("queue.connections.{$baseConnection}");

        // Build testing config from the base config
        $testingConfig = $baseConfig;

        // Always override the connection if base config has it
        $testingConfig['connection'] = env('REDIS_QUEUE_CONNECTION_TESTING', $baseConfig['connection'] . "_testing");

        // Override the queue if base config has it and env variable is set
        if (isset($baseConfig['queue']) && env('REDIS_QUEUE_TESTING') !== null) {
            $testingConfig['queue'] = env('REDIS_QUEUE_TESTING');
        }

        // Override the retry_after if base config has it and env variable is set
        if (isset($baseConfig['retry_after']) && env('REDIS_QUEUE_RETRY_AFTER_TESTING') !== null) {
            $testingConfig['retry_after'] = env('REDIS_QUEUE_RETRY_AFTER_TESTING');
        }

        config([
            "queue.connections.{$testingConnection}" => $testingConfig,
        ]);

        Log::info(
            "[LaravelE2ETesting] Created testing queue connection '{$testingConnection}'."
        );

        // Create the Redis database connection for queues
        if (! config("database.redis." . $testingConfig['connection'])) {
            Log::info(
                "[LaravelE2ETesting] Testing Redis queue connection '{$testingConfig['connection']}' does not " . 
                "exists. Attempting automatic creation."
            );
            $this->createDatabaseTestingConnectionRedis($testingConfig['connection']);
        } else {
            Log::info(
                "[LaravelE2ETesting] Testing Redis queue connection '{$testingConfig['connection']}' already " . 
                "exists. Skipping automatic creation."
            );
        }

        return;
    }

    /**
     * Create a testing Redis connection for queue usage.
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
                "[LaravelE2ETesting] Cannot determine base connection for testing connection '{$testingConnection}'. " .
                "For automatic test connection creation, the testing connection name must follow the pattern " .
                "'{baseConnection}_testing' (e.g., 'queue_testing'). Alternatively, define the connection manually " .
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
                "Cannot create testing connection '{$testingConnection}'. " .
                "Make sure the base connection is defined in the config: 'database.redis.{$baseConnection}'."
            );

            return false;
        }

        // Build testing config from the base config
        $testingConfig = $baseConfig;

        // Override the database number if the environment variable is set
        $testingConfig['database'] = env('REDIS_QUEUE_DB_TESTING', 13);

        config([
            "database.redis.{$testingConnection}" => $testingConfig,
        ]);

        Log::info(
            "[LaravelE2ETesting] Created testing Redis queue connection '{$testingConnection}'."
        );

        return true;
    }
}

