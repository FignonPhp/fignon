<?php

declare(strict_types=1);

namespace Fignon;

use Fignon\MiddlewareChain\MiddlewareChain;
use Fignon\TunnelStation\TunnelStation;
use Fignon\Request\Request;
use Fignon\Response\Response;
use Fignon\Error\TunnelError;
use Fignon\Helper\RoutingHelper;
use Fignon\Middlewares\ErrorHandler;
use Fignon\Helper\UtilitiesHelper;
use Fignon\Extra\ViewEngine;
use Symfony\Component\Yaml\Yaml;


/**
 * Fignon Framework.
 *
 * It's the main object in your application.
 *
 * It responsible to register middleware (router too) and find the best group
 * of middleware to run when a request come in and run them the order they were registered.
 *
 * Fignon has a builtin error handler middleware, meaning if you don't provide middleware for 404
 * or unhandled error in middleware, it will do it for you.
 *
 *
 * Create it by `new Tunnel()`.
 *
 * Declare middleware functions in the order they should be executed using app.use()
 * or app.METHOD() functions, where METHOD is the HTTP method of the request that the middleware function handles (such as GET, PUT, or POST) in lowercase.
 *
 * You can also group routes and register theme using `new Router()` and use the same
 * api as the app object (use, get, post, put, delete, etc)
 *
 * Middleware functions are executed sequentially, therefore the order of middleware inclusion is important.
 *
 * Finally, use app->listen() to start the tunnel.
 */
class Tunnel extends TunnelStation
{
    /**
     * The Application settings table contains a set of properties that affect the application’s
     * behavior and are accessible using app->config() and app->set().
     */
    private $settings = [];

    /**
     * The app->locals object has properties that are local variables within the application,
     * and will be available in templates rendered with res->render.
     */
    public $locals = [];

    /** View Engine list the app support. 
     * 
     * Fignon support multiple engine and will always use the first
     * entry of this array as the active engine view.
    */

    /**
     * Master middleware chain responsible to run the filtered middleware grouped.
     */
    public MiddlewareChain $errorMiddlewareChain;

    /**
     * Helper to do routing
     * */
    protected RoutingHelper $routingHelper;

    /**
     * The view engine set on the app
     */
    protected ?string $engineName;

    /**
     * The view engine object responsible to render. 
     * Must be an object implementing the interface `ViewEngine`
     */
    protected ViewEngine $viewEngine;


    public function __construct()
    {
        parent::__construct();
        $this->defaultSettings();
        $this->routingHelper = new RoutingHelper();
    }

    /**
     * Define the default settings for the app
     *
     * @return Tunnel $this
     */
    public function defaultSettings(): Tunnel
    {
        $this->settings = [
            "env" => "production",
            "baseUrl" => $_SERVER['HTTP_HOST'] ?? null,
            "debug" => $_ENV['APP_DEBUG'] ?? false,
            "trustProxy" => false,
            "proxies" => [],
            "trustedHeaderSet" => 1,
            "xPoweredBy" => false,
            "views" => null,
            "viewEngineOptions" => [],
            "viewsCache" => null,
            "caseSensitiveRouting" => false,
            "viewEngine" => null
        ];

        return $this;
    }

    /**
     * Generate a link to a given named route if any or return null
     *
     * @param string $routeName The name of the route
     * @param array $params Parameters, if any
     * @return mixed null or string representing the generated url
     */
    public function urlTo(string $routeName, array $params = [], array $options = [])
    {
        $link = $this->routingHelper->urlTo($this->container, $routeName, $params, $options);

        if ($options['absolute']) {
            if ($this->config('baseUrl') !== null) {
                $link = $this->config('baseUrl') . $link;
            } else {
                throw new TunnelError("The baseUrl is not set. You must set the baseUrl in the app settings table to use the absolute option in urlTo() method.");
            }
        }

        return $link;
    }

    /**
     * Generate a link to a given named route if any or return null
     *
     * @param string $routeName The name of the route
     * @param array $params Parameters, if any
     * @return mixed null or string representing the generated url
     */
    public function link(string $routeName, array $params = [], array $options = [])
    {
        return $this->urlTo($routeName, $params, $options);
    }



    /**
     * Sets the Boolean setting name to false, where name is one of the properties from the app settings table.
     *
     * Calling app->set('foo', false) for a Boolean property is the same as calling app->disable('foo').
     */
    public function disable($name)
    {
        if (isset($this->settings[$name]) && is_bool($this->settings[$name])) {
            $this->settings[$name] = false;
        } else {
            throw new TunnelError("The setting '$name' is not a boolean or not exists");
        }

        return $this;
    }

    /**
     * Returns true if the Boolean setting name is disabled (false), where name is one of the properties from the app settings table.
     */
    public function disabled($name)
    {
        if (isset($this->settings[$name]) && is_bool($this->settings[$name])) {
            return $this->settings[$name] === false;
        } else {
            throw new TunnelError("The setting '$name' is not a boolean or not exists");
        }
    }

    /**
     * Sets the Boolean setting name to true, where name is one of the properties from the app settings table.
     *
     * Calling app->set('foo', true) for a Boolean property is the same as calling app->enable('foo').
     */
    public function enable($name)
    {
        if (isset($this->settings[$name]) && is_bool($this->settings[$name])) {
            $this->settings[$name] = true;
        }

        return $this;
    }


    /**
     * Returns true if the setting name is enabled (true), where name is one of the properties from the app settings table.
     */
    public function enabled($name)
    {
        if (isset($this->settings[$name]) && is_bool($this->settings[$name])) {
            return $this->settings[$name] === true;
        }

        return false;
    }


    /**
     * Returns the value of name app setting, where name is one of the strings in the app settings table.
     *
     * `null` is returned if the setting is not defined.
     */
    public function config($name = null)
    {
        if ($name == null) {
            return $this->settings;
        }

        if (strpos($name, '.') !== false) {
            $keys = explode('.', $name);
            $topKey = array_shift($keys);

            // Assuming $this->settings is the modified array returned by setNestedProperty
            $settings = $this->settings[$topKey];

            foreach ($keys as $key) {
                if (isset($settings[$key])) {
                    $settings = $settings[$key];
                } else {
                    return null; // Or throw an exception if you prefer
                }
            }

            return $settings;
        }

        $convertedName = UtilitiesHelper::toConvertToCamelCase($name);

        return $this->settings[$convertedName] ?? null;
    }

    /**
     * Remove a settings from the settings table
     *
     * @param string $key The key of the value to remove from settings
     * @return boolean True if key found and remove, false otherwise
     */
    public function remove(string $key): bool
    {
        if (isset($this->settings[$key])) {
            unset($this->settings[$key]);
            return true;
        }

        return false;
    }

    /**
     * Check whether a key is defined in the settings table
     *
     * @param string $key The key to check
     * @return boolean True/False whether the settings table has the key
     */
    public function has(string $key): bool
    {
        return isset($this->settings[$key]);
    }

    /**
     * Assigns setting name to value. You may store any value that you want, but certain names can be used to configure the behavior of the server.
     *
     * These special names are listed in the app settings table.
     *
     * Calling app->set('foo', true) for a Boolean property is the same as calling app->enable('foo').
     * Similarly, calling app->set('foo', false) for a Boolean property is the same as calling app->disable('foo').
     */
    public function set($name, $value)
    {

        if (strpos($name, '.') !== false) {
            list($topKey, $array) = UtilitiesHelper::setNestedProperty($name, $value);
            $this->settings[UtilitiesHelper::toConvertToCamelCase($topKey)] = $array;
        } else {
           $this->settings[UtilitiesHelper::toConvertToCamelCase($name)] = $value;
        }
        return $this;
    }

    /**
     * Load config from an external php files. This file must be a php file returning an array
     *
     * @param string $filePath
     * @return void
     */
    public function setFrom($filePath)
    {
        if (file_exists($filePath)) {
            $config = require $filePath;
            if (is_array($config)) {
                $this->settings = array_merge($this->settings, $config);
            }
        }
    }

    /**
     * Load env vars from the $_ENV php global variable into the app config
     *
     * @return Tunnel
     */
    public function setFromEnv()
    {
        $this->settings = array_merge($this->settings, $_ENV);

        return $this;
    }

    /**
     * Load configuration from a local json file
     *
     * @param string $filePath
     * @return Tunnel $this
     */
    public function setFromJson($filePath)
    {
        if (file_exists($filePath)) {
            $config = null;

            try {
                $config = json_decode(file_get_contents($filePath), true);
            } catch (\Throwable $th) {
                throw new TunnelError("Unable to fetch the Json configuration file: " . $th->getMessage());
            }

            if (is_array($config)) {
                $this->settings = array_merge($this->settings, $config);
            }
        }
        return $this;
    }

    /**
     * Load configuration from a local yaml file
     *
     * @param string $filePath
     * @return Tunnel $this
     */
    public function setFromYaml($filePath)
    {
        if (file_exists($filePath)) {
            $config = null;

            try {
                $config = Yaml::parseFile($filePath);
            } catch (\Throwable $th) {
                throw new TunnelError("Unable to fetch the Yaml configuration file: " . $th->getMessage());
            }

            if (is_array($config)) {
                $this->settings = array_merge($this->settings, $config);
            }
        }
        return $this;
    }

    /**
     * Register the app's view engine
     *
     * @param string $name The view engine name. Can be any string
     * @param ViewEngine $viewEngine The view engine object. It must implement the ViewEngine interface
     * @return Tunnel $this;
     */
    public function engine(string $name, ViewEngine $viewEngine)
    {
        $this->engineName = $name;
        $this->viewEngine = $viewEngine->init($this->config('views'), $this->config('viewsCache'), $this->config('viewEngineOptions') ?? []);
        return $this;
    }


    /**
     * Returns the rendered HTML of a view via the callback function. It accepts an optional parameter that is an object containing local variables for the view.
     *
     * It is like res->render(), except it cannot send the rendered view to the client on its own.
     */
    public function render(string $view, array $locals = [], $options = []): string
    {
        if ($this->engineName == null || $this->viewEngine == null) {
            throw new TunnelError("View engine is not set");
        }

        //Add the app level locals
        $locals["app"] = $this->locals;
        return (string) ($this->viewEngine->render($view, $locals, $options) ?? '');
    }


    /**
     * Start the tunnel to listen to incoming requests
     */
    public function listen(callable $before = null, callable $after = null)
    {
        $res = new Response();
        $req = Request::createFromGlobals();
        $req->setApp($this);
        $req->init(); // Always set app before init because init uses app
        $res->setApp($this);
        $res->setRequest($req);
        $req->setResponse($res);

        // Call the optional before callback just for logging purpose
        if ($before) {
            list($req, $res) =  $before($req, $res);
        }


        // First we find error handler and plug the built in 404 error handler
        list($preChain, $routingChain, $errorChain, $allChain) = $this->middlewareChainForCurrentMatchedRoute($this->container);
        // Add at the end of the errorChain, the default built in handler
        $errorChain->use([ErrorHandler::class, 'UnHandledError']);
        // Inform the app of which error handler chain to use
        $this->errorMiddlewareChain = $errorChain;
        // Add at the end of the middleware container, the default built in handler which will be used if no route is matched and no middleware is registered to handle 404
        $this->container[] = ["method" => "any", "path" => null, "chain" => [[ErrorHandler::class, 'NotFound']]];

        // Handle the request
        try {
            $this->handle($req, $res);
        } catch (\Throwable $th) {
            $this->errorMiddlewareChain->run($th, $req, $res, null);
        }

        // Call the optional callback just for logging purpose
        if ($after) {
            list($req, $res) = $after($req, $res);
        }
    }


    /**
     * Handle the request by:
     *
     * A. Making a routing decision by:
     *      1. Filtering the container based on the http method
     *      2. Filtering the container based on the path
     * B. Extracting the params from the request path and set them in the request object
     *
     * C. Running the middleware chain which contains middlewares registered on the current matched route
     *
     * Note: The result of the routing is not a single middleware but a chain of middlewares.
     *
     * @param Request $req The request object.
     * @param Response $res The response object.
     * @return void
     */
    public function handle(Request $req, Response $res)
    {

        // Get the http method of the request
        $method = strtolower($req->method);

        // Filter the container based on the http method
        $methodBasedFilteredContainer = $this->routingHelper->filterWithMethod($this->container, $method);

        // Get the path of the request
        $path = $req->path;

        // Filter the container based on path
        $pathBasedFilteredContainer = $this->routingHelper->filterWithPath($methodBasedFilteredContainer, $path, $this->enabled('caseSensitiveRouting'));


        // Extract the params from the request path
        $params = $this->routingHelper->extractParams($req->originalUrl, $this->routingHelper->findFirstMatchedRoutePath($pathBasedFilteredContainer));
        $req->setParams($params);

        /**
         * The middleware chain is an array of middlewares that will be executed sequentially.
         */
        list($preChain, $routingChain, $errorChain, $allChain) = $this->middlewareChainForCurrentMatchedRoute($pathBasedFilteredContainer);

        $allChain->use([ErrorHandler::class, 'NotFound']);
        $errorChain->use([ErrorHandler::class, 'UnHandledError']);

        // We know, the route, the middleware chain to handle error with also
        $this->errorMiddlewareChain = $errorChain;


        // Run the middleware chain
        $allChain->run(null, $req, $res, $this->errorMiddlewareChain);
    }
}
