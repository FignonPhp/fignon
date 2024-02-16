<?php

declare(strict_types=1);

namespace Fignon\TunnelStation;

use Fignon\Error\TunnelError;
use Fignon\MiddlewareChain\MiddlewareChain;
use Fignon\Router\Router;
use Fignon\Helper\MiddlewareHelper;

/**
 * TunnelStation is the base class extended by Router and the Tunnel class.
 *
 * On Router or Tunnel, you can register your middlewares like this:
 *
 * 1. Closure [$app->use(function($req, $res, $next) {});]
 *
 * 2. Named function (Same as closure) [$app->use($nameFunction);]
 *
 * 3. Class with __invoke method. [$app->use(new MyClassWithInvoke());]
 *
 * 4. A class's method. [$app->use([new MyClassWithMethod(),'methodName']);]
 *
 * 5. A class with a static method. [$app->use([MyClassWithMethod::class,'methodName']);]
 */
class TunnelStation
{
    /** The base path on which this Tunnel Station is mounted */
    protected $basePath = '';

    /** The container of all route of this tunnel station */
    protected $container = [];

    protected MiddlewareHelper $helper;

    /**
     * @var array|null $lastRouteAdded The last route added
     * It's a reference to the last route added to the container
     *
     * Primary used to name the route and void naming non existing route
     */
    private $lastRouteAdded = null;


    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }


    public function __construct()
    {
        $this->helper = new MiddlewareHelper();
    }

    /**
     * Returns an instance of a single route which you can then use to handle HTTP verbs with optional middleware.
     * Use router.route() to avoid duplicate route naming and thus typing errors.
     */
    public function route($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * Register a middleware at $path into the specified chain
     */
    protected function registerMiddleware($method, $path, $middlewares)
    {

        if (count($middlewares) === 0) {
            throw new TunnelError("You must provide at least one middleware");
        }

        // Middleware list register to a given path must be as the same type (error or standard)
        $isErrorMiddleware = $this->helper->isErrorMiddleware($middlewares[0]);
        foreach ($middlewares as $middleware) {
            if ($isErrorMiddleware && !$this->helper->isErrorMiddleware($middleware)) {
                throw new TunnelError("Same middleware type check failed! You must provide only " . ($isErrorMiddleware ? "error" : "standard") . " middlewares");
            }
        }


        // Push the new route into the specified chain
        $this->container[] = [
            'method' => $method,
            'path' => $this->basePath . $path, // The path of the route
            'chain' => $middlewares,
            // 'name'=> '' // will be set by the as() function as optional name for the route
        ];
        $this->lastRouteAdded = end($this->container);
        return $this;
    }

    /**
     * Name the route
     *
     * Be aware that this function will name the last route registered.
     *
     * @param string $name The name of the route
     * @return $this
     */
    public function as($name)
    {
        if ($this->lastRouteAdded === null) {
            throw new TunnelError("You must add a route before calling 'as'");
        }

        $index = count($this->container) - 1;
        $this->container[$index]['name'] = $name;
        $this->lastRouteAdded['name'] = $name;
        return $this;
    }

    /**
     * Register a middleware at $path into the get chain
     */
    public function get($path, ...$middlewares)
    {

        return $this->registerMiddleware('get', $path, $middlewares);
    }

    /**
     * Register a middleware at $path into the post chain
     */
    public function post($path, ...$middlewares)
    {
        return $this->registerMiddleware('post', $path, $middlewares);
    }

    /**
     * Register a middleware at $path into the put chain
     */
    public function put($path, ...$middlewares)
    {
        return $this->registerMiddleware('put', $path, $middlewares);
    }

    /**
     * Register a middleware at $path into the delete chain
     */
    public function patch($path, ...$middlewares)
    {
        return $this->registerMiddleware('patch', $path, $middlewares);
    }

    /**
     * Register a middleware at $path into the head chain
     */
    public function head($path, ...$middlewares)
    {
        return $this->registerMiddleware('head', $path, $middlewares);
    }

    /**
     * Register a middleware at $path into the options chain
     */
    public function options($path, ...$middlewares)
    {
        return $this->registerMiddleware('options', $path, $middlewares);
    }

    /**
     * Register a middleware at $path into the delete chain
     */
    public function delete($path, ...$middlewares)
    {
        return $this->registerMiddleware('delete', $path, $middlewares);
    }


    /**
     * Register a middleware at $path into the any chain
     */
    public function any($path, ...$middlewares)
    {
        return $this->registerMiddleware('any', $path, $middlewares);
    }



    /**
     * Register a middleware as closure
     */
    public function useMiddlewareAsClosure(...$args)
    {
        if (count($args) === 1) {
            // Without path
            return $this->any(null, $args[0]);
        } else {
            // With path
            return $this->any($args[0], $args[1]);
        }
    }


    /**
     * Register a middleware as callable
     */
    public function useMiddlewareAsCallable(...$args)
    {
        if (count($args) === 1) {
            // Without a path
            return $this->any(null, $args[0]);
        } else {
            // With a path
            return $this->any($args[0], $args[1]);
        }
    }



    /**
     * Register a middleware as static method of a class
     */
    public function useMiddlewareAsStaticMethod(...$args)
    {
        if (count($args) === 1) {
            return $this->any(null, $args[0]);
        } else {
            return $this->any($args[0], $args[1]);
        }
    }



    /**
     * Register a method of a class as middleware
     */
    public function useMiddlewareAsNonStaticMethod(...$args)
    {
        if (count($args) === 1) {
            return $this->any(null, $args[0]);
        } else {
            return $this->any($args[0], $args[1]);
        }
    }

    /**
     * Register a middleware or a router. With or without path.
     */
    public function use(...$args)
    {
        // Use expect to have at least 1 argument and also avoid an infinite arguments
        if (count($args) === 0 || count($args) > 21) {
            throw new TunnelError("You must provide at least one middleware at most, 10. If you need more, please use a router to groupe your route");
        }

        //If instance of router is registered with or without path
        if ($this->helper->isRouter($args) || $this->helper->isPathAndRouter($args)) {
            return $this->useRouter(...$args);
        } elseif (
            $this->helper->isMiddlewareAsClosure($args)
            || $this->helper->isPathAndMiddlewareAsClosure($args)
            || $this->helper->isMiddlewareAsCallable($args)
            || $this->helper->isPathAndMiddlewareAsCallable($args)
            || $this->helper->isMiddlewareAsStaticMethod($args)
            || $this->helper->isPathAndMiddlewareAsStaticMethod($args)
            || $this->helper->isMiddlewareAsNonStaticMethod($args)
            || $this->helper->isPathAndMiddlewareAsNonStaticMethod($args)
        ) {
            if (is_string($args[0])) {
                return $this->any($args[0], ...array_slice($args, 1));
            } else {
                return $this->any(null, ...$args);
            }
        } else {

            // Extract path if it's the first argument
            $path = is_string($args[0]) ? array_shift($args) : null;

            foreach ($args as $middleware) {
                if ($this->helper->isSingleRouter($middleware)) {
                    return $this->useRouter($path, $middleware);
                } elseif ($this->helper->isSingleMiddlewareAsClosure($middleware)) {
                    return  $this->useMiddlewareAsClosure($path, $middleware);
                } elseif ($this->helper->isSingleMiddlewareAsCallable($middleware)) {
                    return  $this->useMiddlewareAsCallable($path, $middleware);
                } elseif ($this->helper->isSingleMiddlewareAsStaticMethod($middleware)) {
                    return  $this->useMiddlewareAsStaticMethod($path, $middleware);
                } elseif ($this->helper->isSingleMiddlewareAsNonStaticMethod($middleware)) {
                    return  $this->useMiddlewareAsNonStaticMethod($path, $middleware);
                } else {
                    continue;
                }
            }
        }

        // For any others, throw 'Invalid middleware type'
        throw new TunnelError("Invalid middleware type");
    }


    /**
     * Register a router into the tunnel
     *
     * @param array $args The arguments
     *
     * Can be called like this:
     * `useRouter(Router $router)` or
     * `useRouter(string $path, Router $router)`
     *
     * @throws TunnelError If wrong number of arguments or if the router is not an instance of Router
     * @return $this
     */
    private function useRouter(...$args)
    {
        $router = null;
        $path = null;

        if (count($args) === 1) {
            $router = $args[0];
            $router->setBasePath('/');
        } elseif (count($args) === 2) {
            $path = $args[0];
            $router = $args[1];
            $router->setBasePath($path);
        } else {
            throw new TunnelError("Invalid number of arguments to useRouter()");
        }

        // Check if the router is an instance of Router
        if (!($router instanceof Router)) {
            throw new TunnelError("The router must be an instance of Router");
        }

        // If the path is not null, then it must be a string
        if ($path !== null && !is_string($path)) {
            throw new TunnelError("The path must be a string");
        }

        // Push each routes of the router into the main container
        foreach ($router->container as $route) {
            // If the path is not null, then we must prepend the path to the route path
            if ($path !== null) {
                $route['path'] = $path . $route['path'];
            }

            // Push the route to the current http method chain
            $this->container[] = $route;
        }

        return $this;
    }

    /**
     * Get the middleware chain for the current matched route
     *
     * Order is: [preChain, routeChain, errorChain,allChain]
     */
    public function middlewareChainForCurrentMatchedRoute($pathBasedFilteredContainer = []): array
    {
        $pre = [];
        $route = [];
        $error = [];
        $all = [];

        foreach ($pathBasedFilteredContainer as $routeItem) {
            foreach ($routeItem['chain'] as $middleware) {
                $isError = $this->helper->isErrorMiddleware($middleware);
                $paramCount = $this->helper->getNumberOfParameters($middleware);

                if ($routeItem['path'] == null && $routeItem['method'] == 'any' && !$isError) {

                    $pre[] = $middleware;
                } elseif ($isError) {
                    $error[] = $middleware;
                } elseif ($paramCount === 2 || $paramCount === 3) {
                    $route[] = $middleware;
                }

                $all[] = $middleware;
            }
        }

        $preChain = new MiddlewareChain($pre);
        $routingChain = new MiddlewareChain($route);
        $errorChain = new MiddlewareChain($error);
        $allChain = new MiddlewareChain($all);

        return [$preChain, $routingChain, $errorChain, $allChain];
    }
}
