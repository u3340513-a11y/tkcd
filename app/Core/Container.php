<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\ContainerException;
use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Yansıma (reflection) tabanlı bağımlılık enjeksiyon konteyneri.
 *
 * Neden: Controller ve servisler bağımlılıklarını kurucu metotta arayüz
 * olarak talep eder; somut sınıflar tek noktadan bağlanır. Bu sayede admin
 * panel fazında dosya tabanlı repository'ler PDO tabanlılarla değiştirildiğinde
 * uygulama kodunda değişiklik gerekmez (Dependency Inversion).
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /**
     * Arayüzü somut sınıfa bağlar.
     */
    public function bind(string $abstract, string $concrete): void
    {
        $this->aliases[$abstract] = $concrete;
    }

    /**
     * Nesnenin nasıl üretileceğini elle tanımlar (tekil olarak saklanır).
     */
    public function factory(string $abstract, Closure $factory): void
    {
        $this->factories[$abstract] = $factory;
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return T|object
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->factories[$id])) {
            return $this->instances[$id] = ($this->factories[$id])($this);
        }

        $concrete = $this->aliases[$id] ?? $id;

        if (!class_exists($concrete)) {
            throw new ContainerException(sprintf('Çözümlenemeyen bağımlılık: %s', $id));
        }

        return $this->instances[$id] = $this->build($concrete);
    }

    private function build(string $concrete): object
    {
        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(sprintf('Örneklenemeyen sınıf: %s', $concrete));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($parameter, $concrete),
            $constructor->getParameters(),
        );

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(ReflectionParameter $parameter, string $owner): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->get($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(sprintf(
            '%s sınıfının "%s" parametresi çözümlenemedi.',
            $owner,
            $parameter->getName(),
        ));
    }
}
