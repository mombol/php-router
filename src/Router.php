<?php

namespace Mombol\Router;

/**
 * @method static Router get(string $route, Callable $callback)
 * @method static Router post(string $route, Callable $callback)
 * @method static Router put(string $route, Callable $callback)
 * @method static Router delete(string $route, Callable $callback)
 * @method static Router options(string $route, Callable $callback)
 * @method static Router head(string $route, Callable $callback)
 */
class Router
{
    public static $routes = array();
    public static $methods = array();
    public static $callbacks = array();
    public static $patterns = array(
        ':any' => '[^/]+',
        ':num' => '[0-9]+',
        ':all' => '.*'
    );
    public static $error_callback;

    /**
     * Defines a route w/ callback and method
     */
    public static function __callstatic($method, $params)
    {
        $uri = '/' . trim($params[0], '/');

//        $uri = rtrim($uri, '/');
        $callback = $params[1];

        array_push(self::$routes, $uri);
        array_push(self::$methods, strtoupper($method));
        array_push(self::$callbacks, $callback);
    }

    /**
     * Defines callback if route is not found
     */
    public static function error($callback)
    {
        self::$error_callback = $callback;
    }

    /**
     * Runs the callback for the given request
     */
    public static function dispatch($callback = null)
    {
        $uri = self::detect_uri();
        $method = $_SERVER['REQUEST_METHOD'];

        $searches = array_keys(static::$patterns);
        $replaces = array_values(static::$patterns);

        $found_route = false;
        $return = null;

        self::$routes = str_replace('//', '/', self::$routes);

        // Check if route is defined without regex
        if (in_array($uri, self::$routes)) {
            $route_pos = array_keys(self::$routes, $uri);
            foreach ($route_pos as $route) {
                // Using an ANY option to match both GET and POST requests
                if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY') {

                    $found_route = true;

                    // If route is not an object
                    if (!is_object(self::$callbacks[$route])) {

                        // Grab all parts based on a / separator
                        $parts = explode('/', self::$callbacks[$route]);

                        // Collect the last index of the array
                        $last = end($parts);

                        // Grab the controller name and method call
                        $segments = explode('@', $last);

                        // Instanitate controller
                        $controller = new $segments[0]();

                        // Call method and return
                        $return = $controller->{$segments[1]}();
                    } else {
                        // Call closure and return
                        $return = call_user_func(self::$callbacks[$route]);
                    }
                }
            }
        } else {
            // Check if defined with regex
            $pos = 0;
            foreach (self::$routes as $route) {
                if (strpos($route, ':') !== false) {
                    $route = str_replace($searches, $replaces, $route);
                }

                $route = self::escapeRegExp($route);

                if (preg_match('#^' . $route . '$#', $uri, $matched)) {
                    if (self::$methods[$pos] == $method || self::$methods[$pos] == 'ANY') {

                        $found_route = true;

                        // Remove $matched[0] as [1] is the first parameter.
                        array_shift($matched);

                        if (!is_object(self::$callbacks[$pos])) {

                            // Grab all parts based on a / separator
                            $parts = explode('/', self::$callbacks[$pos]);

                            // Collect the last index of the array
                            $last = end($parts);

                            // Grab the controller name and method call
                            $segments = explode('@', $last);

                            // Instanitate controller
                            $controller = new $segments[0]();

                            // Fix multi parameters
                            if (!method_exists($controller, $segments[1])) {
                                throw new \InvalidArgumentException('action not found');
                            } else {
                                $return = call_user_func_array(array($controller, $segments[1]), $matched);
                            }
                        } else {
                            $return = call_user_func_array(self::$callbacks[$pos], $matched);
                        }
                    }
                }
                $pos++;
            }
        }

        if ($found_route) {
            if (is_string($callback)) {
                $callback_segments = explode('@', $callback);
                $callback_class = $callback_segments[0];
                $callback_function = $callback_segments[1];
                $callback_class::$callback_function($return);
            } else if ($callback instanceof \Closure) {
                call_user_func($callback, $return);
            }
        } else {
            // Run the error callback if the route was not found
            if (!self::$error_callback) {
                self::$error_callback = function () {
                    header($_SERVER['SERVER_PROTOCOL'] . " 404 Not Found");
                    return '404';
                };
            } else {
                if (is_string(self::$error_callback)) {
                    self::get($_SERVER['REQUEST_URI'], self::$error_callback);
                    self::$error_callback = null;
                    return self::dispatch($callback);
                }
            }
            return call_user_func(self::$error_callback);
        }
    }

    /**
     * Escape regular express
     *
     * @param $reg_exp
     * @return mixed
     */
    private static function escapeRegExp($reg_exp)
    {
        return str_replace(array('#'), array('\#'), $reg_exp);
    }

    /**
     * detect true URI
     *
     * @return mixed|string
     */
    private static function detect_uri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        if (($query_pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $query_pos);
        }
//        print_r($_SERVER);exit;
//        echo $uri;exit;
        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])) - 1);
        }
//        echo $uri;exit;
        if ($uri == '/' || empty($uri)) {
            return '/';
        }
        $uri = parse_url($uri, PHP_URL_PATH);
        return str_replace(array('//', '../'), '/', rtrim($uri, '/'));
    }
}
