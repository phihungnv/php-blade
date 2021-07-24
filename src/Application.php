<?php

namespace Coolpraz\PhpBlade;

use Illuminate\Container\Container;

class Application extends Container
{
    /**
     * The application namespace.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Create a new Illuminate application instance.
     * @return void
     */
    public function __construct()
    {
        $this->instance('app', $this);

        $this->registerCoreContainerAliases();
    }

    /**
     * Get the application namespace.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Set the application namespace.
     *
     * @param string $namespace
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
        foreach ([
             'app' => [\Illuminate\Contracts\Container\Container::class, \Illuminate\Contracts\Foundation\Application::class, \Psr\Container\ContainerInterface::class],
             'view' => [\Illuminate\View\Factory::class, \Illuminate\Contracts\View\Factory::class]
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }
}