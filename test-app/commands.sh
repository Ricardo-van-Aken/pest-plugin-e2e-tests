# Run e2e tests
docker exec -u testrunner laravel_app sh -c 'php artisan test -c phpunit.e2e.xml --filter "users can authenticate using the login screen"'