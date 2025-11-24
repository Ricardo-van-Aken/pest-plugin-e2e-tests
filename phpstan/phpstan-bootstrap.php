<?php

/**
 * PHPStan bootstrap file for Larastan.
 *
 * Larastan expects the LARAVEL_VERSION constant to be defined, but packages
 * don't boot a full Laravel application. We define it here so Larastan knows.
 */

if (!defined('Larastan\\Larastan\\LARAVEL_VERSION')) {
    define('Larastan\\Larastan\\LARAVEL_VERSION', '12.0.0');
}

$TEST_APP_ROOT = __DIR__ . '/test-app/laravel_root'; // <<-- adjust to your test app root

if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        global $TEST_APP_ROOT;
        $ret = $TEST_APP_ROOT;
        if ($path !== '') {
            // normalize slashes safely
            $ret = rtrim($ret, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
        }
        return $ret;
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return base_path('config' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '')
    {
        return base_path('app' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return base_path('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return base_path('resources' . ($path !== '' ? DIRECTORY_SEPARATOR . $path : ''));
    }
}
