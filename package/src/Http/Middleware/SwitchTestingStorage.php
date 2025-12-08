<?php

namespace RicardoVanAken\PestPluginE2ETests\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Container\Container;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;
use RicardoVanAken\PestPluginE2ETests\Support\TestingConnectionNaming;

class SwitchTestingStorage
{
    /**
     * Handle an incoming request by switching to testing storage when required.
     */
    public function handle(Request $request, Closure $next)
    {
        error_log('SwitchTestingStorage middleware executed');
        
        $headerName = config('e2e-testing.header_name', 'X-TESTING');
        
        if ($request->hasHeader($headerName)) {
            TestingEnvironmentSwitcher::switchSessionConnection();
        }

        return $next($request);
    }
}

