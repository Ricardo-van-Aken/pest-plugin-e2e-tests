<?php

namespace RicardoVanAken\PestPluginE2ETests\Support;

trait TestingHelpers
{
    /**
     * Check if the current request is a test request (has X-TESTING header).
     */
    protected function isTestRequest(): bool
    {
        return request()->hasHeader(config('e2e-testing.header_name', 'X-TESTING'));
    }

    /**
     * Check if we're running tests (test code, not receiving test requests).
     */
    protected function isRunningTests(): bool
    {
        return config('app.env') === 'testing';
    }
}


