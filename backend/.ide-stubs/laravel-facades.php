<?php

/**
 * IDE stubs for Laravel facades (Route, Auth, DB, Hash, etc.)
 * Provides correct return-type hints so Intelephense resolves
 * chained calls like Route::prefix()->middleware()->group().
 *
 * NOT loaded at runtime — static analysis only.
 */

namespace Illuminate\Routing;

use Closure;

/**
 * Stub for RouteRegistrar — exposes magic __call attributes as typed methods
 * so Intelephense resolves chained Route::prefix()->middleware()->group() calls.
 *
 * @see \Illuminate\Routing\RouteRegistrar (real class in vendor)
 */
class RouteRegistrar
{
    public function __construct() {}

    /** @return static */
    public function middleware(array|string|null $middleware): static { return $this; }

    /** @return static */
    public function prefix(string $prefix): static { return $this; }

    /** @return static */
    public function name(string $name): static { return $this; }

    /** @return static */
    public function domain(string $domain): static { return $this; }

    /** @return static */
    public function namespace(string $namespace): static { return $this; }

    /** @return static */
    public function where(array|string $name, ?string $expression = null): static { return $this; }

    /** @return static */
    public function withoutMiddleware(array|string $middleware): static { return $this; }

    /** @return Router|void */
    public function group(Closure|string|array $callback) {}

    /** @return Route */
    public function get(string $uri, array|string|Closure|null $action = null): Route { return new Route('GET', $uri, $action); }

    /** @return Route */
    public function post(string $uri, array|string|Closure|null $action = null): Route { return new Route('POST', $uri, $action); }

    /** @return Route */
    public function put(string $uri, array|string|Closure|null $action = null): Route { return new Route('PUT', $uri, $action); }

    /** @return Route */
    public function patch(string $uri, array|string|Closure|null $action = null): Route { return new Route('PATCH', $uri, $action); }

    /** @return Route */
    public function delete(string $uri, array|string|Closure|null $action = null): Route { return new Route('DELETE', $uri, $action); }
}

namespace Illuminate\Support\Facades;

use Illuminate\Routing\RouteRegistrar;

/**
 * @see \Illuminate\Routing\Router
 */
class Route
{
    /** @return RouteRegistrar */
    public static function prefix(string $prefix): RouteRegistrar { return new RouteRegistrar(app('router')); }

    /** @param array|string|null $middleware @return RouteRegistrar */
    public static function middleware(array|string|null $middleware): RouteRegistrar { return new RouteRegistrar(app('router')); }

    /** @param \Closure|string|array $callback @return Router|void */
    public static function group(\Closure|string|array $callback) {}

    /** @return RouteRegistrar */
    public static function name(string $name): RouteRegistrar { return new RouteRegistrar(app('router')); }

    /** @return RouteRegistrar */
    public static function domain(string $domain): RouteRegistrar { return new RouteRegistrar(app('router')); }

    /** @return RouteRegistrar */
    public static function namespace(string $namespace): RouteRegistrar { return new RouteRegistrar(app('router')); }

    /** @return \Illuminate\Routing\Route */
    public static function get(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('GET', $uri, $action); }

    /** @return \Illuminate\Routing\Route */
    public static function post(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('POST', $uri, $action); }

    /** @return \Illuminate\Routing\Route */
    public static function put(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('PUT', $uri, $action); }

    /** @return \Illuminate\Routing\Route */
    public static function patch(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('PATCH', $uri, $action); }

    /** @return \Illuminate\Routing\Route */
    public static function delete(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('DELETE', $uri, $action); }

    /** @return \Illuminate\Routing\Route */
    public static function options(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('OPTIONS', $uri, $action); }

    /** @return \Illuminate\Routing\Route */
    public static function any(string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('GET', $uri, $action); }

    /** @param array|string $methods @return \Illuminate\Routing\Route */
    public static function match(array|string $methods, string $uri, array|string|\Closure|null $action = null): \Illuminate\Routing\Route { return new \Illuminate\Routing\Route('GET', $uri, $action); }

    public static function apiResource(string $name, string $controller, array $options = []): void {}
    public static function resource(string $name, string $controller, array $options = []): void {}
    public static function fallback(array|string|\Closure|null $action): void {}
}
