<?php

namespace RicardoVanAken\PestPluginE2ETests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpRequestBuilder
{
    private Client $client;
    /** @var array{method?: string, uri?: string, params?: array<string, mixed>} */
    private array $pendingRequest = [];
    /** @var array<string, string> */
    private array $headers = [];
    private ?string $xsrfToken = null;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->xsrfToken = $this->getXsrfToken();
    }

    public function withRequestLogging(): self
    {
        /** @var HandlerStack $handlerStack */
        $handlerStack = $this->client->getConfig('handler');

        $handlerStack->push(Middleware::tap(function (RequestInterface $request, array $options): void {
            Log::debug('E2E request', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'headers' => $request->getHeaders(),
                'body' => (string) $request->getBody(),
                'options' => $options,
            ]);
        }));

        return $this;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $params
     * @return $this
     */
    public function get(string $uri, array $params = []): self
    {
        $this->pendingRequest = [
            'method' => 'GET',
            'uri' => $uri,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $params
     * @return $this
     */
    public function post(string $uri, array $params = []): self
    {
        $this->pendingRequest = [
            'method' => 'POST',
            'uri' => $uri,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $params
     * @return $this
     */
    public function patch(string $uri, array $params = []): self
    {
        $this->pendingRequest = [
            'method' => 'PATCH',
            'uri' => $uri,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $params
     * @return $this
     */
    public function put(string $uri, array $params = []): self
    {
        $this->pendingRequest = [
            'method' => 'PUT',
            'uri' => $uri,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * @param string $uri
     * @param array<string, mixed> $params
     * @return $this
     */
    public function delete(string $uri, array $params = []): self
    {
        $this->pendingRequest = [
            'method' => 'DELETE',
            'uri' => $uri,
            'params' => $params,
        ];
        return $this;
    }

    private function getXsrfToken(): ?string
    {
        // Use lightweight route to set CSRF token cookie
        $response = $this->get('/test/csrf-token')->send();

        $body = (string) $response->getBody();
        /** @var array{csrf_token?: string} $data */
        $data = json_decode($body, true);

        return $data['csrf_token'] ?? null;
    }

    public function refreshXsrf(): self
    {
        $this->xsrfToken = $this->getXsrfToken();
        return $this;
    }

    /**
     * @param array<string, string> $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * @param Authenticatable $user
     * @param string $password
     * @param string|null $recoveryCode
     * @return $this
     */
    public function actingAs(Authenticatable $user, string $password = 'password', ?string $recoveryCode = null): self
    {
        // Get the login post url
        /** @var string $loginUrl */
        $loginUrl = config('e2e-testing.login_route', '/login');
        if (!str_starts_with($loginUrl, '/')) {
            try {
                $loginUrl = route($loginUrl);
            } catch (\Exception $e) {
                throw new \Exception('Login route in config e2e-testing does not exist: ' . $loginUrl);
            }
        }

        // Get the 2FA challenge post url
        /** @var string $twoFactorChallengeUrl */
        $twoFactorChallengeUrl = config('e2e-testing.two_factor_challenge_route', '/two-factor-challenge');
        if (!str_starts_with($twoFactorChallengeUrl, '/')) {
            try {
                $twoFactorChallengeUrl = route($twoFactorChallengeUrl);
            } catch (\Exception $e) {
                throw new \Exception('Two factor challenge route in config e2e-testing does not exist: ' . $twoFactorChallengeUrl);
            }
        }

        // Get the 2FA challenge location url
        /** @var string $twoFactorChallengeLocation */
        $twoFactorChallengeLocation = config('e2e-testing.two_factor_challenge_location_route', '/two-factor-challenge');
        if (!str_starts_with($twoFactorChallengeLocation, '/')) {
            try {
                $twoFactorChallengeLocation = route($twoFactorChallengeLocation);
            } catch (\Exception $e) {
                throw new \Exception('Two factor challenge location route in config e2e-testing does not exist: ' . $twoFactorChallengeLocation);
            }
        }

        // Log in the user
        // Get email - try property first, then getAuthIdentifier if property doesn't exist
        /** @var string $email */
        $email = property_exists($user, 'email') && isset($user->email) ? (string) $user->email : (string) $user->getAuthIdentifier();
        $loginResponse = $this->post($loginUrl, [
            'email' => $email,
            'password' => $password,
        ])->send();
        $loginRedirect = $loginResponse->getHeaderLine('Location');

        // Check if login failed
        if (str_contains($loginRedirect, $loginUrl)) {
            $body = (string) $loginResponse->getBody();
            throw new \Exception(
                'Login failed - redirected back to login page. ' .
                'This usually means invalid credentials or validation errors. ' .
                'Response: ' . (strlen($body) > 200 ? substr($body, 0, 200) . '...' : $body)
            );
        }

        // Refresh XSRF token after succesful login
        $this->xsrfToken = $this->getXsrfToken();

        // If redirected to 2FA challenge page, complete 2FA
        if (str_contains($loginRedirect, $twoFactorChallengeLocation)) {
            // Post the 2FA challenge
            $twoFactorChallengeResponse = $this->post($twoFactorChallengeUrl, [
                'recovery_code' => $recoveryCode,
            ])->send();

            // Check if 2FA failed
            $twoFactorRedirect = $twoFactorChallengeResponse->getHeaderLine('Location');
            if (str_contains($twoFactorRedirect, $twoFactorChallengeUrl)) {
                $body = (string) $twoFactorChallengeResponse->getBody();
                throw new \Exception(
                    '2FA challenge failed - redirected back to 2FA challenge page. ' .
                    'This usually means invalid recovery code or validation errors. ' .
                    'Response: ' . (strlen($body) > 200 ? substr($body, 0, 200) . '...' : $body)
                );
            }

            // Refresh XSRF token after succesful 2FA
            $this->xsrfToken = $this->getXsrfToken();
        }

        return $this;
    }

    public function send(): ResponseInterface
    {
        if (!isset($this->pendingRequest['method']) || !isset($this->pendingRequest['uri'])) {
            throw new \Exception('HTTP method and URI must be set before calling send().');
        }

        /** @var string $method */
        $method = $this->pendingRequest['method'];
        /** @var string $uri */
        $uri = $this->pendingRequest['uri'];
        /** @var array<string, mixed> $params */
        $params = $this->pendingRequest['params'] ?? [];

        /** @var array<string, mixed> $options */
        $options = [
            'allow_redirects' => false,
        ];

        if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            $options['form_params'] = $params;
        } elseif (in_array($method, ['GET'], true)) {
            // Add params to the query string
            $options['query'] = $params;

            // Merge the existing query params from the url with the new query params
            $existing_query_params = [];
            $url_query = parse_url($uri, PHP_URL_QUERY);
            if (is_string($url_query)) {
                parse_str($url_query, $existing_query_params);
            }
            /** @var array<string, mixed> $existing_query_params */
            $options['query'] = array_merge($existing_query_params, $params);
        }

        // Initialize headers array
        /** @var array<string, string> $headers */
        $headers = [];
        // Add the csrf token if available
        if ($this->xsrfToken) {
            $headers['X-CSRF-TOKEN'] = $this->xsrfToken;
        }
        // Merge any custom headers that were set via withHeaders()
        if (!empty($this->headers)) {
            $headers = array_merge($headers, $this->headers);
        }
        // Always add the X-TESTING header to make sure the application receiving the request knows it's a testing request.
        /** @var string $headerName */
        $headerName = config('e2e-testing.header_name', 'X-TESTING');
        $headers[$headerName] = '1';
        $options['headers'] = $headers;

        /** @var ResponseInterface $response */
        $response = $this->client->request($method, $uri, $options);

        // Reset the builder's request and headers
        $this->pendingRequest = [];
        $this->headers = [];

        return $response;
    }

    public function __invoke(): ResponseInterface
    {
        return $this->send();
    }
}

