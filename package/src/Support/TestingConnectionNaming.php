<?php

namespace RicardoVanAken\PestPluginE2ETests\Support;

class TestingConnectionNaming
{
    /**
     * Get the testing database connection name.
     *
     * @return string The testing database connection name
     */
    public static function getTestingDatabaseConnection(): string
    {
        $baseConnection = config('database.default');
        $testingConnection = env('DB_CONNECTION_TESTING', $baseConnection . '_testing');

        return $testingConnection;
    }

    /**
     * Get the testing queue connection name.
     *
     * @return string The testing queue connection name
     */
    public static function getTestingQueueConnection(): string
    {
        $baseConnection = config('queue.default');
        $testingConnection = env('QUEUE_CONNECTION_TESTING', $baseConnection . '_testing');

        return $testingConnection;
    }

    /**
     * Get the testing session connection name based on the driver.
     *
     * @param string $driver The session driver ('redis' or 'database')
     * @return string The testing session connection name
     * @throws \InvalidArgumentException If the driver is invalid
     */
    public static function getTestingSessionConnection(string $driver): string
    {
        switch ($driver) {
            case 'redis':
                $baseConnection = config('session.connection') ?? 'default';
                break;
            case 'database':
                $baseConnection = config('session.connection') ?? config('database.default');
                break;
            default:
                throw new \InvalidArgumentException("Invalid session driver: {$driver}");
        }

        $testingConnection = env('SESSION_CONNECTION_TESTING', $baseConnection . '_testing');

        return $testingConnection;
    }

    /**
     * Get the testing cache store name.
     *
     * @return string The testing cache store name
     */
    public static function getTestingCacheStore(): string
    {
        $baseStore = config('cache.default');
        $testingStore = env('CACHE_STORE_TESTING', $baseStore . '_testing');

        return $testingStore;
    }

    /**
     * Derive the base connection name from the testing connection name.
     *
     * @param string $testingConnection The testing connection name
     * @return string The base connection name
     * @throws \RuntimeException If the base connection cannot be derived
     */
    public static function deriveBaseConnection(string $testingConnection): string
    {
        // Derive the base connection name by removing _testing from the end of the testing connection name
        if (! str_ends_with($testingConnection, '_testing')) {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Cannot determine base connection for testing connection '{$testingConnection}'. " .
                "For automatic test connection creation, the testing connection name must follow the pattern " .
                "'{baseConnection}_testing' (e.g., 'default_testing'). Alternatively, define the connection manually."
            );
        }

        return substr($testingConnection, 0, -8); // Remove '_testing' (8 characters) from the end
    }
}

