<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginIntegrationTests;

use Pest\Plugin;
use RicardoVanAken\PestPluginIntegrationTests\IntegrationTestCase;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

Plugin::uses(Example::class);

/**
 * @return TestCase
 */
function example(string $argument)
{
    return test()->example(...func_get_args()); // @phpstan-ignore-line
}

pest()->extend(IntegrationTestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Integration');