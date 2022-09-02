<?php

namespace LandKit\Route;

trait RouteTrait
{
    /**
     * @return string
     */
    public static function home(): string
    {
        return self::$projectUrl;
    }

    /**
     * @return bool
     */
    public static function isGet(): bool
    {
        return self::$httpMethod == 'GET';
    }

    /**
     * @return bool
     */
    public static function isPost(): bool
    {
        return self::$httpMethod == 'POST';
    }

    /**
     * @return bool
     */
    public static function isPut(): bool
    {
        return self::$httpMethod == 'PUT';
    }

    /**
     * @return bool
     */
    public static function isPatch(): bool
    {
        return self::$httpMethod == 'PATCH';
    }

    /**
     * @return bool
     */
    public static function isDelete(): bool
    {
        return self::$httpMethod == 'DELETE';
    }

    /**
     * @param string $name
     * @param array|null $data
     * @return string|null
     */
    public static function route(string $name, array $data = null)
    {
        foreach (self::$routes as $httpVerb) {
            foreach ($httpVerb as $routeItem) {
                if (!empty($routeItem['name']) && $routeItem['name'] == $name) {
                    return self::treat($routeItem, $data);
                }
            }
        }

        return null;
    }

    /**
     * @return object|null
     */
    public static function current()
    {
        return (object) array_merge(
            [
                'controller' => self::$controller,
                'session' => self::$session,
                'path' => self::$path
            ],
            (self::$route ?? [])
        );
    }

    /**
     * @return string
     */
    public static function currentUrl(): string
    {
        return self::home() . self::current()->path;
    }

    /**
     * @param string ...$names
     * @return bool
     */
    public static function isCurrent(string ...$names): bool
    {
        if (!self::$route || !in_array(self::$route['name'], $names)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $route
     * @param array $data
     * @return void
     */
    public static function redirect(string $route, array $data = [])
    {
        if ($url = self::route($route, $data)) {
            header("Location: {$url}");
            exit;
        }

        if (filter_var($route, FILTER_VALIDATE_URL)) {
            header("Location: {$route}");
            exit;
        }

        $route = substr($route, -1) == '/' ? $route : "/{$route}";
        header('Location: ' . self::$projectUrl . $route);

        exit;
    }

    /**
     * @return array|null
     */
    public static function getJsonData()
    {
        return json_decode(file_get_contents('php://input'), true) ?? null;
    }

    /**
     * @return array
     */
    public static function getRouteParams(): array
    {
        return self::getParams('route');
    }

    /**
     * @return array
     */
    public static function getQueryParams(): array
    {
        return self::getParams('query');
    }

    /**
     * @param string $type
     * @return array
     */
    private static function getParams(string $type): array
    {
        return self::$route ? self::$route['params'][$type] : [];
    }
}