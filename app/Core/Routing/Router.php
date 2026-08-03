<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container;
use App\Core\Exception\HttpNotFoundException;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * İsteği ilgili denetleyici metoduna yönlendiren basit yönlendirici.
 *
 * Denetleyiciler konteyner üzerinden çözümlenir; böylece bağımlılıkları
 * kurucu metotta enjekte edilir.
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $path, string $controller, string $action, string $name): self
    {
        $this->routes[] = new Route('GET', $path, $controller, $action, $name);

        return $this;
    }

    public function post(string $path, string $controller, string $action, string $name): self
    {
        $this->routes[] = new Route('POST', $path, $controller, $action, $name);

        return $this;
    }

    /**
     * @throws HttpNotFoundException Eşleşen rota bulunamadığında.
     */
    public function dispatch(Request $request): Response
    {
        // HEAD istekleri GET rotalarıyla eşleşir; gövde web sunucusu tarafından atılır.
        $method = $request->isMethod('HEAD') ? 'GET' : $request->method;

        foreach ($this->routes as $route) {
            $parameters = $route->match($method, $request->path);

            if ($parameters === null) {
                continue;
            }

            $controller = $this->container->get($route->controller);
            $action = $route->action;

            if (!method_exists($controller, $action)) {
                throw new HttpNotFoundException(sprintf(
                    '%s::%s bulunamadı.',
                    $route->controller,
                    $action,
                ));
            }

            /** @var Response */
            return $controller->{$action}(...$parameters);
        }

        throw new HttpNotFoundException(sprintf('Rota bulunamadı: %s', $request->path));
    }
}
