<?php

declare(strict_types=1);

namespace Fignon\Helper;

use Fignon\Router\Router;

class MiddlewareHelper
{
    public function hasValidSignature(mixed $middleware): bool
    {
        return $this->isClosureWithValidParameters($middleware)
            || $this->isClassWithInvokeMethod($middleware)
            || $this->isClassMethodWithValidParameters($middleware);
    }


    public function isValidMiddleware(mixed $middleware): bool
    {
        return $this->hasValidSignature($middleware)
            && $this->isValidNumberOfParameters($middleware);
    }


    public function isErrorMiddleware(mixed $middleware): bool
    {
        return $this->isValidMiddleware($middleware)
            && $this->hasFourParameters($middleware);
    }


    private function isClosureWithValidParameters(mixed $middleware): bool
    {
        return $middleware instanceof \Closure
            && \in_array((new \ReflectionFunction($middleware))->getNumberOfParameters(), [2, 3, 4]);
    }

    private function isClassWithInvokeMethod(mixed $middleware): bool
    {
        return \is_object($middleware)
            && \method_exists($middleware, '__invoke');
    }


    /**
     * Check if a middleware is a class's method with 2, 3, or 4 parameters (supports static methods).
     *
     * @param mixed $middleware
     * @return bool
     */
    private function isClassMethodWithValidParameters(mixed $middleware): bool
    {
        if (\is_array($middleware)) {
            list($class, $method) = $middleware;

            // Instance Method
            if (\is_object($class)) {
                return $this->isValidClassMethod($class, $method);
            }

            // Static Method
            if (\is_string($class) && \class_exists($class)) {
                // Static method
                return $this->isValidStaticMethod($class, $method);
            }
        }

        return false;
    }


    private function isValidClassMethod(mixed $class, mixed $method): bool
    {
        return \is_object($class)
            && \method_exists($class, $method)
            && \in_array((new \ReflectionMethod($class, $method))->getNumberOfParameters(), [2, 3, 4]);
    }


    private function isValidStaticMethod(mixed $class, mixed $method): bool
    {
        return \is_string($class)
            && \class_exists($class)
            && \method_exists($class, $method)
            && \in_array((new \ReflectionMethod($class, $method))->getNumberOfParameters(), [2, 3, 4]);
    }

    private function isValidNumberOfParameters(mixed $middleware): bool
    {
        return $this->hasTwoParameters($middleware)
            || $this->hasThreeParameters($middleware)
            || $this->hasFourParameters($middleware);
    }

    private function hasTwoParameters(mixed $middleware): bool
    {
        return 2 === $this->getNumberOfParameters($middleware);
    }

    private function hasThreeParameters(mixed $middleware): bool
    {
        return 3 === $this->getNumberOfParameters($middleware);
    }

    private function hasFourParameters(mixed $middleware): bool
    {
        return 4 === $this->getNumberOfParameters($middleware);
    }

    public function getNumberOfParameters(mixed $middleware): int
    {
        // Closure
        if ($middleware instanceof \Closure) {
            return (new \ReflectionFunction($middleware))->getNumberOfParameters();
        }

        // Static method or instance method
        if (\is_array($middleware) && 2 === \count($middleware)) {
            list($class, $method) = $middleware;
            return (new \ReflectionMethod($class, $method))->getNumberOfParameters();
        }

        // Callable class (class with __invoke method)
        if (\is_object($middleware) && \method_exists($middleware, '__invoke')) {
            return (new \ReflectionMethod($middleware, '__invoke'))->getNumberOfParameters();
        }

        throw new \InvalidArgumentException('Invalid middleware type');
    }

    /**
     * Check if the given arguments is a router and countable
     */
    public function isRouter(mixed $args): bool
    {
        return 1 === \count($args) && $args[0] instanceof Router;
    }

    /**
     * Check if the given arguments is a $path with a router as second argument
     */
    public function isPathAndRouter(mixed $args): bool
    {
        return (2 === \count($args) && \is_string($args[0]) && $args[1] instanceof Router);
    }

    /**
     * Check if the given arguments is a list of middlewares as closure
     */
    public function isMiddlewareAsClosure(mixed $args): bool
    {
        foreach ($args as $arg) {
            if (!$arg instanceof \Closure) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the given arguments is a $path with
     * a list of middlewares as closure as second argument
     */
    public function isPathAndMiddlewareAsClosure(mixed $args): bool
    {
        return \is_string($args[0])
        && $this->isMiddlewareAsClosure(\array_slice($args, 1));
    }


    /**
     * Check if the given arguments is a list of middlewares as callable
     */
    public function isMiddlewareAsCallable(mixed $args): bool
    {
        foreach ($args as $arg) {
            if (!\is_callable($arg)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the given arguments is a $path with a list of middlewares as callable as second argument
     */
    public function isPathAndMiddlewareAsCallable(mixed $args): bool
    {
        return \is_string($args[0])
        && $this->isMiddlewareAsCallable(\array_slice($args, 1));
    }



    /**
     * Check if the given arguments is a list of middlewares as static method
     *
     * Should able to detect middleware lik this:
     *
     * $app->use([MyClassWithStaticMethod::class, 'staticMethod'],[MyOtherClassWithStaticMethod::class, 'staticMethod2']);
     *
     */
    public function isMiddlewareAsStaticMethod(mixed $args): bool
    {
        foreach ($args as $arg) {
            if (
                \is_array($arg)
                && 2 === \count($arg)
                && \is_string($arg[0])
                && \is_string($arg[1])
                && method_exists($arg[0], $arg[1])
            ) {
                continue;
            }
            return false;
        }
        return true;
    }

    /**
     * Check if the given argument is a path and a list of middleware as static method
     * as second argument
     */
    public function isPathAndMiddlewareAsStaticMethod(mixed $args): bool
    {
        return \is_string($args[0])
            && $this->isMiddlewareAsStaticMethod(\array_slice($args, 1));
    }

    /**
     * Check if the given arguments is a list of middlewares as  method of class
     *
     * Should able to detect middleware lik this:
     *
     * $app->use([new MyClassWithMethod(), 'aMethod'],[new MyOtherClassWithMethod(), 'otherMethod']);
     *
     */
    public function isMiddlewareAsNonStaticMethod(mixed $args): bool
    {
        foreach ($args as $arg) {
            if (
                !\is_array($arg)
                || 2 !== \count($arg)
                || !\is_object($arg[0])
                || !\is_string($arg[1])
                || !\method_exists($arg[0], $arg[1])
            ) {
                return false;
            }
        }
        return true;
    }


    /**
     * Check if the arg is a path and a list of non static method a class
     */
    public function isPathAndMiddlewareAsNonStaticMethod(mixed $args): bool
    {
        return \is_string($args[0])
            && $this->isMiddlewareAsNonStaticMethod(\array_slice($args, 1));
    }


    /**
     * Check if the given param is a router.
     *
     * Useful when user mixed different middleware type in the same use() method
     */
    public function isSingleRouter(mixed $middleware): bool
    {
        return $middleware instanceof Router;
    }

    /**
     * Check if the given param is a single middleware as closure
     *
     * Useful when user mixed different middleware type in the same use() method
     */
    public function isSingleMiddlewareAsClosure(mixed $middleware): bool
    {
        return $middleware instanceof \Closure;
    }


    /**
     * Check if the given param is a single middleware as callable
     *
     * Useful when user mixed different middleware type in the same use() method
     */
    public function isSingleMiddlewareAsCallable(mixed $middleware): bool
    {
        return \is_callable($middleware);
    }


    /**
     * Check if the given param is a single middleware as static method
     *
     * Useful when user mixed different middleware type in the same use() method
     */
    public function isSingleMiddlewareAsStaticMethod(mixed $middleware): bool
    {
        return \is_array($middleware)
            && 2 === \count($middleware)
            && \is_string($middleware[0])
            && \is_string($middleware[1])
            && \method_exists($middleware[0], $middleware[1]);
    }


    /**
     * Check if the given param is a single middleware as non static method
     *
     * Useful when user mixed different middleware type in the same use() method
     */
    public function isSingleMiddlewareAsNonStaticMethod(mixed $middleware): bool
    {
        return \is_array($middleware)
            && 2 === \count($middleware)
            && \is_object($middleware[0])
            && \is_string($middleware[1])
            && \method_exists($middleware[0], $middleware[1]);
    }
}
