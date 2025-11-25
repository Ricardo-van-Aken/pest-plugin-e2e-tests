<?php

namespace RicardoVanAken\PestPluginE2ETests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class TestingQueueServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Switch queue to testing storage if header is present (receiving test requests)
        if ($this->isTestRequest()) {
            $this->switchQueueToTestingStorage();
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
     * Check if the current request is a test request.
     */
    protected function isTestRequest(): bool
    {
        return request()->hasHeader(config('e2e-testing.header_name', 'X-TESTING'));
    }

    /**
     * Switch the queue configuration to use testing storage.
     */
    protected function switchQueueToTestingStorage(): void
    {
        $queueConnection = config('queue.default');

        // Get the queue connection configuration
        $queueConfig = config("queue.connections.{$queueConnection}", []);

        if (empty($queueConfig)) {
            return;
        }

        $queueDriver = $queueConfig['driver'] ?? null;

        switch ($queueDriver) {
            case 'redis':
                $this->switchRedisQueueToTesting($queueConnection);
                break;

            case 'database':
                $this->switchDatabaseQueueToTesting($queueConnection);
                break;

            case 'sync':
                // Sync queue is already isolated per process, no action needed
                break;

            case 'sqs':
                $this->switchSqsQueueToTesting($queueConnection);
                break;

            case 'beanstalkd':
                $this->switchBeanstalkdQueueToTesting($queueConnection);
                break;

            default:
                // Unknown queue driver, log a warning
                Log::warning(
                    "[LaravelIntegrationTesting] Unknown queue driver '{$queueDriver}'. ".
                    'Queue isolation for testing may not work correctly.'
                );
                break;
        }
    }

    /**
     * Switch Redis queue to use the testing database.
     */
    protected function switchRedisQueueToTesting(string $queueConnection): void
    {
        // Get the Redis connection used by the queue (defaults to 'default' if not specified)
        $redisConnectionName = config("queue.connections.{$queueConnection}.connection", 'default');
        $redisConfig = config("database.redis.{$redisConnectionName}", []);

        if (empty($redisConfig)) {
            Log::warning(
                "[LaravelIntegrationTesting] Redis config not found for queue connection '{$redisConnectionName}'. ".
                'Queue isolation for testing may not work correctly.'
            );

            return;
        }

        $currentQueueDb = $redisConfig['database'] ?? 0;
        $testingQueueDb = (int) env('REDIS_QUEUE_DB_TESTING', 13);

        // Check if the testing and application redis queue databases are the same.
        if ($testingQueueDb === $currentQueueDb) {
            Log::warning(
                '[LaravelIntegrationTesting] Redis queue database for testing is the same as the applications Redis '.
                'queue database. This will cause tests to use the same queue as the application. '.
                "\nCurrent database number: {$currentQueueDb}. ".
                "\nTesting database number: {$testingQueueDb}. ".
                "\nPlease set REDIS_QUEUE_DB_TESTING to a different database number."
            );

            return;
        }

        // Switch the used redis queue database to the testing queue database.
        config([
            "database.redis.{$redisConnectionName}.database" => $testingQueueDb,
        ]);
    }

    /**
     * Switch database queue to use the testing database connection.
     */
    protected function switchDatabaseQueueToTesting(string $queueConnection): void
    {
        // Get the current default connection and switch to its _testing version
        $testingConnection = config('database.default').'_testing';

        // Ensure the testing connection exists
        if (config("database.connections.{$testingConnection}")) {
            // Update the queue to use the testing database connection
            config([
                "queue.connections.{$queueConnection}.connection" => $testingConnection,
            ]);
        }
    }

    /**
     * Switch SQS queue to use a testing queue name.
     */
    protected function switchSqsQueueToTesting(string $queueConnection): void
    {
        $testingQueueName = env('SQS_QUEUE_TESTING', null);

        if ($testingQueueName === null) {
            Log::warning(
                '[LaravelIntegrationTesting] SQS queue driver does not have a testing queue configured. '.
                "Set SQS_QUEUE_TESTING in your application's environment to use a separate queue for testing. ".
                'Without this, tests will use the same queue as the application.'
            );

            return;
        }

        // Switch to the testing queue name
        config([
            "queue.connections.{$queueConnection}.queue" => $testingQueueName,
        ]);
    }

    /**
     * Switch Beanstalkd queue to use a testing queue name.
     */
    protected function switchBeanstalkdQueueToTesting(string $queueConnection): void
    {
        $testingQueueName = env('BEANSTALKD_QUEUE_TESTING', null);

        if ($testingQueueName === null) {
            Log::warning(
                '[LaravelIntegrationTesting] Beanstalkd queue driver does not have a testing queue configured. '.
                "Set BEANSTALKD_QUEUE_TESTING in your application's environment to use a separate queue for testing. ".
                'Without this, tests will use the same queue as the application.'
            );

            return;
        }

        // Switch to the testing queue name
        config([
            "queue.connections.{$queueConnection}.queue" => $testingQueueName,
        ]);
    }
}
