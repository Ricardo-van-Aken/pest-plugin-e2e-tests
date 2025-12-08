<?php

namespace RicardoVanAken\PestPluginE2ETests\Support;

use Illuminate\Support\Facades\Log;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

class TestingConnectionCreator
{
    /**
     * Create a testing Redis connection.
     *
     * The base connection is used as a template for the testing connection configuration.
     *
     * @param string $testingConnection The name of the testing Redis connection to create
     * @param array<string, mixed> $overrides Array of config key => value overrides to apply
     * @return void
     * @throws \RuntimeException If the base connection cannot be derived or is not found
     */
    public static function createTestingRedisConnection(string $testingConnection, array $overrides): void
    {
        $baseConnection = TestingConnectionNaming::deriveBaseConnection($testingConnection);

        // Get the base connection configuration
        $baseConfig = config("database.redis.{$baseConnection}");
        if (! $baseConfig) {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Base Redis connection '{$baseConnection}' not found. Cannot create testing redis connection " . 
                "'{$testingConnection}'. Make sure the base connection is defined in the config: " .
                "'database.redis.{$baseConnection}, or define the testing connection manually in the config: " .
                "'database.redis.{$testingConnection}'."
            );
        }

        // Build testing config from the base config and overrides
        $testingConfig = $baseConfig;
        foreach ($overrides as $key => $value) {
            if (isset($baseConfig[$key])) {
                $testingConfig[$key] = $value;
            }
        }

        config(["database.redis.{$testingConnection}" => $testingConfig]);

        Log::info("[LaravelE2ETesting] Created testing Redis connection '{$testingConnection}'.");
        return;
    }

    /**
     * Create a testing database connection.
     *
     * The base connection is used as a template for the testing connection configuration.
     *
     * @param string $testingConnection The name of the testing database connection to create
     * @param array<string, mixed> $overrides Array of config key => value overrides to apply
     * @return void
     * @throws \RuntimeException If the base connection cannot be derived or is not found
     */
    public static function createTestingDatabaseConnection(string $testingConnection, array $overrides): void
    {
        $baseConnection = TestingConnectionNaming::deriveBaseConnection($testingConnection);

        // Get the base connection configuration
        $baseConfig = config("database.connections.{$baseConnection}");
        if (! $baseConfig) {
            throw new \RuntimeException("[LaravelE2ETesting] " .
                "Base database connection '{$baseConnection}' not found. Cannot create testing database connection " .  
                "'{$testingConnection}'. Make sure the base connection is defined in the config: " .
                "'database.connections.{$baseConnection}, or define the testing connection manually in the config: " .
                "'database.connections.{$testingConnection}'."
            );
        }

        // Build testing config from the base config and overrides
        $testingConfig = $baseConfig;
        foreach ($overrides as $key => $value) {
            if (isset($baseConfig[$key])) {
                $testingConfig[$key] = $value;
            }
        }

        config(["database.connections.{$testingConnection}" => $testingConfig]);

        Log::info("[LaravelE2ETesting] " . 
            "Created testing database connection '{$testingConnection}'."
        );

        return;
    }
}

