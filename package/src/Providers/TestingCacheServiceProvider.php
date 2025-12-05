<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;

class TestingCacheServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $testingStore = TestingEnvironmentSwitcher::getTestingCacheStore();

        // Create the testing store if it doesn't already exist
        if (! config("cache.stores.{$testingStore}")) {
            $this->createTestingStore($testingStore);
        } else {
            Log::info(
                "[LaravelE2ETesting] Testing cache store '{$testingStore}' already exists. Skipping " .
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
     * Create a testing cache store by deriving the base store from the testing store name.
     * The base store is used as a template for the testing store configuration.
     *
     * @param string $testingStore The name of the testing store to create
     * @return bool True if the store was created, false otherwise
     */
    protected function createTestingStore(string $testingStore): bool
    {
        // Derive the base store name by removing _testing from the end of the testing store name
        if (! str_ends_with($testingStore, '_testing')) {
            Log::warning(
                "[LaravelE2ETesting] Cannot determine base store for testing cache store '{$testingStore}'. " .
                "For automatic test store creation, the testing store name must follow the pattern " .
                "'{baseStore}_testing' (e.g., 'redis_testing'). Alternatively, define the store manually " .
                "in the config: 'cache.stores.{$testingStore}'."
            );

            return false;
        }

        $baseStore = substr($testingStore, 0, -8); // Remove '_testing' (8 characters) from the end

        // Get the base store configuration
        $baseConfig = config("cache.stores.{$baseStore}");
        if (! $baseConfig) {
            Log::warning(
                "[LaravelE2ETesting] Base cache store '{$baseStore}' not found. " .
                "Cannot create testing store '{$testingStore}'. " .
                "Make sure the base store is defined in the config: 'cache.stores.{$baseStore}'."
            );

            return false;
        }

        // Configure testing-specific cache store
        $driver = $baseConfig['driver'] ?? null;
        switch ($driver) {
            case 'array':
                // Array cache is already isolated per process, but create store for consistency
                break;

            case 'redis':
                $this->createTestingStoreRedis($baseStore, $testingStore);
                break;

            default:
                Log::warning(
                    "[LaravelE2ETesting] Unknown or unsupported cache driver '{$driver}'. Cache isolation for " .
                    "testing may not work correctly."
                );
                return false;
        }

        return true;
    }

    /**
     * Configure Redis cache store for testing.
     */
    protected function createTestingStoreRedis(string $baseStore, string $testingStore): void
    {
        $baseConfig = config("cache.stores.{$baseStore}");

        // Build testing config from the base config
        $testingConfig = $baseConfig;

        // Always override the connection to use the testing Redis connection
        $testingConfig['connection'] = env('REDIS_CACHE_CONNECTION_TESTING', $baseConfig['connection'] . '_testing');

        config([
            "cache.stores.{$testingStore}" => $testingConfig,
        ]);

        Log::info(
            "[LaravelE2ETesting] Created testing Redis cache store '{$testingStore}'."
        );

        // Create the Redis database connection for cache
        if (! config("database.redis." . $testingConfig['connection'])) {
            Log::info(
                "[LaravelE2ETesting] Testing Redis cache connection '{$testingConfig['connection']}' does not exist. " .
                "Attempting automatic creation."
            );
            $this->createDatabaseTestingConnectionRedis($testingConfig['connection']);
        } else {
            Log::info(
                "[LaravelE2ETesting] Testing Redis cache connection '{$testingConfig['connection']}' already exists. " .
                "Skipping automatic creation."
            );
        }

        return;
    }

    /**
     * Create a testing Redis connection for cache usage.
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
                "[LaravelE2ETesting] Cannot determine base connection for testing cache connection '{$testingConnection}'. " .
                "For automatic test connection creation, the testing connection name must follow the pattern " .
                "'{baseConnection}_testing' (e.g., 'cache_testing'). Alternatively, define the connection manually " .
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
                "Cannot create testing cache connection '{$testingConnection}'. " .
                "Make sure the base connection is defined in the config: 'database.redis.{$baseConnection}'."
            );

            return false;
        }

        // Build testing config from the base config
        $testingConfig = $baseConfig;

        // Override the database number if the environment variable is set
        $testingConfig['database'] = env('REDIS_CACHE_DB_TESTING', 11);

        config([
            "database.redis.{$testingConnection}" => $testingConfig,
        ]);

        Log::info(
            "[LaravelE2ETesting] Created testing Redis cache connection '{$testingConnection}'."
        );

        return true;
    }
}

