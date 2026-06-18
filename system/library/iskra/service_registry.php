<?php
/**
 * iskra-core — Сервисный реестр (Service Registry)
 *
 * Предоставляет единое место для регистрации и получения сервисов расширений.
 * Расширения Искра могут регистрировать свои сервисы, и другие расширения
 * могут к ним обращаться.
 *
 * Версия: 0.1.0
 */

declare(strict_types=1);

namespace Opencart\Extension\IskraCore\System\Library;

use Opencart\System\Engine\Registry as OpenCartRegistry;

final class ServiceRegistry
{
    private OpenCartRegistry $registry;
    /** @var array<string, array<string, mixed>> Карта сервисов [name => [instance, options]] */
    private array $services = [];

    public function __construct(OpenCartRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Зарегистрировать сервис
     *
     * @param string $name Имя сервиса (например, 'iskra_analytic')
     * @param object $instance Экземпляр сервиса
     * @param array<string, mixed> $options Опции (singleton, lazy и т.д.)
     */
    public function register(string $name, object $instance, array $options = []): void
    {
        if (isset($this->services[$name])) {
            throw new \RuntimeException("Service '$name' is already registered");
        }

        $this->services[$name] = [
            'instance' => $instance,
            'options' => array_merge([
                'singleton' => true,
            ], $options),
        ];

        // Регистрируем в OpenCart Registry для доступа через $registry->get()
        $this->registry->set($name, $instance);
    }

    /**
     * Получить зарегистрированный сервис
     *
     * @template T
     * @param class-string<T>|string $name Имя сервиса или имя класса
     * @return T|mixed
     */
    public function get(string $name): mixed
    {
        if (isset($this->services[$name])) {
            return $this->services[$name]['instance'];
        }

        // Попробуем получить из стандартного реестра OpenCart
        $service = $this->registry->get($name);
        if ($service !== null) {
            return $service;
        }

        throw new \RuntimeException("Service '$name' not registered");
    }

    /**
     * Проверить, зарегистрирован ли сервис
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || $this->registry->has($name);
    }

    /**
     * Удалить сервис
     */
    public function unregister(string $name): void
    {
        unset($this->services[$name]);
    }

    /**
     * Получить список всех зарегистрированных сервисов
     *
     * @return array<string>
     */
    public function list(): array
    {
        return array_keys($this->services);
    }
}
