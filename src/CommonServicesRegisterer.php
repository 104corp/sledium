<?php


namespace Sledium;

class CommonServicesRegisterer
{
    protected $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function register()
    {
        $this->registerCommonDeferredProviders();
        $this->registerCommonProviders();
        $this->registerCommonAliases();
    }

    protected function registerCommonDeferredProviders()
    {
        foreach ([   // service => provider
                     'cache' => 'Illuminate\Cache\CacheServiceProvider',
                     'cache.store' => 'Illuminate\Cache\CacheServiceProvider',
                     'encrypter' => 'Illuminate\Encryption\EncryptionServiceProvider',
                     'db' => 'Illuminate\Database\DatabaseServiceProvider',
                     'db.connection' => 'Illuminate\Database\DatabaseServiceProvider',
                     'events' => 'Illuminate\Events\EventServiceProvider',
                     'files' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'filesystem' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'filesystem.disk' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'filesystem.cloud' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'hash' => 'Illuminate\Hashing\HashServiceProvider',
                     'log' => 'Sledium\ServiceProviders\LogServiceProvider',
                     'mailer' => 'Illuminate\Mail\MailServiceProvider',
                     'queue' => 'Illuminate\Queue\QueueServiceProvider',
                     'queue.connection' => 'Illuminate\Queue\QueueServiceProvider',
                     'queue.failer' => 'Illuminate\Queue\QueueServiceProvider',
                     'queue.listener' => 'Illuminate\Queue\QueueServiceProvider',
                     'redis' => 'Illuminate\Redis\RedisServiceProvider',
                 ] as $service => $provider) {
            $this->container->registerDeferredProvider($provider, $service);
        }
    }

    protected function registerCommonProviders()
    {
        foreach ([
            //  provider
                 ] as $provider) {
            $this->container->register($provider);
        }
    }

    protected function registerCommonAliases()
    {
        foreach ([   // service => [alias, alias ......]
                     'app' => [
                         'Sledium\Container',
                         'Illuminate\Contracts\Container\Container',
                         'Illuminate\Contracts\Foundation\Application',
                         'Psr\Container\ContainerInterface'
                     ],
                     'cache' => [
                         'Illuminate\Cache\CacheManager',
                         'Illuminate\Contracts\Cache\Factory'
                     ],
                     'cache.store' => [
                         'Illuminate\Cache\Repository',
                         'Illuminate\Contracts\Cache\Repository'
                     ],
                     'config' => [
                         'Illuminate\Config\Repository',
                         'Sledium\Config',
                     ],
                     'encrypter' => [
                         'Illuminate\Encryption\Encrypter',
                         'Illuminate\Contracts\Encryption\Encrypter',
                     ],
                     'db' => [
                         'Illuminate\Database\DatabaseManager'
                     ],
                     'db.connection' => [
                         'Illuminate\Database\Connection',
                         'Illuminate\Database\ConnectionInterface'
                     ],
                     'events' => [
                         'Illuminate\Events\Dispatcher',
                         'Illuminate\Contracts\Events\Dispatcher'
                     ],
                     'files' => [
                         'Illuminate\Filesystem\Filesystem'
                     ],
                     'filesystem' => [
                         'Illuminate\Filesystem\FilesystemManager',
                         'Illuminate\Contracts\Filesystem\Factory'
                     ],
                     'filesystem.disk' => [
                         'Illuminate\Contracts\Filesystem\Filesystem'
                     ],
                     'filesystem.cloud' => [
                         'Illuminate\Contracts\Filesystem\Cloud'
                     ],
                     'hash' => [
                         'Illuminate\Contracts\Hashing\Hasher'
                     ],

                     'log' => [
                         'Illuminate\Log\Writer',
                         'Illuminate\Contracts\Logging\Log',
                         'Psr\Log\LoggerInterface'
                     ],
                     'mailer' => [
                         'Illuminate\Mail\Mailer',
                         'Illuminate\Contracts\Mail\Mailer',
                         'Illuminate\Contracts\Mail\MailQueue'
                     ],
                     'queue' => [
                         'Illuminate\Queue\QueueManager',
                         'Illuminate\Contracts\Queue\Factory',
                         'Illuminate\Contracts\Queue\Monitor'
                     ],
                     'queue.connection' => [
                         'Illuminate\Contracts\Queue\Queue'
                     ],
                     'queue.failer' => [
                         'Illuminate\Queue\Failed\FailedJobProviderInterface'
                     ],
                     'redis' => [
                         'Illuminate\Redis\RedisManager',
                         'Illuminate\Contracts\Redis\Factory'
                     ],

                 ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->container->alias($key, $alias);
            }
        }
    }
}
