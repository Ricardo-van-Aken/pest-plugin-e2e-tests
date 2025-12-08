<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionCreator;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

class TestingQueueServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $testingConnection = TestingConnectionNaming::getTestingQueueConnection();

        // Create the testing connection if it doesn't already exist
        if (! config("queue.connections.{$testingConnection}")) {
            $this->createTestingConnection($testingConnection);
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

    /**
     * Create a testing queue connection by deriving the base connection from the testing connection name.
     * The base connection is used as a template for the testing connection configuration.
     *
     * @param string $testingConnection The name of the testing connection to create
     * @return void
     */
    protected function createTestingConnection(string $testingConnection): void
    {
        $baseConnection = TestingConnectionNaming::deriveBaseConnection($testingConnection);
        
        // Get the base connection configuration
        $baseConfig = config("queue.connections.{$baseConnection}");
        if (! $baseConfig) {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Base queue connection '{$baseConnection}' not found. Cannot create testing connection " .
                "'{$testingConnection}'. Make sure the base connection is defined in the config: " .
                "'queue.connections.{$baseConnection}, or define the testing connection manually in the config: " .
                "'queue.connections.{$testingConnection}'."
            );
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
                throw new \RuntimeException("[LaravelE2ETesting] " .
                    "Unknown or unsupported queue driver '{$driver}'. "
                );
        }

        return;
    }

    /**
     * Configure Redis queue connection for testing.
     */
    protected function createTestingConnectionRedis(string $baseConnection, string $testingConnection): void
    {
        $baseConfig = config("queue.connections.{$baseConnection}");

        // Build testing config from the base config and overrides
        $testingConfig = $baseConfig;
        
        $testingConfig['connection'] = env('REDIS_QUEUE_CONNECTION_TESTING', $baseConfig['connection'] . "_testing");
        if (isset($baseConfig['queue']) && env('REDIS_QUEUE_TESTING') !== null) {
            $testingConfig['queue'] = env('REDIS_QUEUE_TESTING');
        }
        if (isset($baseConfig['retry_after']) && env('REDIS_QUEUE_RETRY_AFTER_TESTING') !== null) {
            $testingConfig['retry_after'] = env('REDIS_QUEUE_RETRY_AFTER_TESTING');
        }

        config(["queue.connections.{$testingConnection}" => $testingConfig]);

        Log::info("[LaravelE2ETesting] " . 
            "Created testing queue connection '{$testingConnection}'."
        );

        // Create the Redis database connection for queues
        if (! config("database.redis." . $testingConfig['connection'])) {
            TestingConnectionCreator::createTestingRedisConnection($testingConfig['connection'], [
                    'database' => env('REDIS_QUEUE_DB_TESTING', 13),
            ]);
        }

        return;
    }

}

