# Laravel End-To-End Testing

A Laravel package that allows for the easy creation of true end-to-end tests, which test the laravel application while using the full desired tech stack. Tests are created with a request builder, allowing for actual HTTP(S) requests to be sent to the application. The HTTP requests contain a unique header which the application uses to differentiate between normal requests and 'test' requests. When this header is detected the application automatically switches from its normal storage to separate testing storage for databases, cache, queues, and sessions, ensuring complete isolation between test and production environments. 

## Features

- **Storage Switching:** Automatically switches databases, cache, queues, and sessions to testing storage when `X-TESTING` header is detected
- **HTTP Request Builder:** Provides `E2ETestCase` with fluent HTTP client and request builder
- **Test Stubs:** Includes ready-to-use test stubs for common Laravel features (authentication, registration, settings, etc.)

## Requirements

- PHP ^8.1
- Laravel ^12.0

## Table of Contents

- [Installation](#installation)
- [Setup](#setup)
  - [1. Environment Configuration](#1-environment-configuration)
    - [Application Environment Variables (`.env`)](#application-environment-variables-env)
    - [Test Code Environment Variables (`phpunit.e2e.xml`)](#test-code-environment-variables-phpunite2exml)
  - [2. Publish Package Assets (Optional)](#2-publish-package-assets)
    - [Config File (`e2e-testing-config`)](#config-file-e2e-testing-config)
    - [E2E Test Stubs (`e2e-tests`)](#e2e-test-stubs-e2e-tests)
  - [3. Composer Script (Optional)](#3-composer-script-optional)
- [Storage Switching](#storage-switching)
  - [Database Switching](#database-switching)
  - [Cache Switching](#cache-switching)
  - [Queue Switching](#queue-switching)
  - [Session Switching](#session-switching)
- [Usage](#usage)
  - [Using E2ETestCase](#using-e2etestcase)
  - [HTTP Request Builder Methods](#http-request-builder-methods)
  - [Running Tests](#running-tests)

## Installation

```bash
composer require ricardo-van-aken/laravel-integration-testing
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

### 1. Environment Configuration

E2E tests work by sending HTTP requests to your application. When the application receives a request with the `X-TESTING` header, it automatically switches to testing storage (database, cache, queues, sessions). These connections are created using a combination of the default connection and values from your `.env` file. 

#### Application Environment Variables (`.env`)

Add these optional environment variables to your application's `.env` file to override the default testing storage configuration. These values are used by the application when it receives test requests with the `X-TESTING` header.

**Note:** All environment variables are optional. If not provided, the package will use defaults based on your base configuration (e.g., `mysql` → `mysql_testing`, default Redis DB numbers, etc.).

**Testing Database Configuration (optional overrides):**
```env
# Override the testing database connection name. Defaults to {baseConnection}_testing, where baseConnection is the
# default database connection
DB_CONNECTION_TESTING=mysql_testing
```

*If using mysql for the database*
```env
# Override the database name. Defaults to {baseDatabase}_testing, where baseDatabase is the database name from the
# baseConnection
DB_DATABASE_TESTING=your_testing_database

# Override the database credentials. Defaults to default connection credentials
DB_USERNAME_TESTING=your_testing_username
DB_PASSWORD_TESTING=your_testing_password
```

**Testing Cache Configuration (optional overrides):**
```env
# Override the testing cache store name. Defaults to {baseStore}_testing, where baseStore is the default cache store
CACHE_STORE_TESTING=redis_testing
```

*If using redis for cache*
```env
# Override the Redis connection name for cache. Defaults to {baseConnection}_testing, where baseConnection is the
# Redis connection used by the base cache store
REDIS_CACHE_CONNECTION_TESTING=default_testing

# If the redis connection in REDIS_CACHE_CONNECTION_TESTING does not yet exist in the config, it will be created
# automatically. These env variables overwrite values in this redis connection.
# Override the Redis database number for cache. Defaults to 11
REDIS_CACHE_DB_TESTING=11
```

**Testing Queue Configuration (optional overrides):**
```env
# Override the testing queue connection name. Defaults to {baseConnection}_testing, where baseConnection is the
# default queue connection
QUEUE_CONNECTION_TESTING=redis_testing
```

*If using redis for queues*
```env
# Redis queue connection overrides:
# Override the redis connection name. Defaults to {baseConnection}_testing, where baseConnection is the connection
REDIS_QUEUE_CONNECTION_TESTING=default_testing

# Override the queue name. Defaults to the queue name from the base queue connection configuration
REDIS_QUEUE_TESTING=testing_queue

# Override the retry_after value. Defaults to the retry_after value from the base queue connection configuration
REDIS_QUEUE_RETRY_AFTER_TESTING=90

# If the redis connection in REDIS_QUEUE_CONNECTION_TESTING does not yet exist in the config, it will be created
# automatically. These env variables overwrite values in this redis connection.
# Override the Redis database number for queues. Defaults to 13
REDIS_QUEUE_DB_TESTING=13
```

**Testing Session Configuration (optional overrides):**

*If using redis for sessions*
```env
# Override the testing session connection name. Defaults to {baseConnection}_testing, where baseConnection is the
# Redis connection configured in session.connection (or 'default' if not set)
SESSION_CONNECTION_TESTING=default_testing

# If the redis connection in SESSION_CONNECTION_TESTING does not yet exist in the config, it will be created
# automatically. These env variables overwrite values in this redis connection.
# Override the Redis database number for sessions. Defaults to 12
REDIS_SESSION_DB_TESTING=14
```

*If using database for sessions*
```env
# Override the testing session connection name. Defaults to {baseConnection}_testing, where baseConnection is the
# database connection configured in session.connection (or the default database connection if not set)
SESSION_CONNECTION_TESTING=mysql_testing

# Override the database name. Defaults to {baseDatabase}_testing, where baseDatabase is the database name from the
# baseConnection
DB_SESSION_DATABASE_TESTING=your_testing_database

# Override the database credentials. Defaults to default connection credentials
DB_SESSION_USERNAME_TESTING=your_testing_username
DB_SESSION_PASSWORD_TESTING=your_testing_password
```


#### Test Code Environment Variables (`phpunit.e2e.xml`)

Publish the PHPUnit configuration file:

```bash
php artisan vendor:publish --tag=e2e-testing-phpunit
```

**Purpose** Set environment variables to be used in the end-to-end tests

**When to publish:** Always. This file ensures your test code can connect to the correct application URL.

**After publishing, configure `phpunit.e2e.xml`:**

Set `APP_URL` to the URL where your application is running and where the E2E tests should send their HTTP requests. For example: `https://your-test-domain.com` or `http://nginx`.


### 2. Publish Package Assets (Optional)

Publish all package assets at once:

```bash
php artisan vendor:publish --provider="RicardoVanAken\PestPluginE2ETests\TestingServiceProvider"
```

Or publish individual assets:

#### Config File (`e2e-testing-config`)

```bash
php artisan vendor:publish --tag=e2e-testing-config
```

**Purpose:** Allows you to customize the package behavior:
- `header_name`: The HTTP header name that triggers storage switching (default: `X-TESTING`)
- `login_route`: The route used for user authentication in E2E tests (default: `login.store`)
- `two_factor_challenge_route`: The route for submitting 2FA challenges (default: `two-factor.login`)
- `two_factor_challenge_location_route`: The route to check if login redirected to 2FA (default: `two-factor.login`)

**When to publish:** Only if you need to customize the header name or authentication routes. The package works with defaults, so this is optional.

#### E2E Test Stubs (`e2e-tests`)

```bash
php artisan vendor:publish --tag=e2e-tests
```

**What it does:** Creates E2E test files in your `tests/E2E` directory.

**Purpose:** Provides example E2E tests based on Laravel 12 starterkit feature tests. These tests demonstrate how to use `E2ETestCase` and the `httpRequestBuilder()` method to make actual HTTP(S) requests to your application, handle authentication, and thus create true end-to-end tests using this package.

**When to publish:** When you want to see examples of how to write E2E tests using `E2ETestCase` and the request builder. Use these as a starting point for writing your own E2E tests.

**Note:** The published tests will be placed in the `tests/E2E` directory. The plugin automatically configures Pest to use `E2ETestCase` for tests in this directory.

### 3. Composer Script (Optional)

Add this to your `composer.json` scripts section:

```json
{
    "scripts": {
        "test:e2e": [
            "@php artisan config:clear --ansi",
            "@php artisan test -c phpunit.e2e.xml"
        ]
    }
}
```

**What it does:** This script provides a convenient command to run E2E tests. It clears the Laravel configuration cache and then runs tests using the `phpunit.e2e.xml` configuration file, which ensures your tests use the correct testing storage configuration.

## Storage Switching

When the application receives a request with the `X-TESTING` header, it automatically switches from default storage to separate testing storage for databases, cache, queues, and sessions. This ensures complete isolation between test and production environments.

**Note:** Some storage drivers require additional setup (such as creating an extra mysql database). See the individual sections below for details.

### Database Switching

The application automatically switches from the default database connection to a `{connection}_testing` connection (for example: `mysql` → `mysql_testing`). This connection is automatically created in the config if it does not exist yet.

**Supported and Tested:** Only the MySQL driver is currently tested and officially supported.

**Additional Setup Required:** You must create a separate testing database in your database server. The package will use the credentials from `DB_DATABASE_TESTING`, `DB_USERNAME_TESTING`, and `DB_PASSWORD_TESTING` to connect to this database.


### Cache Switching

The application automatically switches from the default cache store to a `{store}_testing` store (for example: `redis` → `redis_testing`).

**Supported and Tested:** Only the Redis driver is currently tested and officially supported.

**Additional Setup Required:** If using Redis for cache, ensure the Redis database number specified in `REDIS_CACHE_DB_TESTING` exists in your Redis server. Redis databases are numbered 0-15 by default, so make sure you're using an available database number.


### Queue Switching

The application automatically switches from the default queue connection to a `{connection}_testing` store (for example: `redis` → `redis_testing`).

**Supported and Tested:** Only the Redis driver is currently tested and officially supported.

**Redis Queue:**
- Switches to a separate Redis database number specified by `REDIS_QUEUE_DB_TESTING` (default: 13)

**Additional Setup Required:** Ensure the Redis database number specified in `REDIS_QUEUE_DB_TESTING` exists in your Redis server. Redis databases are numbered 0-15 by default, so make sure you're using an available database number.


### Session Switching

**Supported and Tested:** Only Redis is currently tested and officially supported.

**Redis Session:**
- Switches to a separate Redis database number specified by `REDIS_SESSION_DB_TESTING` (default: 12)

**Additional Setup Required:** Ensure the Redis database number specified in `REDIS_SESSION_DB_TESTING` exists in your Redis server. Redis databases are numbered 0-15 by default, so make sure you're using an available database number.

## Usage

### Using E2ETestCase

The package provides an `E2ETestCase` base class that includes an HTTP request builder that automatically adds the `X-TESTING` header to all requests. This ensures that the application switches to testing storage (database, cache, queues, sessions) when processing test requests.


### HTTP Request Builder Methods

The `httpRequestBuilder()` provides the following methods for sending requests to your application:

**HTTP Methods:**
- `get($uri, $params = [])` - GET request
- `post($uri, $params = [])` - POST request
- `patch($uri, $params = [])` - PATCH request
- `put($uri, $params = [])` - PUT request
- `delete($uri, $params = [])` - DELETE request

**Request Configuration:**
- `withHeaders(array $headers)` - Add custom headers to the request
- `withRequestLogging()` - Enable request logging (logs method, URI, headers, body, and options)
- `refreshXsrf()` - Refresh the CSRF token

**Authentication:**
- `actingAs($user, $password = 'password', $recoveryCode = null)` - Authenticates a user. Automatically handles login and 2FA if enabled(assuming laravel's fortify is used). The `$recoveryCode` parameter is used for 2FA authentication.

**Execution:**
- `send()` - Sends the request and returns the response. You can also invoke the builder directly (`$this->httpRequestBuilder()->get('/')->send()` can be written as `$this->httpRequestBuilder()->get('/')()`)

### Running Tests

```bash
# Using the composer script
composer test:e2e

# Or directly
php artisan test -c phpunit.e2e.xml
```
