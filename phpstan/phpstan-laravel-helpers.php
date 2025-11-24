<?php

declare(strict_types=1);

namespace Illuminate\Foundation\Testing {
    abstract class TestCase extends \PHPUnit\Framework\TestCase {}
}

namespace Illuminate\Http {
    class Request
    {
        public function hasHeader(string $name): bool {}
        public function header(string $name, $default = null) {}
    }
}

namespace Illuminate\Routing {
    class RouteRegistrar
    {
        public function group(array $attributes, \Closure $routes): void {}
    }

    class Route
    {
        public function middleware($middleware) {}
        public function json($data = [], int $status = 200, array $headers = []) {}
    }
}

namespace {
    function config_path(string $path = ''): string {}
    function app_path(string $path = ''): string {}
    function storage_path(string $path = ''): string {}
    function resource_path(string $path = ''): string {}
    function base_path(string $path = ''): string {}
    function config($key = null, $default = null) {}
    function request(): \Illuminate\Http\Request {}
    function route(string $name, array $parameters = [], bool $absolute = true): string {}
    function response($content = '', int $status = 200, array $headers = []) {}
    function csrf_token(): string {}
}


