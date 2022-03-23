<?php

namespace LandKit\Route;

trait MiddlewareTrait
{
    /**
     * @var array
     */
    private static $middlewares = [];

    /**
     * @var array
     */
    protected static $currentMiddlewares = [];

    /**
     * @var array
     */
    private static $queue = [];

    /**
     * @var int
     */
    private static $currentQueueNumber = 0;

    /**
     * @return array
     */
    public static function getMiddlewares(): array
    {
        return self::$middlewares;
    }

    /**
     * @param array $value
     * @return void
     */
    public static function setMiddlewares(array $value)
    {
        self::$middlewares = array_merge(self::$middlewares, $value);
    }

    /**
     * @param string ...$keys
     * @return void
     */
    public static function removeMiddlewares(string ...$keys)
    {
        foreach ($keys as $key) {
            if (isset(self::$middlewares[$key])) {
                unset(self::$middlewares[$key]);
            }
        }
    }

    /**
     * @param string ...$values
     * @return Route
     */
    public static function middleware(string ...$values): Route
    {
        self::$currentMiddlewares = $values;
        return self::$instance;
    }

    /**
     * @return void
     */
    private function resetCurrentMiddlewares()
    {
        self::$currentMiddlewares = [];
    }

    /**
     * @return void
     */
    private function prepareMiddlewares()
    {
        foreach (self::$currentRoute['middlewares'] as $middleware) {
            $middleware = trim(rtrim($middleware));

            if (isset(self::$middlewares[$middleware]) && is_array(self::$middlewares[$middleware])) {
                foreach (self::$middlewares[$middleware] as $class) {
                    self::$queue[] = $this->instanceMiddleware($class);
                }

                continue;
            }

            self::$queue[] = $this->instanceMiddleware($middleware);
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function getMiddlewareByAlias(string $value): string
    {
        if (!array_key_exists($value, self::$middlewares)) {
            return '';
        }

        if (class_exists(self::$middlewares[$value])) {
            return self::$middlewares[$value];
        }

        return '';
    }

    /**
     * @param string $middleware
     * @return object|null
     */
    private function instanceMiddleware(string $middleware)
    {
        if (!preg_match('/\\\/', $middleware)) {
            $middlewareClass = $this->getMiddlewareByAlias($middleware);

            return !$middlewareClass ? null : new $middlewareClass();
        }

        if (class_exists($middleware)) {
            return new $middleware();
        }

        return null;
    }

    /**
     * @return bool
     */
    private function next(): bool
    {
        self::$currentQueueNumber++;
        return $this->callMiddlewares();
    }

    /**
     * @return void
     */
    private function reset()
    {
        self::$currentQueueNumber = 0;
    }

    /**
     * @return bool
     */
    private function callMiddlewares(): bool
    {
        if (!isset(self::$queue[self::$currentQueueNumber])) {
            $this->reset();
            return true;
        }

        $currentMiddleware = self::$queue[self::$currentQueueNumber];

        if (empty($currentMiddleware)) {
            return $this->next();
        }

        return $currentMiddleware->handle(
            self::$currentRoute['data'],
            function () {
                return $this->next();
            }
        );
    }
}