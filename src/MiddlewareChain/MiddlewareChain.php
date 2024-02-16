<?php

declare(strict_types=1);

namespace Fignon\MiddlewareChain;

use Fignon\Request\Request;
use Fignon\Response\Response;
use Fignon\Error\TunnelError;
use Fignon\Helper\MiddlewareHelper;

class MiddlewareChain
{
    protected array $chain = [];
    protected int $index = 0;
    protected MiddlewareHelper $helper;

    public function __construct($chain = [])
    {
        $this->helper = new MiddlewareHelper();

        foreach ($chain as $middleware) {
            if (!$this->helper->isValidMiddleware($middleware)) {
                throw new TunnelError("Middleware must have either (Error err, Request req, Response res, Closure next) or (Request req, Response res, Closure next) or (Request req, Response res) signature");
            }
        }

        $this->chain = $chain;
    }


    public function getChain(): array
    {
        return $this->chain;
    }

    public function getIndex(): int
    {
        return $this->index;
    }


    /**
     * Add a middleware to the chain
     *
     * @param mixed $middleware The middleware to add
     *
     * @return MiddlewareChain
     */
    public function use(mixed $middleware): MiddlewareChain
    {
        if (!$this->helper->isValidMiddleware($middleware)) {
            throw new TunnelError("Middleware must have either (Error err, Request req, Response res, Closure next) or (Request req, Response res, Closure next) or (Request req, Response res) signature");
        }

        $this->chain[] = $middleware;

        return $this;
    }


    /**
     * Execute the middleware chain.
     *
     * @param mixed $errorPassedToErrorChain It's useful to run error middleware.
     * @param Request $req The request object.
     * @param Response $res The response object.
     * @param MiddlewareChain $errorChain The error middleware chain to run when an error occurs
     * @return void
     */
    public function run($errorPassedToErrorChain = null, $req, $res, $errorChain = null): void
    {
        // This is required to always start chain execution at the beginning at each request
        $this->index = 0;

        /**
         * Run next middleware when a previous middleware calls the next() function
         * @param mixed $errorPassedToNext If any error is passed to the next($error) function, it's this
         * @return void
         */
        $runNextMiddleware = function ($errorPassedToNext = null) use ($req, $res, $errorChain, $errorPassedToErrorChain, &$runNextMiddleware) {

            // When a previous middleware calls the next($err) function with an error
            if ($errorPassedToNext && $errorChain) {
                $errorChain->run($errorPassedToNext, $req, $res, null);
                return;
            }

            if ($this->index < count($this->chain)) {
                $currentMiddleware = $this->chain[$this->index];

                // Move up the cursor to the next middleware
                $this->index++;

                // We should run error middleware chain either
                // when the user explicitly calls the `run($errorPassedToErrorChain, $req, $res, $errorChain)` method with an error
                // or when a middleware calls the next($err) with an error object
                $errorToNext = $errorPassedToNext ?? $errorPassedToErrorChain;

                if ($errorToNext) {
                    $currentMiddleware($errorToNext, $req, $res, $runNextMiddleware);
                } else {
                    $currentMiddleware($req, $res, $runNextMiddleware);
                }
            }
        };

        // Ensure that any error thrown by a middleware is caught and passed to the error chain
        try {
            $runNextMiddleware();
        } catch (\Exception $e) {
            if ($errorChain) {
                $errorChain->run($e, $req, $res, null);
            }
        } catch (\Error $err) {
            if ($errorChain) {
                $errorChain->run($err, $req, $res, null);
            }
        }
    }
}
