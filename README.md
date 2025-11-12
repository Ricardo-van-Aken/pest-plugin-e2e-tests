# Laravel Integration Testing

A Laravel package that allows for the easy creation of true integration tests, which test the laravel application while using the full desired tech stack. Tests are created with a request builder, allowing for actual HTTP(S) requests to be sent to the application. The HTTP requests contain a unique header which the application uses to differentiate between normal requests and 'test' requests. When this header is detected the application switches from its normal database to a seperate testing database in the same database cluster. 

## Table of Contents

- [Installation](#installation)
- [Setup](#setup)
  - [1. Environment Variables](#1-environment-variables)
  - [2. Publish Package Assets](#2-publish-package-assets)
    - [Config File (`integration-testing-config`)](#config-file-integration-testing-config)
    - [PHPUnit Integration Configuration (`testing-database-phpunit`)](#phpunit-integration-configuration-testing-database-phpunit)
  - [Automatic Database Connection Creation](#automatic-database-connection-creation)
  - [3. Composer Script (Optional)](#3-composer-script-optional)
  - [4. Pest Configuration (If using Pest)](#4-pest-configuration-if-using-pest)
- [Usage](#usage)
  - [Using IntegrationTestCase](#using-integrationtestcase)
  - [HTTP Request Builder Methods](#http-request-builder-methods)
- [Running Tests](#running-tests)
- [Configuration](#configuration)
  - [Custom Header Name](#custom-header-name)
  - [Custom Login Route](#custom-login-route)
- [Features](#features)
- [Requirements](#requirements)

## Installation

```bash
composer require ricardo-van-aken/laravel-integration-testing --dev
```

If the package is not published to Packagist, add the repository to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Ricardo-van-Aken/laravel-integration-testing.git"
        }
    ],
    "require-dev": {
        "ricardo-van-aken/laravel-integration-testing": "dev-main"
    }
}
```

## Setup

### 1. Environment Variables

Add these to your `.env` or `.env.testing` file:

```env
DB_DATABASE_TESTING=your_testing_database
DB_USERNAME_TESTING=your_testing_username
DB_PASSWORD_TESTING=your_testing_password
```

Also make sure your APP_URL points to the correct URL.

### 2. Publish Package Assets

Publish all package assets at once:

```bash
php artisan vendor:publish --provider="RicardoVanAken\LaravelIntegrationTesting\TestingDatabaseServiceProvider"
```

Or publish individual assets:

#### Config File (`integration-testing-config`)

```bash
php artisan vendor:publish --tag=integration-testing-config
```

**What it does:** Creates `config/integration-testing.php` in your project root.

**Purpose:** Allows you to customize the package behavior:
- `header_name`: The HTTP header name that triggers database switching (default: `X-TESTING`)
- `login_route`: The route used for user authentication in integration tests (default: `/login`)

**When to publish:** Only if you need to customize the header name or login route. The package works with defaults, so this is optional.

#### PHPUnit Integration Configuration (`testing-database-phpunit`)

```bash
php artisan vendor:publish --tag=testing-database-phpunit
```

**What it does:** Creates `phpunit.integration.xml` in your project root.

**Purpose:** Provides a separate PHPUnit configuration file specifically for integration tests with different environment variables:
- Sets `APP_ENV=testing`
- Sets `DB_CONNECTION` to your testing connection (e.g., `mysql_testing`)
- Defines the Integration test suite pointing to `tests/Integration` directory

**When to publish:** Always. This file allows you to run integration tests with different database settings than your regular unit/feature tests.

**Customization:** After publishing, adjust the `DB_CONNECTION` value in `phpunit.integration.xml` to match your preferred testing connection (e.g., `mysql_testing`, `pgsql_testing`, etc.).

### Automatic Database Connection Creation

**Note:** The package automatically creates the following connections if the corresponding base connection exists:

- `mysql_testing` (if `mysql` exists)
- `mariadb_testing` (if `mariadb` exists)
- `pgsql_testing` (if `pgsql` exists)
- `sqlite_testing` (if `sqlite` exists)
- `sqlsrv_testing` (if `sqlsrv` exists)

These connections use the same settings as the base connection, but with the testing database credentials from your environment variables (`DB_DATABASE_TESTING`, `DB_USERNAME_TESTING`, `DB_PASSWORD_TESTING`).

### 3. Composer Script (Optional)

Add this to your `composer.json` scripts section:

```json
{
    "scripts": {
        "test:integration": [
            "@php artisan config:clear --ansi",
            "@php artisan test --configuration=phpunit.integration.xml"
        ]
    }
}
```

## Usage

### Using IntegrationTestCase

The package provides an `IntegrationTestCase` base class that includes an HTTP request builder that automatically adds the `X-TESTING` header, along with a few example tests.


### HTTP Request Builder Methods

The `httpRequestBuilder()` provides a fluent API:

- `get($uri, $params = [])` - GET request
- `post($uri, $params = [])` - POST request
- `patch($uri, $params = [])` - PATCH request
- `put($uri, $params = [])` - PUT request
- `delete($uri, $params = [])` - DELETE request
- `withXsrf()` - Automatically includes CSRF token
- `actingAs($user, $password = 'password')` - Authenticates a user
- `send()` - Sends the request

## Running Tests

### Run Integration Tests

```bash
# Using the composer script
composer test:integration

# Or directly
php artisan test --configuration=phpunit.integration.xml

# Or with PHPUnit
vendor/bin/phpunit --configuration=phpunit.integration.xml
```

## Configuration

After publishing the config file (see Setup step 2), you can customize the package behavior by editing `config/integration-testing.php`:

```php
return [
    'header_name' => env('TESTING_DB_HEADER', 'X-TESTING'),
    'login_route' => env('TESTING_DB_LOGIN_ROUTE', '/login'),
];
```

### Custom Header Name

You can change the HTTP header name that triggers database switching:

```env
TESTING_DB_HEADER=X-CUSTOM-TESTING-HEADER
```

### Custom Login Route

If your application uses a different login route, set it in the config:

```env
TESTING_DB_LOGIN_ROUTE=/custom/login
```

## Features

- Automatically creates `{connection}_testing` connections for all database drivers
- Provides `IntegrationTestCase` with HTTP client and request builder
- Automatically includes `X-TESTING` header in all test requests
- Switches database connection based on HTTP header presence

## Requirements

- PHP ^8.1
- Laravel ^10.0|^11.0|^12.0
- Guzzle HTTP Client (automatically installed)
