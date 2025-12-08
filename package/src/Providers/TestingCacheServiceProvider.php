<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionCreator;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

class TestingCacheServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $testingStore = TestingConnectionNaming::getTestingCacheStore();

        // Create the testing store if it doesn't already exist
        if (! config("cache.stores.{$testingStore}")) {
            $this->createTestingStore($testingStore);
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
     * Create a testing cache store by deriving the base store from the testing store name.
     * The base store is used as a template for the testing store configuration.
     *
     * @param string $testingStore The name of the testing store to create
     * @return void
     */
    protected function createTestingStore(string $testingStore): void
    {
        $baseStore = TestingConnectionNaming::deriveBaseConnection($testingStore);

        // Get the base store configuration
        $baseConfig = config("cache.stores.{$baseStore}");
        if (! $baseConfig) {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Base cache store '{$baseStore}' not found. Cannot create testing store " .
                "'{$testingStore}'. Make sure the base store is defined in the config: " .
                "'cache.stores.{$baseStore}, or define the testing store manually in the config: " .
                "'cache.stores.{$testingStore}'."
            );
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
                throw new \RuntimeException("[LaravelE2ETesting] " .
                    "Unknown or unsupported cache driver '{$driver}'. "
                );
        }

        return;
    }

    /**
     * Configure Redis cache store for testing.
     */
    protected function createTestingStoreRedis(string $baseStore, string $testingStore): void
    {
        $baseConfig = config("cache.stores.{$baseStore}");

        // Build testing config from the base config and overrides
        $testingConfig = $baseConfig;
        $testingConfig['connection'] = env('REDIS_CACHE_CONNECTION_TESTING', $baseConfig['connection'] . '_testing');

        config(["cache.stores.{$testingStore}" => $testingConfig,]);

        Log::info("[LaravelE2ETesting] " . 
            "Created testing Redis cache store '{$testingStore}'."
        );

        // Create the Redis database connection for cache if it doesn't exist
        if (! config("database.redis." . $testingConfig['connection'])) {
            TestingConnectionCreator::createTestingRedisConnection($testingConfig['connection'], [
                'database' => env('REDIS_CACHE_DB_TESTING', 11),
            ]);
        }

        return;
    }

}

