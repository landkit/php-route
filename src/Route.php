<?php

namespace LandKit\Route;

class Route
{
    use RouteTrait;
    use MiddlewareTrait;

    /**
     * @var Route
     */
    protected static $instance;

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
    private static $patch;

    /**
     * @var string
     */
    private static $httpMethod;

    /**
     * @var string
     */
    protected static $controller;

    /**
     * @var string
     */
    protected static $session;

    /**
     * @var array
     */
    private static $routes;

    /**
     * @var array
     */
    private static $currentRoute;

    /**
     * @var array
     */
    private static $data;

    /**
     * @var int|null
     */
    private static $fail;

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
     * Create new Route instance.
     */
    private function __construct()
    {
        self::$patch = filter_input(INPUT_GET, 'route', FILTER_DEFAULT) ?? '/';
        self::$httpMethod = $_SERVER['REQUEST_METHOD'];
        self::$controller = '';
        self::$session = '';
        self::$routes = [];
        self::$currentRoute = [];
        self::$data = [];
        self::$fail = null;
    }

    /**
     * Block Route instance cloning.
     */
    private function __clone()
    {
    }

    /**
     * @param string $projectUrl
     * @param string $separator
     * @return void
     */
    public static function init(string $projectUrl, string $separator = ':')
    {
        if (empty(self::$instance)) {
            self::$instance = new Route();
            self::$projectUrl = $projectUrl;
            self::$separator = $separator;
        }
    }

    /**
     * @return Route
     */
    public static function instance(): Route
    {
        return self::$instance;
    }

    /**
     * @return string
     */
    public static function projectUrl(): string
    {
        return self::$projectUrl;
    }

    /**
     * @param string $namespace
     * @return void
     */
    public static function controller(string $namespace)
    {
        self::$controller = $namespace;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function session(string $value)
    {
        self::$session = $value;
    }

    /**
     * @return array
     */
    public static function routes(): array
    {
        return self::$routes;
    }

    /**
     * @return array
     */
    public static function currentRoute(): array
    {
        return self::$currentRoute;
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
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function get(string $route, $handler, string $name = '')
    {
        self::$instance->addRoute('GET', $route, $handler, $name)->resetCurrentMiddlewares();
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function post(string $route, $handler, string $name = '')
    {
        self::$instance->addRoute('POST', $route, $handler, $name)->resetCurrentMiddlewares();
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function put(string $route, $handler, string $name = '')
    {
        self::$instance->addRoute('PUT', $route, $handler, $name)->resetCurrentMiddlewares();
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function patch(string $route, $handler, string $name = '')
    {
        self::$instance->addRoute('PATCH', $route, $handler, $name)->resetCurrentMiddlewares();
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function delete(string $route, $handler, string $name = '')
    {
        self::$instance->addRoute('DELETE', $route, $handler, $name)->resetCurrentMiddlewares();
    }

    /**
     * @param array $httpMethods
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function match(array $httpMethods, string $route, $handler, string $name = '')
    {
        foreach ($httpMethods as $httpMethod) {
            self::$instance->addRoute($httpMethod, $route, $handler, $name);
        }

        self::$instance->resetCurrentMiddlewares();
    }

    /**
     * @param array $httpMethods
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function less(array $httpMethods, string $route, $handler, string $name = '')
    {
        $httpMethodsDiff = array_diff(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $httpMethods);

        foreach ($httpMethodsDiff as $httpMethod) {
            self::$instance->addRoute($httpMethod, $route, $handler, $name);
        }

        self::$instance->resetCurrentMiddlewares();
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return void
     */
    public static function any(string $route, $handler, string $name = '')
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $httpMethod) {
            self::$instance->addRoute($httpMethod, $route, $handler, $name);
        }

        self::$instance->resetCurrentMiddlewares();
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
     * @return void
     */
    public static function dispatch()
    {
        if (!self::$routes || empty(self::$routes[self::$httpMethod])) {
            self::$fail = self::NOT_IMPLEMENTED;
            return;
        }

        foreach (self::$routes[self::$httpMethod] as $key => $route) {
            if (preg_match("~^{$key}$~", self::$patch)) {
                self::$currentRoute = $route;
                break;
            }
        }

        self::$instance->execute();
    }

    /**
     * @param string $httpMethod
     * @param string $route
     * @param callable|string $handler
     * @param string $name
     * @return Route
     */
    private function addRoute(string $httpMethod, string $route, $handler, string $name): Route
    {
        if ($route == '/') {
            $this->addRoute($httpMethod, '', $handler, $name);
        }

        $this->formSpoofing();

        preg_match_all("~{\s* ([a-zA-Z_][a-zA-Z0-9_-]*) }~x", $route, $keys, PREG_SET_ORDER);

        $routeDiff = array_diff(explode('/', self::$patch), explode('/', $route));
        $routeValues = array_values($routeDiff);

        $offset = !self::$session ? 0 : 1;

        foreach ($keys as $key) {
            self::$data[$key[1]] = $routeValues[$offset++] ?? null;
        }

        $data = self::$data;
        $session = self::$session;
        $route = !$session ? $route : "/{$session}{$route}";

        $router = function () use (
            $session,
            $route,
            $name,
            $httpMethod,
            $handler,
            $data
        ) {
            return [
                'session' => $session,
                'route' => $route,
                'name' => $name,
                'httpMethod' => $httpMethod,
                'handler' => $this->handler($handler),
                'middleware' => self::$currentMiddlewares,
                'action' => $this->action($handler),
                'data' => $data
            ];
        };

        $route = preg_replace('~{([^}]*)}~', '([^/]+)', $route);
        self::$routes[$httpMethod][$route] = $router();

        return $this;
    }

    /**
     * @return void
     */
    private function execute()
    {
        if (!self::$currentRoute) {
            self::$fail = self::NOT_FOUND;
            return;
        }

        if (is_callable(self::$currentRoute['handler'])) {
            call_user_func(self::$currentRoute['handler'], self::$currentRoute['data'] ?? []);
            return;
        }

        $middleware = self::$currentRoute['middleware'];

        if ($middleware) {
            if (!$this->executeMiddleware()) {
                self::$fail = self::NOT_FOUND;
                return;
            }
        }

        $controller = self::$currentRoute['handler'];

        if (!class_exists($controller)) {
            self::$fail = self::BAD_REQUEST;
            return;
        }

        $method = self::$currentRoute['action'];

        if (!method_exists($controller, $method)) {
            self::$fail = self::METHOD_NOT_ALLOWED;
            return;
        }

        (new $controller())->$method(self::$currentRoute['data'] ?? []);
    }

    /**
     * @return void
     */
    private function formSpoofing()
    {
        $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $httpMethods = ['PUT', 'PATCH', 'DELETE'];

        if ($post && !empty($post['_method']) && in_array($post['_method'], $httpMethods)) {
            self::$httpMethod = $post['_method'];
            self::$data = $post;
            unset(self::$data['_method']);

            return;
        }

        if (in_array(self::$httpMethod, $httpMethods) && !empty($_SERVER['CONTENT_LENGTH'])) {
            parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $putPatch);
            self::$data = $putPatch;

            return;
        }

        if ($post && self::$httpMethod == 'POST') {
            self::$data = $post;
            return;
        }

        if (self::$httpMethod == 'GET') {
            $queryParams = str_replace('?', '', strstr($_SERVER['REQUEST_URI'], '?'));
            $queryParams = filter_var($queryParams, FILTER_SANITIZE_STRIPPED);

            parse_str($queryParams, self::$data);
            unset(self::$data['_method']);

            return;
        }

        self::$data = [];
    }

    /**
     * @param callable|string $value
     * @return callable|string
     */
    private function handler($value)
    {
        return !is_string($value) ? $value : self::$controller . '\\' . explode(self::$separator, $value)[0];
    }

    /**
     * @param callable|string $value
     * @return bool|string|null
     */
    private function action($value)
    {
        return !is_string($value) ? true : explode(self::$separator, $value)[1] ?? null;
    }

    /**
     * @param array $route
     * @param array $data
     * @return string
     */
    private function treat(array $route, array $data = []): string
    {
        $route = $route['route'];

        if ($data) {
            $arguments = [];
            $params = [];

            foreach ($data as $key => $value) {
                if (!strstr($route, "{{$key}}")) {
                    $params[$key] = $value;
                }

                $arguments["{{$key}}"] = $value;
            }

            $route = $this->process($route, $arguments, $params);
        }

        return self::$projectUrl . $route;
    }

    /**
     * @param string $route
     * @param array $arguments
     * @param array $params
     * @return string
     */
    private function process(string $route, array $arguments, array $params = []): string
    {
        $route = str_replace(array_keys($arguments), array_values($arguments), $route);
        return $route . (!$params ? '' : '?' . http_build_query($params));
    }
}