<?php

declare(strict_types=1);

namespace Fignon\Request;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Fignon\Response\Response;
use Fignon\Tunnel;

/**
 * The Fignon Request
 *
 * The req object represents the HTTP request and has properties for the request query string, parameters, body, HTTP headers, and so on.
 *
 * By convention, the object is always referred to as req (and the HTTP response is res)
 * but its actual name is determined by the parameters to the callback function in which you’re working.
 */
class Request extends SymfonyRequest
{
    /**
     * A Boolean property that is true if a TLS connection is established. Equivalent to:
     *
     * `req->protocol === 'https'`
     */
    public ?bool $secure = null;

    /**
     * An array of subdomains in the domain name of the request.
     */
    public ?array $subdomains = [];

    /**
     * A Boolean property that is true if the request’s X-Requested-With header field is “XMLHttpRequest”, indicating that the request was issued by a client library such as jQuery.
     */
    public ?bool $xhr = null;



    /**
     * Contains key-value pairs of data submitted in the request body.
     *
     * By default, it is null, and is populated when you use BodyParser middlewares
     * such as: `$app->use(new BodyParser());`
     *
     * The above automatically populate the body property with the parsed body of the request.
     * It handle json body, urlencoded body and multipart/form-data body.
     * For non supported content type, it will be the raw content.
     *
     * Note that req.body’s shape will depend on the content type sent by the client.
     *
     * Note: For multipart/form-data req.body will contains only non file field,
     *  you can use req->file() to get the files.
     */
    public mixed $body = null;

    /**
     * The request http method
     */
    public ?string $method = null;

    /**
     * This property holds a reference to the instance of the Tunnel application that is using the middleware.
     */
    public ?Tunnel $app = null;
    /**
     * Contains the hostname derived from the Host HTTP header or the X-Forwarded-Host header when the trust proxy setting does not evaluate to false.
     *
     * When the trust proxy setting does not evaluate to false, this property will instead get the value from the X-Forwarded-Host header field. This header can be set by the client or by the proxy.
     *
     * If there is more than one X-Forwarded-Host header in the request, the value of the first header is used. This includes a single header with comma-separated values, in which the first value is used.
     */
    public ?string $hostname = null;

    /**
     * Contains the remote IP address of the request.
     *
     * When the trust proxy setting does not evaluate to false, the value of this property is derived from the left-most entry in the X-Forwarded-For header.
     *
     * This header can be set by the client or by the proxy.
     */
    public ?string $ip = null;


    /**
     * When the trust proxy setting does not evaluate to false, this property contains an array of IP addresses specified in the X-Forwarded-For request header. Otherwise, it contains an empty array. This header can be set by the client or by the proxy.
     *
     * For example, if X-Forwarded-For is client, proxy1, proxy2, req->ips would be ["client", "proxy1", "proxy2"], where proxy2 is the furthest downstream.
     */
    public ?array $ips = [];

    /**
     * The port on which the request made to the server.
     */
    public ?int $port = null;

    /**
     * Contains the original URL of the request, including the query string,
     * host & protocol
     */
    public ?string $fullUrl = null;


    /**
     * Contains the original URL of the request, including the query string.
     *
     * Note: This is not the full URL including protocol, host, and query string.
     * Refer to req.fullUrl for the full URL.
     */
    public ?string $originalUrl = null;


    /**
     * This property is an object containing properties mapped to the named route “parameters”.
     *
     * For example, if you have the route /user/:name, then the “name” property is available as req->params[name].
     *
     * This object defaults to [].
     */
    public ?array $params = [];


    /**
     * Contains the path part of the request URL.
     */
    public ?string $path = null;


    /**
     * Contains the request protocol string: either http or (for TLS requests) https.
     *
     * When the trust proxy setting does not evaluate to false, this property will use the value of the X-Forwarded-Proto header field if present.
     *
     * This header can be set by the client or by the proxy.
     */
    public ?string $protocol = null;

    /**
     * This property holds a reference to the response object that relates to this request object.
     */
    public ?Response $res = null;

    /**
     * Contains key-value pairs of data which can be set or retrieved by middleware.
     */
    private ?array $container = [];


    /**
     * Initialize the request object
     * 
     * This method is intended to be call internally.
     * 
     * @return Request The current request
     */
    public function init(): Request
    {
        if ($this->app->enabled('trustProxy')) {
            $this->setTrustedProxies($this->app->config('proxies'), $this->app->config('trustedHeaderSet'));
        }

        $this->method = $this->getMethod();
        $this->hostname = $this->getHost();
        $this->ip = $this->getClientIp();
        $this->ip = $this->getClientIp();
        $this->ips = $this->getClientIps();
        $this->port = $this->getPort();
        $this->protocol = $this->getScheme();
        $this->secure = $this->isSecure();
        $this->fullUrl = $this->getUri();
        $this->originalUrl = $this->getRequestUri();
        $this->path = parse_url($this->getPathInfo(), PHP_URL_PATH);
        $this->subdomains = explode('.', $this->hostname);
        $this->xhr = $this->isXmlHttpRequest();

        return $this;
    }


    /**
     * Get a value from the container of this request.
     * The container is a key-value pair that can be used to store data that can be accessed by middlewares.
     *
     * @param string $key The key of the value to retrieve
     * @param mixed $default The default value to return if the key is not found
     * @return mixed The value associated with the key or the default value if the key is not found
     */
    public function data($key, $default = null): mixed
    {
        return $this->container[$key] ?? $default;
    }


    /**
     * Set a value in the container of this request.
     * The container is a key-value pair that can be used to store data that can be accessed by middlewares.
     *
     * @param string $key The key of the value to set
     * @param mixed $value The value to set
     * @return Request The current request
     */
    public function addData($key, $value): Request
    {
        $this->container[$key] = $value;
        return $this;
    }

    /**
     * Set the app object that relates to this request object.
     * 
     * @param Tunnel $app The app object
     * @return Request The current request
     */
    public function setApp(Tunnel $app): Request
    {
        $this->app = $app;
        return $this;
    }


    /**
     * Set the response object that relates to this request object.
     *
     * @param Response $res The response object
     * @return Request The current request
     */
    public function setResponse(Response $res): Request
    {
        $this->res = $res;
        return $this;
    }

    /**
     * Set the request parameters. 
     * This method is intended to be call internally while routing is done.
     *
     * @param mixed $params The request params retrieved from matched routes
     * @return Request The current request
     */
    public function setParams(array $params = []): Request
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Get a value from the request parameters
     *
     * @param string $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function param(string $key = null): mixed
    {
        if (null === $key) {
            return $this->params;
        }

        if (isset($this->params[$key])) {
            return $this->params[$key];
        }

        return  null;
    }

    /**
     * Get a value from the request parameters.
     * 
     * This is an alias of the `param` method.
     *
     * @param string $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function p(string $key): mixed
    {
        return $this->param($key);
    }

    /**
     * Get a value from the request headers or all the headers if no key is provided.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function header(string $key = null): mixed
    {
        if (null === $key) {
            return $this->headers;
        }

        return $this->headers->get($key);
    }

    /**
     * Get a value from the request headers or all the headers if no key is provided.
     * 
     * This is an alias of the `header` method.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function h(string $key = null): mixed
    {
        return $this->header($key);
    }

    /**
     * Get a value from the request query or all the query if no key is provided.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function query(string $key = null): mixed
    {
        if (null === $key) {
            return $this->query;
        }
        
        return $this->query->get($key);
    }

    /**
     * Get a value from the request query or all the query if no key is provided.
     * 
     * This is an alias of the `query` method.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function q(string $key = null): mixed
    {
        return $this->query($key);
    }

    /**
     * Get a value from the request body or all the body if no key is provided.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function request(string $key = null): mixed
    {
        if (null === $key) {
            return $this->request;
        }

        return $this->request->get($key);
    }

    /**
     * Get a value from the request body or all the body if no key is provided.
     * 
     * This is an alias of the `request` method.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function r(string $key = null): mixed
    {
        return $this->request($key);
    }

    /**
     * Get a value from the request files or all the files if no key is provided.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function file(string $key = null): mixed
    {
        if (null === $key) {
            return $this->files;
        }

        return $this->files->get($key);
    }

    /**
     * Get a value from the request files or all the files if no key is provided.
     * 
     * This is an alias of the `file` method.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function f(string $key = null): mixed
    {
        return $this->file($key);
    }

    /**
     * Get a value from the request session or all the session if no key is provided.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function session(string $key = null): mixed
    {
        if (null === $key) {
            return $this->session;
        }

        return $this->session->get($key);
    }

    
    /**
     * Add a value to the request session.
     * 
     * @param string $key The key of the value to set
     * @param mixed $value The value to set
     * @return Request The current request
     */
    public function addSession(string $key, $value): Request
    {
        $this->session->set($key, $value);
        return $this;
    }

    /**
     * Get a value from the request session or all the session if no key is provided.
     * 
     * This is an alias of the `session` method.
     *
     * @param string|null $key The key of the value to retrieve
     * @return mixed The value associated with the key or null if the key is not found
     */
    public function s(string $key = null): mixed
    {
        return $this->session($key);
    }
}
