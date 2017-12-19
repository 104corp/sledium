<?php


namespace Sledium;

use Closure;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Foundation\Application as IlluminateApplication;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * ArrayAccess setter default singleton and implements Illuminate Application
 * Class Container
 * @package Sledium
 */
class Container extends IlluminateContainer implements IlluminateApplication
{
    /** @var string project base path */
    protected $basePath;
    /** @var  string */
    protected $appPath;
    /** @var  string */
    protected $bootstrapPath;
    /** @var  string */
    protected $configPath;
    /** @var  string */
    protected $databasePath;
    /** @var  string */
    protected $publicPath;
    /** @var  string */
    protected $storagePath;
    /** @var  string */
    protected $resourcePath;
    /** @var  string */
    protected $dependencePath;

    /** @var bool */
    protected $isBooted = false;
    /** @var \Closure[] */
    protected $bootingCallbacks = [];
    /** @var \Closure[] */
    protected $bootedCallbacks = [];
    /** @var  ServiceProvider[] */
    protected $loadedProviders = [];
    /** @var string[] */
    protected $deferredProviders = [];
    /** @var  string */
    protected $environment;
    /** @var  bool */
    protected $isRunningInConsole;
    private $namespace;

    /**
     * Container constructor.
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->init();
    }

    /**
     * Get the base path of the project.
     *
     * @return string
     */
    public function basePath(): string
    {
        return $this->basePath;
    }


    /**
     * Get the path to the application "app" directory.
     *
     * @param string $path To append to the app path
     * @return string
     */
    public function path($path = ''): string
    {
        if (null === $this->appPath) {
            $this->appPath = $this->basePath() . DIRECTORY_SEPARATOR . 'app';
        }
        return $this->appPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }


    /**
     * Get the path to the bootstrap directory.
     *
     * @param string $path To append to the bootstrap path
     * @return string
     */
    public function bootstrapPath($path = ''): string
    {
        if (null === $this->bootstrapPath) {
            $this->bootstrapPath = $this->basePath() . DIRECTORY_SEPARATOR . 'bootstrap';
        }
        return $this->bootstrapPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param string $path To append to the config path
     * @return string
     */
    public function configPath($path = ''): string
    {
        if (null === $this->configPath) {
            $this->configPath = $this->basePath() . DIRECTORY_SEPARATOR . 'config';
        }
        return $this->configPath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the database directory.
     *
     * @param string $path To append to the database path
     * @return string
     */
    public function databasePath($path = ''): string
    {
        if (null === $this->databasePath) {
            $this->databasePath = $this->basePath() . DIRECTORY_SEPARATOR . 'database';
        }
        return $this->databasePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }


    /**
     * Get the path to the public / web root directory.
     *
     * @return string
     */
    public function publicPath(): string
    {
        if (null === $this->publicPath) {
            $this->publicPath = $this->basePath() . DIRECTORY_SEPARATOR . 'public';
        }
        return $this->publicPath;
    }

    /**
     * Get the path to the storage directory.
     *
     * @return string
     */
    public function storagePath(): string
    {
        if (null === $this->storagePath) {
            $this->storagePath = $this->basePath() . DIRECTORY_SEPARATOR . 'storage';
        }
        return $this->storagePath;
    }

    /**
     * Get the path to the resources directory.
     *
     * @param  string $path
     * @return string
     */
    public function resourcePath($path = ''): string
    {
        if (null === $this->resourcePath) {
            $this->resourcePath = $this->basePath() . DIRECTORY_SEPARATOR . 'resources';
        }
        return $this->resourcePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Get the path to the dependencies directory.
     *
     * @return string
     */
    public function dependencePath(): string
    {
        if (null === $this->dependencePath) {
            $this->dependencePath = $this->basePath() . DIRECTORY_SEPARATOR . 'dependencies';
        }
        return $this->dependencePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param string $appPath
     */
    public function setAppPath(string $appPath)
    {
        $this->appPath = $appPath;
    }

    /**
     * @param string $bootstrapPath
     */
    public function setBootstrapPath(string $bootstrapPath)
    {
        $this->bootstrapPath = $bootstrapPath;
    }

    /**
     * @param string $configPath
     */
    public function setConfigPath(string $configPath)
    {
        $this->configPath = $configPath;
    }

    /**
     * @param string $databasePath
     */
    public function setDatabasePath(string $databasePath)
    {
        $this->databasePath = $databasePath;
    }

    /**
     * @param string $publicPath
     */
    public function setPublicPath(string $publicPath)
    {
        $this->publicPath = $publicPath;
    }

    /**
     * @param string $storagePath
     */
    public function setStoragePath(string $storagePath)
    {
        $this->storagePath = $storagePath;
    }

    /**
     * @param string $resourcePath
     */
    public function setResourcePath(string $resourcePath)
    {
        $this->resourcePath = $resourcePath;
    }

    /**
     * @param string $dependencePath
     */
    public function setDependencePath(string $dependencePath)
    {
        $this->dependencePath = $dependencePath;
    }



    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath(): string
    {
        // Not use it yet
        return '';
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath(): string
    {
        // Not use it yet
        return '';
    }

    /**
     * Active Illuminate Facades
     */
    public function activeIlluminateFacades()
    {
        Facade::setFacadeApplication($this);
    }

    /**
     * @param $abstract
     * @return bool
     */
    public function resolveInDependenciesPath($abstract): bool
    {
        if (is_string($abstract)
            && ($realPath = realpath($this->dependencePath() . DIRECTORY_SEPARATOR . $abstract . '.php'))
        ) {
            $callBack = include $realPath;
            if ($callBack instanceof Closure) {
                $this[$abstract] = $callBack;
                return true;
            }
        }
        return false;
    }

    /**
     * let default singleton
     * @param string $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->singleton($key, $value);
    }

    /**
     * @param string $key
     */
    public function offsetUnset($key)
    {
        parent::offsetUnset($key);
        unset($this->deferredProviders[$key]);
    }

    /**
     * @param string $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return isset($this->deferredProviders[$abstract]) || parent::bound($abstract)
            ? true : $this->resolveInDependenciesPath($abstract);
    }

    /**
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function resolve($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);
        if (isset($this->deferredProviders[$abstract]) && !isset($this->instances[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }
        if (!parent::bound($abstract)) {
            $this->resolveInDependenciesPath($abstract);
        }
        return parent::resolve($abstract, $parameters);
    }


    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version(): string
    {
        return '1.0';
    }

    /**
     * Get or check the current application environment.
     *
     * @return string
     */
    public function environment(): string
    {
        return $this->environment ?? 'development';
    }

    /**
     * @param string $environment
     */
    public function setEnvironment(string $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole(): bool
    {
        return (bool)$this->isRunningInConsole ??  php_sapi_name() == 'cli' || php_sapi_name() == 'phpdbg';
    }

    /**
     * @param bool $isRunningInConsole
     */
    public function setIsRunningInConsole(bool $isRunningInConsole)
    {
        $this->isRunningInConsole = $isRunningInConsole;
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance(): bool
    {
        return file_exists($this->storagePath() . '/framework/down');
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        /** @var Config $config */
        if (null == ($config = $this['config'] ?? null)) {
            throw new \RuntimeException('Missing "config" has not registered yet');
        }

        $deferredProviders = $config->get('deferred-providers', []);
        foreach ($deferredProviders as $service => $provider) {
            $this->registerDeferredProvider($provider, $service);
        }

        $providers = $config->get('providers', []);
        foreach ($providers as $provider) {
            $this->register($provider);
        }

        $aliases = $config->get('aliases', []);
        foreach ($aliases as $alias => $service) {
            $this->alias($service, $alias);
        }
    }


    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param  array $options
     * @param  bool $force
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = [], $force = false)
    {
        if (($registered = $this->getProvider($provider)) && !$force) {
            return $registered;
        }
        if (is_string($provider)) {
            $provider = new $provider($this);
        }
        if (method_exists($provider, 'register')) {
            $provider->register();
        }
        $this->markAsRegistered($provider);

        if ($this->isBooted) {
            $this->bootProvider($provider);
        }
        return $provider;
    }

    /**
     * @param $provider
     * @return ServiceProvider|null
     */
    public function getProvider($provider)
    {
        if (!is_string($provider)) {
            $provider = get_class($provider);
        }
        return $this->loadedProviders[$provider] ?? null;
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string $provider
     * @param  string|null $service
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        if ($service) {
            $this->addDeferredProvider($provider, $service);
        }
    }

    /**
     * @param string $provider
     * @param string $service
     */
    protected function addDeferredProvider(string $provider, string $service)
    {
        $this->deferredProviders[$service] = $provider;
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string $service
     * @return void
     */
    public function loadDeferredProvider($service)
    {
        if (false === ($provider = ($this->deferredProviders[$service] ?? false))) {
            return;
        }
        if (!$this->isLoadedProvider($provider)) {
            $this->register($instance = new $provider($this));
            if (!$this->isBooted) {
                $this->booting(function () use ($instance) {
                    $this->bootProvider($instance);
                });
            }
        }
    }

    /**
     * @param string $provider
     * @return bool
     */
    public function isLoadedProvider(string $provider)
    {
        return isset($this->loadedProviders[$provider]);
    }

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isBooted) {
            return;
        }
        $this->fireAppBootCallbacks(true);
        foreach ($this->loadedProviders as $provider) {
            $this->bootProvider($provider);
        }
        $this->isBooted = true;
        $this->fireAppBootCallbacks(false);
    }

    /**
     * @param callable $callback
     */
    public function booting($callback)
    {
        $this->addBootCallbacks($callback, true);
    }

    /**
     * Register a new "booted" listener.
     *
     * @param  mixed $callback
     * @return void
     */
    public function booted($callback)
    {
        $this->addBootCallbacks($callback, false);
    }

    /**
     * Flush container
     */
    public function flush()
    {
        parent::flush();
        $this->bootedCallbacks = [];
        $this->bootingCallbacks = [];
        $this->loadedProviders = [];
        $this->deferredProviders = [];
        $this->init();
    }

    /**
     * Get project app namespace
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->guessNamespace();
    }

    /**
     * @return string
     */
    protected function guessNamespace():string
    {
        if ($this->namespace !== null) {
            return $this->namespace;
        }
        if ($composerJson = realpath($this->basePath().'/composer.json')) {
            $composerInfo = json_decode(file_get_contents($composerJson), true);
            $psr4Namespaces = (array)$composerInfo['autoload']['psr-4'] ?? [];
            foreach ($psr4Namespaces as $namespace => $paths) {
                foreach ((array)$paths as $path) {
                    if (realpath($this->path()) == realpath($this->basePath().'/'.$path)) {
                        return $this->namespace = $namespace;
                    }
                }
            }
        }
        throw new \RuntimeException('Unable to guss namespace.');
    }

    /**
     * @param bool $isBooting
     */
    protected function fireAppBootCallbacks(bool $isBooting = true)
    {
        foreach (($isBooting ? $this->bootingCallbacks : $this->bootedCallbacks) as $callback) {
            call_user_func($callback, $this);
        }
    }

    /**
     * @param callable $callback
     * @param bool $isBooting
     */
    protected function addBootCallbacks(callable $callback, bool $isBooting = true)
    {
        if ($isBooting) {
            $this->bootingCallbacks[] = $callback;
        } else {
            $this->bootedCallbacks[] = $callback;
        }
    }


    /**
     * @param ServiceProvider $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * @param ServiceProvider $serviceProvider
     */
    protected function markAsRegistered(ServiceProvider $serviceProvider)
    {
        $this->loadedProviders[get_class($serviceProvider)] = $serviceProvider;
    }

    protected function init()
    {
        $this->registerBaseBindings();
    }

    /**
     * Register the basic bindings
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(PsrContainerInterface::class, $this);
        $this->instance(IlluminateApplication::class, $this);
    }
}
