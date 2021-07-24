<?php 

namespace Coolpraz\PhpBlade;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\DynamicComponent;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class PhpBlade
{
	/**
	 * Array containing paths where to look for blade files
	 * @var array
	 */
	protected $viewPaths;

	/**
	 * Location where to store cached views
	 * @var string
	 */
	protected $cachePath;

    /**
     * Illuminate Container instance.
     * @var Container
     */
    protected $app;

    /**
     * Constructor.
     *
     * @param string|array $viewPaths
     * @param string $cachePath
     */
    public function __construct($viewPaths, $cachePath)
    {
        $this->app = new Container();
        $this->viewPaths = is_string($viewPaths) ? [$viewPaths] : $viewPaths;
        $this->cachePath = $cachePath;

        $this->register();
    }

    /**
     * @return \Illuminate\View\Factory
     */
    public function view()
    {
        return $this->app['view'];
    }

    /**
     * @return BladeCompiler
     */
    public function bladeCompiler()
    {
        return $this->app['blade.compiler'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function register()
    {
        $this->registerNativeFilesystem();
        $this->registerEvents();

        $this->registerFactory();
        $this->registerViewFinder();
        $this->registerBladeCompiler();
        $this->registerEngineResolver();
    }

    /**
     * Register the native filesystem implementation.
     *
     * @return void
     */
    protected function registerNativeFilesystem()
    {
        $this->app->singleton('files', function () {
            return new Filesystem;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function registerEvents()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    protected function registerFactory()
    {
        $this->app->singleton('view', function ($app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = $this->createFactory($resolver, $finder, $app['events']);

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer($app);

            $factory->share('app', $app);

            return $factory;
        });
    }

    /**
     * Create a new Factory Instance.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @param  \Illuminate\View\ViewFinderInterface  $finder
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return \Illuminate\View\Factory
     */
    protected function createFactory($resolver, $finder, $events)
    {
        return new Factory($resolver, $finder, $events);
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    protected function registerViewFinder()
    {
        $me = $this;
        $this->app->bind('view.finder', function ($app) use ($me) {
            return new FileViewFinder($app['files'], $me->viewPaths);
        });
    }

    /**
     * Register the Blade compiler implementation.
     *
     * @return void
     */
    protected function registerBladeCompiler()
    {
        $me = $this;
        $this->app->singleton('blade.compiler', function ($app) use ($me) {
            return tap(new BladeCompiler($app['files'], $me->cachePath), function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    protected function registerEngineResolver()
    {
        $this->app->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register'.ucfirst($engine).'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    protected function registerFileEngine($resolver)
    {
        $resolver->register('file', function () {
            return new FileEngine($this->app['files']);
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    protected function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine($this->app['files']);
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param  \Illuminate\View\Engines\EngineResolver  $resolver
     * @return void
     */
    protected function registerBladeEngine($resolver)
    {
        $resolver->register('blade', function () {
            return new CompilerEngine($this->app['blade.compiler'], $this->app['files']);
        });
    }
}