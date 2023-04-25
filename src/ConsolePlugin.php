<?php

namespace Sicet7\Console;

use Psr\Container\ContainerInterface;
use Sicet7\Plugin\Container\Interfaces\PluginInterface;
use Sicet7\Plugin\Container\MutableDefinitionSourceHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

readonly class ConsolePlugin implements PluginInterface
{
    /**
     * @param string[] $commandMap
     */
    public function __construct(private array $commandMap = [])
    {
        array_map(fn(string $key) => $key, array_keys($this->commandMap));
        array_map(fn(string $value) => $value, $this->commandMap);
    }

    /**
     * @param MutableDefinitionSourceHelper $source
     * @return void
     */
    public function register(MutableDefinitionSourceHelper $source): void
    {
        $commandMap = $this->commandMap;
        $source->factory(ContainerCommandLoader::class, function (
            ContainerInterface $container
        ) use ($commandMap) : ContainerCommandLoader {
            return new ContainerCommandLoader($container, $commandMap);
        });
        $source->reference(CommandLoaderInterface::class, ContainerCommandLoader::class);
        $source->factory(Application::class, function (
            ContainerInterface $container,
            CommandLoaderInterface $commandLoader
        ): Application {
            $appName = 'UNKNOWN';
            $appVersion = 'UNKNOWN';
            if ($container->has('app.name') && is_string($container->get('app.name'))) {
                $appName = $container->get('app.name');
            }
            if ($container->has('app.version') && is_string($container->get('app.version'))) {
                $appVersion = $container->get('app.version');
            }
            $app = new Application($appName, $appVersion);
            $app->setCommandLoader($commandLoader);
            if ($container->has(EventDispatcherInterface::class)) {
                $app->setDispatcher($container->get(EventDispatcherInterface::class));
            }
            return $app;
        });
    }
}