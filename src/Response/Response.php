<?php

declare(strict_types=1);

namespace Fignon\Response;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Fignon\Request\Request;
use Fignon\Tunnel;

/**
 * The Fignon Response
 *
 * The res object represents the HTTP response that a Fignon app sends when it gets an HTTP request.
 *
 * By convention, the object is always referred to as res (and the HTTP request is req) but its actual name is determined by the parameters to the callback function in which you’re working.
 */
class Response extends SymfonyResponse
{
    /**
     * This property holds a reference to the instance of the Tunnel application that is using the middleware.
     *
     * res.app is identical to the req.app property in the request object.
     */
    public Tunnel $app;

    /**
     * Boolean property that indicates if the app sent HTTP headers for the response.
     */
    public bool $headersSent;

    /**
     * Use this property to set variables accessible in templates rendered with res.render.
     *
     * The variables set on res.locals are available within a single request-response cycle, and will not be shared between requests.
     *
     * In order to keep local variables for use in template rendering between requests, use app.locals instead.
     */
    public array $locals;

    /**
     * This property holds a reference to the request object that relates to this response object.
     */
    public Request $req;


    public function __construct(?string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
    }


    public function sendHeaders(?int $statusCode = null): static
    {
        if ($this->app->disabled('xPoweredBy')) {
            $this->headers->remove('X-Powered-By');
        } else {
            $this->headers->set('X-Powered-By', 'Fignon Php', true);
        }
        parent::sendHeaders($statusCode);
        $this->headersSent = true;
        return $this;
    }

    /**
     * Sends a JSON response.
     *
     * The parameter can be any JSON type, including object, array, string, Boolean, number, or null, and you can also use it to convert other values to JSON.
     */
    public function json($content)
    {
        $this->setContent(json_encode($content, JSON_PRETTY_PRINT));
        $this->prepare($this->req);
        $this->send(true);
        $this->end();
        return $this;
    }

    public function html(?string $content)
    {
        $this->setContent($content);
        $this->prepare($this->req);
        $this->send(true);
        $this->end();
        return $this;
    }


    /**
     * Ends the response process without any data
     *
     * If you need to send a data to the client, use instead res.json() or res.send()
     */
    public function end()
    {
        exit();
    }


    /**
     * Returns the HTTP response header specified by field. The match is case-insensitive.
     */
    public function get($field)
    {
        return $this->headers->get($field);
    }

    public function render(string $view, array $locals = [], ?array $options = [])
    {
        $this->setContent($this->app->render($view, $locals, $options));
        $this->prepare($this->req);
        $this->send(true);
        $this->end();
        return $this;
    }

    public function setApp(Tunnel $app)
    {
        $this->app = $app;
        return $this;
    }

    public function setRequest(Request $req)
    {
        $this->req = $req;
        return $this;
    }


    public function set($field, $value)
    {
        $this->headers->set($field, $value);
        return $this;
    }

    public function status($code)
    {
        $this->setStatusCode($code);
        return $this;
    }
}
