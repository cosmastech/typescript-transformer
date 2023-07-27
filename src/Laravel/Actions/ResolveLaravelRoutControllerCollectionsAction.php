<?php

namespace Spatie\TypeScriptTransformer\Laravel\Actions;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteClosure;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteCollection;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteController;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteControllerAction;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteInvokableController;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteParameter;
use Spatie\TypeScriptTransformer\Laravel\Routes\RouteParameterCollection;

class ResolveLaravelRoutControllerCollectionsAction
{
    public function execute(
        ?string $defaultNamespace,
        bool $includeRouteClosures,
    ): RouteCollection {
        /** @var array<string, RouteController> $controllers */
        $controllers = [];
        /** @var array<RouteClosure> $closures */
        $closures = [];

        foreach (app(Router::class)->getRoutes()->getRoutes() as $route) {
            $controllerClass = $route->getControllerClass();

            if ($controllerClass === null && ! $includeRouteClosures) {
                continue;
            }

            if ($controllerClass === null) {
                $name = "Closure({$route->uri})";

                $closures[$name] = new RouteClosure(
                    $this->resolveRouteParameters($route),
                    $route->methods,
                    $this->resolveUrl($route),
                    $route->getName(),
                );

                continue;
            }

            if ($defaultNamespace !== null) {
                $controllerClass = Str::of($controllerClass)
                    ->trim('\\')
                    ->replace($defaultNamespace, '')
                    ->trim('\\')
                    ->toString();
            }

            $controllerClass = str_replace('\\', '.', $controllerClass);

            if ($route->getActionMethod() === $route->getControllerClass()) {
                $controllers[$controllerClass] = new RouteInvokableController(
                    $this->resolveRouteParameters($route),
                    $route->methods,
                    $this->resolveUrl($route),
                    $route->getName(),
                );

                continue;
            }

            if (! array_key_exists($controllerClass, $controllers)) {
                $controllers[$controllerClass] = new RouteController([]);
            }

            $controllers[$controllerClass]->actions[$route->getActionMethod()] = new RouteControllerAction(
                $this->resolveRouteParameters($route),
                $route->methods,
                $this->resolveUrl($route),
                $route->getName(),
            );
        }

        return new RouteCollection($controllers, $closures);
    }

    protected function resolveRouteParameters(
        Route $route
    ): RouteParameterCollection {
        preg_match_all('/\{(.*?)\}/', $route->getDomain().$route->uri, $matches);

        $parameters = array_map(fn (string $match) => new RouteParameter(
            trim($match, '?'),
            str_ends_with($match, '?')
        ), $matches[1]);

        return new RouteParameterCollection($parameters);
    }

    protected function resolveUrl(Route $route): string
    {
        return str_replace('?}', '}', $route->getDomain().$route->uri);
    }
}
