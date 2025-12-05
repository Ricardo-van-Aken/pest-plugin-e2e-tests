<?php

namespace RicardoVanAken\PestPluginE2ETests\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;

class SwitchTestingStorage
{
    /**
     * Handle an incoming request by switching to testing storage when required.
     */
    public function handle(Request $request, Closure $next)
    {
        // Always log that middleware is running to debug execution order
        Log::info("[LaravelE2ETesting] SwitchTestingStorage middleware executed", [
            'method' => $request->method(),
            'uri' => $request->fullUrl(),
            'route' => $request->route()?->getName() ?? $request->path(),
        ]);
        
        $headerName = config('e2e-testing.header_name', 'X-TESTING');
        $hasHeader = $request->hasHeader($headerName);
        
        Log::info("[LaravelE2ETesting] Middleware header check", [
            'header_name' => $headerName,
            'has_header' => $hasHeader,
            'headers' => array_keys($request->headers->all()),
        ]);
        
        if ($hasHeader) {
            Log::info(
                "[LaravelE2ETesting] Switching to testing environment based on X-TESTING header.",
                [
                    'method' => $request->method(),
                    'uri' => $request->fullUrl(),
                ]
            );
            TestingEnvironmentSwitcher::switchAll();

            // Clear the session manager's handler cache so it rebuilds with the new connection
            // This is necessary because Laravel caches the handler instance
            TestingEnvironmentSwitcher::resetSessionHandler();
        }

        return $next($request);
    }
}

