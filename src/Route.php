<?php

namespace LandKit\Route;

class Route
{
    use RouteTrait;

    /**
     * @var Route|null
     */
    private static $instance = null;

    /**
     * @var string
     */
    private static $projectUrl;

    /**
     * @var string
     */
    private static $separator;

    /**
     * @var string
     */
    private static $httpMethod;

    /**
     * @var string
     */
    private static $path;

    /**
     * @var array|null
     */
    private static $route = null;

    /**
     * @var array
     */
    private static $routes = [];

    /**
     * @var string|null
     */
    private static $controller = null;

    /**
     * @var string|null
     */
    private static $session = null;

    /**
     * @var array|null
     */
    private static $middleware = [];

    /**
     * @var array
     */
    private static $data = [];

    /**
     * @var int|null
     */
    private static $fail = null;

    /**
     * @const int
     */
    const BAD_REQUEST = 400;

    /**
     * @const int
     */
    const NOT_FOUND = 404;

    /**
     * @const int
     */
    const METHOD_NOT_ALLOWED = 405;

    /**
     * @const int
     */
    const NOT_IMPLEMENTED = 501;

    /**
     * Route constructor.
     *
     * @param string $projectUrl
     * @param string|null $separator
     */
    private function __construct(string $projectUrl, string $separator)
    {
        self::$projectUrl = substr($projectUrl, -1) == '/' ? substr($projectUrl, 0, -1) : $projectUrl;
        self::$separator = $separator ?? ':';
        self::$httpMethod = $_SERVER['REQUEST_METHOD'];
        self::$path = rtrim((filter_input(INPUT_GET, 'route') ?? '/'), '/');
    }

    /**
     * Route clone.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * @return array|null
     */
    public function __debugInfo()
    {
        return self::$routes;
    }

    /**
     * @param string $projectUrl
     * @param string|null $separator
     * @return void
     */
    public static function init(string $projectUrl, string $separator = null)
    {
        if (empty(self::$instance)) {
            self::$instance = new Route($projectUrl, $separator);
        }
    }

    /**
     * @return Route|null
     */
    public static function instance()
    {
        return self::$instance;
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function get(string $route, $handler, string $name = null, $middleware = [])
    {
        self::addRoute('GET', $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function post(string $route, $handler, string $name = null, $middleware = [])
    {
        self::addRoute('POST', $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function put(string $route, $handler, string $name = null, $middleware = [])
    {
        self::addRoute('PUT', $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function patch(string $route, $handler, string $name = null, $middleware = [])
    {
        self::addRoute('PATCH', $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function delete(string $route, $handler, string $name = null, $middleware = [])
    {
        self::addRoute('DELETE', $route, $handler, $name, $middleware);
    }

    /**
     * @param array|string $httpVerb
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function match($httpVerb, string $route, $handler, string $name = null, $middleware = [])
    {
        if (is_string($httpVerb)) {
            $httpVerb = [$httpVerb];
        }

        foreach ($httpVerb as $item) {
            self::addRoute($item, $route, $handler, $name, $middleware);
        }
    }

    /**
     * @param array|string $httpVerb
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function less($httpVerb, string $route, $handler, string $name = null, $middleware = [])
    {
        if (is_string($httpVerb)) {
            $httpVerb = [$httpVerb];
        }

        $verbs = array_diff(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $httpVerb);

        foreach ($verbs as $item) {
            self::addRoute($item, $route, $handler, $name, $middleware);
        }
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    public static function any(string $route, $handler, string $name = null, $middleware = [])
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $item) {
            self::addRoute($item, $route, $handler, $name, $middleware);
        }
    }

    /**
     * @param string|null $controller
     * @return Route
     */
    public static function controller(string $controller): Route
    {
        self::$controller = $controller ? ucwords($controller) : null;
        return self::$instance;
    }

    /**
     * @param string|null $session
     * @param array|string $middleware
     * @return Route
     */
    public static function session(string $session, $middleware = []): Route
    {
        self::$session = $session ? trim($session, '/') : null;
        self::$middleware = !$middleware ? [] : (is_string($middleware) ? [$middleware] : $middleware);

        return self::$instance;
    }

    /**
     * @return array
     */
    public static function data(): array
    {
        return self::$data;
    }

    /**
     * @return int|null
     */
    public static function fail()
    {
        return self::$fail;
    }

    /**
     * @return void
     */
    public static function dispatch()
    {
        if (empty(self::$routes) || empty(self::$routes[self::$httpMethod])) {
            self::$fail = self::NOT_IMPLEMENTED;
            return;
        }

        foreach (self::$routes[self::$httpMethod] as $key => $route) {
            if (preg_match('~^' . $key . '$~', self::$path, $found)) {
                self::$route = $route;
            }
        }

        self::execute();
    }

    /**
     * @param string $method
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string $middleware
     * @return void
     */
    private static function addRoute(string $method, string $route, $handler, string $name = null, $middleware = [])
    {
        $route = rtrim($route, '/');

        $removeSessionFromPath = self::$session ? str_replace(self::$session, '', self::$path) : self::$path;
        $pathAssoc = trim($removeSessionFromPath, '/');
        $routeAssoc = trim($route, '/');

        preg_match_all('~\{\s* ([a-zA-Z_][a-zA-Z0-9_-]*) \}~x', $routeAssoc, $keys, PREG_SET_ORDER);
        $routeDiff = array_values(array_diff_assoc(explode('/', $pathAssoc), explode('/', $routeAssoc)));

        self::formSpoofing();

        $routeParams = [];
        $offset = 0;

        foreach ($keys as $key) {
            $routeParams[$key[1]] = $routeDiff[$offset++] ?? null;
        }

        $route = !self::$session ? $route : '/' . self::$session . $route;
        $data = self::$data;
        $controller = self::$controller;
        $middleware = is_string($middleware) ? [$middleware] : $middleware;

        $router = function () use ($method, $handler, $data, $route, $name, $controller, $middleware, $routeParams) {
            return [
                'route' => $route,
                'name' => $name,
                'method' => $method,
                'middlewares' => array_merge(self::$middleware, $middleware),
                'handler' => self::handler($handler, $controller),
                'action' => self::action($handler),
                'data' => $data,
                'params' => [
                    'route' => $routeParams,
                    'query' => self::queryParams()
                ]
            ];
        };

        $route = preg_replace('~{([^}]*)}~', '([^/]+)', $route);

        self::$routes[$method][$route] = $router();
    }

    /**
     * @return void
     */
    private static function formSpoofing()
    {
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? [];

        if (!empty($post['_method']) && in_array($post['_method'], ['PUT', 'PATCH', 'DELETE'])) {
            self::$httpMethod = $post['_method'];
            self::$data = self::safeSpoofing($post);

            return;
        }

        if (self::$httpMethod == 'POST') {
            self::$data = self::safeSpoofing($post);
            return;
        }

        if (in_array(self::$httpMethod, ['PUT', 'PATCH', 'DELETE']) && !empty($_SERVER['CONTENT_LENGTH'])) {
            parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $putPatch);
            self::$data = self::safeSpoofing($putPatch);

            return;
        }

        self::$data = [];
    }

    /**
     * @param array|null $data
     * @return array|null
     */
    private static function safeSpoofing(array $data)
    {
        if (isset($data['_method'])) {
            unset($data['_method']);
        }

        return $data;
    }

    /**
     * @return array
     */
    private static function queryParams(): array
    {
        $queryParams = str_replace('?', '', strstr($_SERVER['REQUEST_URI'], '?'));
        parse_str($queryParams, $params);
        return $params ?: [];
    }

    /**
     * @return void
     */
    private static function execute()
    {
        if (!self::$route || !self::middleware()) {
            self::$fail = self::NOT_FOUND;
            return;
        }

        if (is_callable(self::$route['handler'])) {
            call_user_func(self::$route['handler'], self::$route['data'], self::$instance);

            return;
        }

        $controller = self::$route['handler'];

        if (!class_exists($controller)) {
            self::$fail = self::BAD_REQUEST;
            return;
        }

        $method = self::$route['action'];

        if (!method_exists($controller, $method)) {
            self::$fail = self::METHOD_NOT_ALLOWED;
            return;
        }

        (new $controller())->$method(self::$route['data']);
    }

    /**
     * @return bool
     */
    private static function middleware(): bool
    {
        if (empty(self::$route['middlewares'])) {
            return true;
        }

        $middlewares = is_array(self::$route['middlewares'])
            ? self::$route['middlewares']
            : [self::$route['middlewares']];

        foreach ($middlewares as $middleware) {
            if (class_exists($middleware)) {
                $newMiddleware = new $middleware;

                if (method_exists($newMiddleware, 'handle')) {
                    if (!$newMiddleware->handle()) {
                        return false;
                    }
                } else {
                    self::$fail = self::METHOD_NOT_ALLOWED;
                    return false;
                }
            } else {
                self::$fail = self::NOT_IMPLEMENTED;
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable|string $handler
     * @param string|null $controller
     * @return callable|string
     */
    private static function handler($handler, string $controller)
    {
        return !is_string($handler) ? $handler : "{$controller}\\" . explode(self::$separator, $handler)[0];
    }

    /**
     * @param callable|string $handler
     * @return string|null
     */
    private static function action($handler)
    {
        return !is_string($handler) ?: (explode(self::$separator, $handler)[1] ?? null);
    }

    /**
     * @param array $routeItem
     * @param array|null $data
     * @return string|null
     */
    private static function treat(array $routeItem, array $data = null)
    {
        $route = $routeItem['route'];

        if (!empty($data)) {
            $arguments = [];
            $params = [];

            foreach ($data as $key => $value) {
                if (!strstr($route, "{{$key}}")) {
                    $params[$key] = $value;
                }

                $arguments["{{$key}}"] = $value;
            }

            $route = self::process($route, $arguments, $params);
        }

        return self::$projectUrl . $route;
    }

    /**
     * @param string $route
     * @param array $arguments
     * @param array|null $params
     * @return string
     */
    private static function process(string $route, array $arguments, array $params = null): string
    {
        $params = !empty($params) ? '?' . http_build_query($params) : null;
        return str_replace(array_keys($arguments), array_values($arguments), $route) . $params;
    }
}
