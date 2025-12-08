<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginE2ETests\Support;

/**
 * @internal
 */
trait Advice // @phpstan-ignore-line
{
    /**
     * @var array<string>
     */
    private static array $badAdvices = [
        'Give the intern admin rights to the database.',
        'Hardcode all everything',
        'It doesn\'t work because the app environment is testing. Try it on production instead.',
        'Your users are basically a free QA team',
    ];

    /**
     * @var array<string>
     */
    private static array $goodAdvices = [
        'Write tests for your code to catch bugs early.',
        'Use meaningful variable and function names.',
    ];

    public function giveBadAdvice(): string
    {
        return self::$badAdvices[array_rand(self::$badAdvices)];
    }

    public function giveGoodAdvice(): string
    {
        return self::$goodAdvices[array_rand(self::$goodAdvices)];
    }

    public function giveAdvice(): string
    {
        $isGood = (bool) random_int(0, 1);

        return $isGood
            ? self::$goodAdvices[array_rand(self::$goodAdvices)]
            : self::$badAdvices[array_rand(self::$badAdvices)];
    }
}


