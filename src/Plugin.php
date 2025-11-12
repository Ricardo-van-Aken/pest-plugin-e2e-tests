<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginIntegrationTests;

use Pest\Contracts\Plugins\Bootable;
use RicardoVanAken\PestPluginIntegrationTests\IntegrationTestCase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * @internal
 */
final class Plugin implements Bootable
{
    /**
     * Boots the plugin.
     * This is called after Pest initializes, so we can safely use pest()->extend()
     */
    public function boot(): void
    {
        // Log that boot was called (useful for debugging)
        if (function_exists('error_log')) {
            error_log('[PestPluginIntegrationTests] Plugin::boot() called');
        }

        // Automatically extend tests in 'Integration' directory with IntegrationTestCase
        // We need to use absolute paths because pest() is called from Plugin::boot()
        // which means Backtrace::file() returns Plugin.php, not the test file
        $cwd = getcwd();
        $testsPath = $cwd . DIRECTORY_SEPARATOR . 'tests';
        $integrationPath = $testsPath . DIRECTORY_SEPARATOR . 'Integration';
        
        // Try to find the tests directory (could be 'tests' or 'Tests')
        if (!is_dir($testsPath)) {
            $testsPath = $cwd . DIRECTORY_SEPARATOR . 'Tests';
            $integrationPath = $testsPath . DIRECTORY_SEPARATOR . 'Integration';
        }
        
        if (function_exists('error_log')) {
            error_log('[PestPluginIntegrationTests] CWD: ' . $cwd);
            error_log('[PestPluginIntegrationTests] Tests path: ' . $testsPath);
            error_log('[PestPluginIntegrationTests] Integration path: ' . $integrationPath);
            error_log('[PestPluginIntegrationTests] Integration exists: ' . (is_dir($integrationPath) ? 'yes' : 'no'));
        }
        
        // Use absolute path - pass the directory, ->in() will handle finding files
        if (is_dir($integrationPath)) {
            $absolutePath = realpath($integrationPath);
            if ($absolutePath) {
                // Pass the absolute directory path - UsesCall will resolve it correctly
                pest()->extend(IntegrationTestCase::class)
                    ->use(DatabaseTruncation::class)
                    ->in($absolutePath);
            }
        }
    }
}