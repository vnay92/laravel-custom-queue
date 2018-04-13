<?php

namespace Vnay92\CustomQueue;

use Illuminate\Support\ServiceProvider;

// Commands
use Vnay92\CustomQueue\Console\WorkCommand;
use Vnay92\CustomQueue\Console\ListenCommand;
use Vnay92\CustomQueue\Console\RestartCommand;

use Vnay92\CustomQueue\Connectors\RabbitMQConnector;

use Vnay92\CustomQueue\Failed\NullFailedJobProvider;

/**
 * @see https://github.com/laravel/framework/blob/5.6/src/Illuminate/Queue/QueueServiceProvider.php
 */
class CustomQueueServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerManager();

        $this->registerWorker();

        $this->registerListener();

        $this->registerFailedJobServices();
    }

    /**
     * Register the queue manager.
     * and also register the Queue Connection
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('custom.queue', function ($app) {
            // Once we have an instance of the queue manager, we will register the various
            // resolvers for the queue connectors. These connectors are responsible for
            // creating the classes that accept queue configs and instantiate queues.
            $manager = new CustomQueueManager($app);

            $this->registerConnectors($manager);

            return $manager;
        });

        $this->app->singleton('custom.queue.connection', function ($app) {
            return $app['custom.queue']->connection();
        });
    }

    /**
     * Register the queue worker,
     * also register Work Command,
     * and the restart Command..
     *
     * @return void
     */
    protected function registerWorker()
    {
        $this->registerWorkCommand();

        $this->registerRestartCommand();

        $this->app->singleton('custom.queue.worker', function ($app) {
            return new Worker($app['custom.queue'], $app['custom.queue.failer'], $app['events']);
        });
    }

    /**
     * Register the queue worker console command.
     *
     * @return void
     */
    protected function registerWorkCommand()
    {
        $this->app->singleton('command.custom.queue.work', function ($app) {
            return new WorkCommand($app['custom.queue.worker']);
        });

        $this->commands('command.custom.queue.work');
    }

    /**
     * Register the queue listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->registerListenCommand();

        $this->app->singleton('custom.queue.listener', function ($app) {
            return new Listener($app->basePath());
        });
    }

    /**
     * Register the queue listener console command.
     *
     * @return void
     */
    protected function registerListenCommand()
    {
        $this->app->singleton('command.custom.queue.listen', function ($app) {
            return new ListenCommand($app['custom.queue.listener']);
        });

        $this->commands('command.custom.queue.listen');
    }

    /**
     * Register the queue restart console command.
     *
     * @return void
     */
    public function registerRestartCommand()
    {
        $this->app->singleton('command.custom.queue.restart', function () {
            return new RestartCommand;
        });

        $this->commands('command.custom.queue.restart');
    }

    /**
     * Register the connectors on the queue manager.
     *
     * @param  Vnay92\CustomQueue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        foreach (['Rabbitmq'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the Null queue connector.
     *
     * @param  Vnay92\CustomQueue\QueueManager  $manager
     * @return void
     */
    protected function registerRabbitmqConnector($manager)
    {
        $manager->addConnector('rabbitmq', function () {
            return new RabbitMQConnector;
        });
    }

    /**
     * Register the failed job services.
     *
     * @return void
     */
    protected function registerFailedJobServices()
    {
        $this->app->singleton('custom.queue.failer', function ($app) {
            $config = $app['config']['custom-queue.failed'];

            // TODO: Add Database Failed Job Provider
            return new NullFailedJobProvider;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'custom.queue',
            'queue.custom.worker',
            'queue.custom.listener',
            'queue.custom.failer',
            'command.custom.queue.work',
            'command.custom.queue.listen',
            'command.custom.queue.restart',
            'command.custom.queue.subscribe',
            'custom.queue.connection',
        ];
    }

}
