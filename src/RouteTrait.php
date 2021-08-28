<?php

namespace LandKit\Route;

trait RouteTrait
{
    /**
     * @return string
     */
    public static function currentRouteUrl(): string
    {
        return !self::$currentRoute ? '' : self::$projectUrl . self::$currentRoute['route'];
    }

    /**
     * @return array
     */
    public static function currentRouteData(): array
    {
        return self::$currentRoute['data'] ?? [];
    }

    /**
     * @param string ...$names
     * @return bool
     */
    public static function isCurrentRouteByName(string ...$names): bool
    {
        if (self::$currentRoute && self::$currentRoute['name'] != '') {
            return in_array(self::$currentRoute['name'], $names);
        }

        return false;
    }

    /**
     * @param string $value
     * @param array $data
     * @return string
     */
    public static function urlByName(string $value, array $data = []): string
    {
        foreach (self::$routes as $httpMethod) {
            foreach ($httpMethod as $route) {
                if (!empty($route['name']) && $route['name'] == $value && $route['name'] != '') {
                    return self::$instance->treat($route, $data);
                }
            }
        }

        return '';
    }

    /**
     * @param string $urlOrRouteName
     * @param array $data
     * @return void
     */
    public static function redirect(string $urlOrRouteName, array $data = [])
    {
        if ($url = self::urlByName($urlOrRouteName, $data)) {
            header("Location: {$url}");
            exit;
        }

        if (filter_var($urlOrRouteName, FILTER_VALIDATE_URL)) {
            header("Location: {$urlOrRouteName}");
            exit;
        }

        header('Location: ' . self::$projectUrl . "/{$urlOrRouteName}");
        exit;
    }
}