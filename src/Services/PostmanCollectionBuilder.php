<?php

namespace LaravelApi\StarterKit\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PostmanCollectionBuilder
{
    /**
     * The Postman Collection v2.1 schema URL.
     */
    protected const SCHEMA_URL = 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json';

    /**
     * Build a complete Postman Collection v2.1 structure.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @param  array  $options
     * @return array
     */
    public function build(Collection $routes, array $options = []): array
    {
        $collectionName = $options['name'] ?? config('api-starter-kit.postman.collection_name') ?? config('app.name', 'API') . ' Collection';
        $baseUrl = $options['base_url'] ?? config('api-starter-kit.postman.base_url', '{{base_url}}');
        $bearer = $options['bearer'] ?? null;
        $groupBy = $options['group_by'] ?? 'prefix';

        $grouped = $this->groupRoutes($routes, $groupBy);
        $items = $this->buildFolders($grouped, $baseUrl, $bearer);

        return [
            'info' => [
                'name' => $collectionName,
                'description' => "Auto-generated Postman collection from Laravel API routes.\nExported at: " . now()->toIso8601String(),
                'schema' => self::SCHEMA_URL,
            ],
            'item' => $items,
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $this->resolveBaseUrl($baseUrl),
                    'type' => 'string',
                ],
            ],
        ];
    }

    /**
     * Group routes by the specified strategy.
     *
     * @param  \Illuminate\Support\Collection  $routes
     * @param  string  $groupBy
     * @return array<string, \Illuminate\Support\Collection>
     */
    public function groupRoutes(Collection $routes, string $groupBy = 'prefix'): array
    {
        if ($groupBy === 'middleware') {
            return $routes->groupBy(function (Route $route) {
                $middleware = $this->getMiddleware($route);
                $authMiddleware = config('api-starter-kit.postman.auth_middleware', []);

                foreach ($middleware as $mw) {
                    if (in_array($mw, $authMiddleware)) {
                        return 'Authenticated';
                    }
                }

                return 'Public';
            })->toArray();
        }

        // Default: group by first meaningful URI segment after the API prefix
        return $routes->groupBy(function (Route $route) {
            $uri = $route->uri();
            $prefix = trim(config('api-starter-kit.prefix', 'api'), '/');

            // Remove the API prefix
            $path = Str::after($uri, $prefix . '/');

            // Get the first segment as the group name
            $segments = explode('/', trim($path, '/'));
            $firstSegment = $segments[0] ?? 'General';

            return Str::title(str_replace(['-', '_'], ' ', $firstSegment));
        })->toArray();
    }

    /**
     * Build folder items from grouped routes.
     *
     * @param  array  $grouped
     * @param  string  $baseUrl
     * @param  string|null  $bearer
     * @return array
     */
    protected function buildFolders(array $grouped, string $baseUrl, ?string $bearer): array
    {
        $folders = [];

        foreach ($grouped as $folderName => $routes) {
            $items = [];

            foreach ($routes as $route) {
                $routeObj = $route instanceof Route ? $route : $route;
                $methods = $this->getHttpMethods($routeObj);

                foreach ($methods as $method) {
                    $items[] = $this->buildItem($routeObj, $method, $baseUrl, $bearer);
                }
            }

            if (count($grouped) === 1 && $folderName === 'General') {
                // Don't wrap in a folder if there's only one generic group
                $folders = array_merge($folders, $items);
            } else {
                $folders[] = [
                    'name' => $folderName,
                    'item' => $items,
                ];
            }
        }

        return $folders;
    }

    /**
     * Build a single Postman request item.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $method
     * @param  string  $baseUrl
     * @param  string|null  $bearer
     * @return array
     */
    public function buildItem(Route $route, string $method, string $baseUrl, ?string $bearer = null): array
    {
        $uri = $route->uri();
        $name = $this->buildItemName($route, $method);
        $url = $this->buildUrl($uri, $baseUrl);
        $headers = $this->buildHeaders();

        $item = [
            'name' => $name,
            'request' => [
                'method' => strtoupper($method),
                'header' => $headers,
                'url' => $url,
            ],
            'response' => [],
        ];

        // Add auth if route has auth middleware
        $auth = $this->detectAuth($route, $bearer);
        if ($auth !== null) {
            $item['request']['auth'] = $auth;
        }

        // Add request body for write methods
        $body = $this->buildRequestBody($method);
        if ($body !== null) {
            $item['request']['body'] = $body;
        }

        return $item;
    }

    /**
     * Build a human-readable item name.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $method
     * @return string
     */
    protected function buildItemName(Route $route, string $method): string
    {
        // Use route name if available
        if ($route->getName()) {
            return Str::title(str_replace(['.', '-', '_'], ' ', $route->getName()));
        }

        // Generate from method + URI
        $uri = $route->uri();
        $segments = explode('/', trim($uri, '/'));
        $lastSegment = end($segments);

        // If last segment is a parameter, use the one before it
        if (Str::startsWith($lastSegment, '{')) {
            $resourceSegment = $segments[count($segments) - 2] ?? $lastSegment;
            $resource = Str::singular(Str::title(str_replace(['-', '_'], ' ', $resourceSegment)));

            return match (strtoupper($method)) {
                'GET' => "Get {$resource}",
                'PUT', 'PATCH' => "Update {$resource}",
                'DELETE' => "Delete {$resource}",
                default => strtoupper($method) . " {$resource}",
            };
        }

        $resource = Str::title(str_replace(['-', '_'], ' ', $lastSegment));

        return match (strtoupper($method)) {
            'GET' => "List {$resource}",
            'POST' => "Create {$resource}",
            default => strtoupper($method) . " {$resource}",
        };
    }

    /**
     * Build the Postman URL object.
     *
     * @param  string  $uri
     * @param  string  $baseUrl
     * @return array
     */
    protected function buildUrl(string $uri, string $baseUrl): array
    {
        // Convert Laravel route parameters {param} to Postman :param
        $postmanPath = preg_replace('/\{(\w+)\??\}/', ':$1', $uri);
        $pathSegments = array_values(array_filter(explode('/', $postmanPath)));

        // Detect path variables
        $variables = [];
        preg_match_all('/\{(\w+)\??\}/', $uri, $matches);
        foreach ($matches[1] as $param) {
            $variables[] = [
                'key' => $param,
                'value' => '',
                'description' => "The {$param} parameter",
            ];
        }

        $url = [
            'raw' => rtrim($baseUrl, '/') . '/' . $postmanPath,
            'host' => [$baseUrl],
            'path' => $pathSegments,
        ];

        if (!empty($variables)) {
            $url['variable'] = $variables;
        }

        return $url;
    }

    /**
     * Build the default request headers.
     *
     * @return array
     */
    protected function buildHeaders(): array
    {
        $configHeaders = config('api-starter-kit.postman.headers', [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $headers = [];
        foreach ($configHeaders as $key => $value) {
            $headers[] = [
                'key' => $key,
                'value' => $value,
                'type' => 'text',
            ];
        }

        return $headers;
    }

    /**
     * Build a sample request body for write methods.
     *
     * @param  string  $method
     * @return array|null
     */
    public function buildRequestBody(string $method): ?array
    {
        $writeMethods = ['POST', 'PUT', 'PATCH'];

        if (!in_array(strtoupper($method), $writeMethods)) {
            return null;
        }

        return [
            'mode' => 'raw',
            'raw' => json_encode(new \stdClass(), JSON_PRETTY_PRINT),
            'options' => [
                'raw' => [
                    'language' => 'json',
                ],
            ],
        ];
    }

    /**
     * Detect if a route has auth middleware and return the auth config.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  string|null  $bearer
     * @return array|null
     */
    public function detectAuth(Route $route, ?string $bearer = null): ?array
    {
        $middleware = $this->getMiddleware($route);
        $authMiddleware = config('api-starter-kit.postman.auth_middleware', [
            'auth:sanctum',
            'auth:api',
            'auth',
            'api.auth',
        ]);

        $hasAuth = !empty(array_intersect($middleware, $authMiddleware));

        if (!$hasAuth) {
            return null;
        }

        return [
            'type' => 'bearer',
            'bearer' => [
                [
                    'key' => 'token',
                    'value' => $bearer ?? '{{auth_token}}',
                    'type' => 'string',
                ],
            ],
        ];
    }

    /**
     * Serialize the collection array to pretty-printed JSON.
     *
     * @param  array  $collection
     * @return string
     */
    public function toJson(array $collection): string
    {
        return json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get the HTTP methods for a route, excluding HEAD.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getHttpMethods(Route $route): array
    {
        return array_filter($route->methods(), function ($method) {
            return !in_array(strtoupper($method), ['HEAD']);
        });
    }

    /**
     * Get middleware assigned to a route.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @return array
     */
    protected function getMiddleware(Route $route): array
    {
        return array_map(function ($middleware) {
            return $middleware instanceof \Closure ? 'Closure' : $middleware;
        }, $route->gatherMiddleware());
    }

    /**
     * Resolve the base URL, falling back to app URL.
     *
     * @param  string  $baseUrl
     * @return string
     */
    protected function resolveBaseUrl(string $baseUrl): string
    {
        if ($baseUrl === '{{base_url}}') {
            return rtrim(config('app.url', 'http://localhost'), '/');
        }

        return rtrim($baseUrl, '/');
    }
}
