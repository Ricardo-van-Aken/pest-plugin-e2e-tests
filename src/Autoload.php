<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginIntegrationTests;

use RicardoVanAken\PestPluginIntegrationTests;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

PestPluginIntegrationTests::uses(Example::class);

/**
 * @return TestCase
 */
function example(string $argument)
{
    return test()->example(...func_get_args()); // @phpstan-ignore-line
}

pest()->extend(PestPluginIntegrationTests\IntegrationTestCase::class)
    ->use(DatabaseTruncation::class)
    ->in('Integration');