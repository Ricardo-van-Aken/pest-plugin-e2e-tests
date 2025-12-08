<?php

namespace RicardoVanAken\PestPluginE2ETests\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use RicardoVanAken\PestPluginE2ETests\Support\TestingEnvironmentSwitcher;
use Illuminate\Http\Request;

/**
 * Main service provider for E2E testing functionality.
 * Handles shared concerns like config merging and route registration.
 * This provider runs once and handles all shared setup.
 */
class TestingServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config if published
        $this->mergeConfigFrom(
            __DIR__.'/../../config/e2e-testing.php',
            'e2e-testing'
        );
        
        // Check for X-TESTING header on and switch environment
        $this->app->booting(function () {
            $request = request();
            if ($request) {
                $headerName = config('e2e-testing.header_name', 'X-TESTING');
                
                if ($request->hasHeader($headerName)) {
                    TestingEnvironmentSwitcher::switchAll();
                }
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish assets
        $this->publishes([
            __DIR__.'/../../config/e2e-testing.php' => config_path('e2e-testing.php'),
        ], 'e2e-testing-config');
        $this->publishes([
            __DIR__.'/../../stubs/phpunit.e2e.xml' => base_path('phpunit.e2e.xml'),
        ], 'e2e-testing-phpunit');
        $this->publishE2ETestStubs();

        // Middleware for swapping the storage on every request with the X-TESTING header
        // $router = $this->app['router'];
        // $router->prependMiddlewareToGroup('web', SwitchTestingStorage::class);
        // $router->prependMiddlewareToGroup('api', SwitchTestingStorage::class);

        // Register routes
        $this->registerTestRoutes();

    }

    /**
     * Publish every E2E test stub file.
     */
    protected function publishE2ETestStubs(): void
    {
        $stubsPath = realpath(__DIR__.'/../../stubs/tests/E2E');
        if (! is_dir($stubsPath)) {
            return;
        }

        $publishes = [];

        foreach (File::allFiles($stubsPath) as $file) {
            $relativePath = ltrim(str_replace($stubsPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);

            $publishes[$file->getPathname()] = base_path('tests/E2E/'.$relativePath);
        }

        if (! empty($publishes)) {
            $this->publishes($publishes, 'e2e-tests');
        }
    }

    /**
     * Register test routes required for E2E testing.
     */
    protected function registerTestRoutes(): void
    {
        Route::middleware('web')->group(function () {
            Route::get('/test/csrf-token', function () {
                return response()->json([
                    'csrf_token' => csrf_token(),
                ]);
            });

            Route::get('/test/requires-auth', function () {
                return response()->json(['success' => true]);
            })->middleware('auth');

            Route::post('/test/requires-auth', function () {
                return response()->json(['success' => true]);
            })->middleware('auth');

            Route::get('/test/requires-nothing', function () {
                return response()->json(['success' => true]);
            });
        });
    }
}

