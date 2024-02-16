<?php

declare(strict_types=1);

namespace Fignon\Helper;

class RoutingHelper
{
    
    /**
     * Compare two arrays of url segments
     *
     * @param array $urlSegments The url segments
     * @param array $routeSchemaSegments The route schema segments
     * @param bool $caseSensitive Whether to use case sensitive comparison or not
     * @return bool True if the segments match, false otherwise
     */
    public function compareSegments(array $urlSegments = [], array $routeSchemaSegments = [], $caseSensitive = false): bool
    {

        // Make sure both arrays have the same length before comparison
        if (count($urlSegments) !== count($routeSchemaSegments)) {
            return false;
        }

        foreach ($urlSegments as $key => $urlSegment) {
            $routeSchemaSegment = $routeSchemaSegments[$key];

            // Check if the route schema segment is a parameter (starts with ':')
            if (0 === strpos($routeSchemaSegment, ':')) {
                // It's a parameter, so continue to the next segment
                continue;
            } else {
                // It's not a parameter, so compare the segments
                // Use case sensitive comparison if the caseSensitive option is true
                if ($caseSensitive) {
                    if (strcmp($urlSegment, $routeSchemaSegment) !== 0) {
                        return false;
                    }
                } else {
                    if (strcasecmp($urlSegment, $routeSchemaSegment) !== 0) {
                        return false;
                    }
                }
            }
        }

        return true;
    }


    /**
     * Segments a path/url into an array of segments
     *
     * @param string $path The path to segment
     * @return array The segments
     */
    public function segments(string $path): ?array
    {
        return explode('/', trim($path, '/'));
    }


    
    /**
     * Extract the parameters from a URL based on a schema.
     *
     * The schema is a route path with parameters and eventual parameter placeholders represented by
     * a colon followed by the parameter name: (/:id).
     * @param string $url The request URL
     * @param string $schema The schema url
     * @return ?array The extracted parameters
     */
    public function extractParams(string $url, string $schema): ?array
    {
        $url = explode('?', $url)[0];
        $urlParts = $this->segments($url);
        $schemaParts = $this->segments($schema);

        if (count($urlParts) != count($schemaParts)) {
            return []; // The number of parts in the URL and schema do not match
        }

        $parameters = [];
        foreach (array_keys($schemaParts) as $i) {
            $part = $schemaParts[$i];
        
            if (0 === strpos($part, ':')) {
                $parameterName = substr($part, 1);
                $parameterValue = $urlParts[$i];
                $parameters[$parameterName] = $parameterValue;
            } elseif ($urlParts[$i] !== $part) {
                return []; // The URL part does not match the schema
            }
        }

        return $parameters ?? [];
    }



    /**
     * Check wether the request url match the route schema
     *
     * @param array $route The route schema
     * @param string $path The request url
     * @return bool True if the request url match the route schema, false otherwise
     */
    public function isUrlMatched(string $routeSchema, string $ReqPath, bool $caseSensitive = false): bool
    {

        $cleanPath = rtrim($ReqPath, '/');
        $cleanRoute = rtrim($routeSchema, '/');

        $routeSchemaSegments = $this->segments($cleanRoute);
        $urlSegments = $this->segments($cleanPath);
        return $this->compareSegments($urlSegments, $routeSchemaSegments,$caseSensitive);
    }

    /**
     * Find the schema path of the matched route within the middleware
     * container which all matched the request
     *
     * Note: This method is needed because the pathBasedFilteredContainer contain route middleware, error middlware
     * and global middleware which don't have all a path. So we need to find a path to be able to extract
     * request parameters.
     *
     *
     * @param array $pathBasedFilteredContainer List of routes which all matched the request (based on req method and path)
     * @return string The schema path of the matched route
     */
    public function findFirstMatchedRoutePath(array $middlewareContainer = []): string
    {
        $theRequestPath = '/';
        foreach ($middlewareContainer as $route) {
            if (null !== $route['path'] && '' !== $route['path']) {
                $theRequestPath = $route['path'];
                break;
            }
        }

        return $theRequestPath;
    }

    /**
     * Filter the middleware container based on the request path
     *
     * @param array $middlewareContainer The middleware container
     * @param string $reqPath The request path
     * @param bool $caseSensitive Whether to use case sensitive comparison or not
     * @return ?array The filtered middleware container
     */
    public function filterWithPath(array $middlewareContainer = [], string $reqPath, bool $caseSensitive = false): ?array
    {
        $filteredContainer = [];
        foreach ($middlewareContainer as $route) {
            // If the route path is null and the method is ANY, then it's a global middleware,
            // it should be passed to the next step
            // Note: $route['path'] == '' is considered as a path '/' and not global middleware
            if (null === $route['path'] && 'any' === $route['method']) {
                $filteredContainer[] = $route;
                continue;
            }

            if ($this->isUrlMatched($route['path'], $reqPath, $caseSensitive)) {
                $filteredContainer[] = $route;
            }

        }

        return $filteredContainer;
    }

    /**
     * Filter the middleware container based on the request method
     *
     * @param array $container The middleware container
     * @param string $reqMethod The request method
     * @return ?array The filtered middleware container
     */
    public function filterWithMethod(array $container = [], string $reqMethod): ?array
    {
        $filteredContainer = [];

        foreach ($container as $route) {
            if (($route['method'] === $reqMethod) || ('any' === $route['method'])) {
                $filteredContainer[] = $route;
            }
        }

        return  $filteredContainer;
    }

    /**
     * Generate the link to a named route
     *
     * @param array $container The route container
     * @param string $routeName The route name
     * @param array $params The route parameters
     * @param array $options The route options. Most important are:
     *  - query: [key => value] The query parameters
     *  - absolute: bool Whether to generate an absolute url or not
     * @return ?string The generated url
     */
    public function urlTo(array $container = [], $routeName, $params = [], $options = []): ?string
    {
        foreach ($container as $route) {
            if (isset($route['name'])) {
                if ($route['name'] === $routeName) {
                    $routePath = $route['path'];
                    foreach ($params as $key => $value) {
                        if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                            $routePath = str_replace(':' . $key, (string) $value, $routePath);
                        }
                    }

                    if (isset($options['query'])) {
                        $routePath .= '?' . http_build_query($options['query']);
                    }

                    return $routePath;
                }
            }
        }

        return null;
    }
}
