<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;

class TestingDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $testingConnection = TestingEnvironmentSwitcher::getTestingDatabaseConnection();

        // Create the testing connection if it doesn't already exist
        if (! config("database.connections.{$testingConnection}")) {
            Log::info(
                "[LaravelE2ETesting] Testing database connection '{$testingConnection}' does not exist. Attempting " . 
                "automatic creation."
            );
            $this->createTestingConnection($testingConnection);
        } else {
            Log::info(
                "[LaravelE2ETesting] Testing database connection '{$testingConnection}' already exists. Skipping " . 
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
     * Create a testing database connection by deriving the base connection from the testing connection name.
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
                "[LaravelE2ETesting] Cannot determine base connection for testing connection '{$testingConnection}'. " .
                "For automatic test connection creation, the testing connection name must follow the pattern " .
                "'{baseConnection}_testing' (e.g., 'mysql_testing'). Alternatively, define the connection manually " .
                "in the config: 'database.connections.{$testingConnection}'."
            );

            return false;
        }

        $baseConnection = substr($testingConnection, 0, -8); // Remove '_testing' (8 characters) from the end

        // Get the base connection configuration
        $baseConfig = config("database.connections.{$baseConnection}");
        if (! $baseConfig) {
            Log::warning(
                "[LaravelE2ETesting] Base database connection '{$baseConnection}' not found. " .
                "Cannot create testing connection '{$testingConnection}'. " .
                "Make sure the base connection is defined in the config: 'database.connections.{$baseConnection}'."
            );

            return false;
        }

        // Build testing config from the base config
        /** @var array<string, mixed> $testingConfig */
        $testingConfig = $baseConfig;
        
        // Always override the database name if base config has it
        if (isset($baseConfig['database'])) {
            $testingConfig['database'] = env('DB_DATABASE_TESTING', $baseConfig['database'] . '_testing');
        }
        
        // Override username if base config has it and env variable is set
        if (isset($baseConfig['username']) && env('DB_USERNAME_TESTING') !== null) {
            $testingConfig['username'] = env('DB_USERNAME_TESTING');
        }
        
        // Override password if base config has it and env variable is set
        if (isset($baseConfig['password']) && env('DB_PASSWORD_TESTING') !== null) {
            $testingConfig['password'] = env('DB_PASSWORD_TESTING');
        }

        config([
            "database.connections.{$testingConnection}" => $testingConfig,
        ]);

        Log::info(
            "[LaravelE2ETesting] Created testing database connection '{$testingConnection}'."
        );

        return true;
    }
}

