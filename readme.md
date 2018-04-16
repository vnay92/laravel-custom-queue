# Custom Queue Handler For Laravel

[![Latest Stable Version](https://poser.pugx.org/vnay92/laravel-custom-queue/v/stable?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)
[![Total Downloads](https://poser.pugx.org/vnay92/laravel-custom-queue/downloads?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)
[![Latest Unstable Version](https://poser.pugx.org/vnay92/laravel-custom-queue/v/unstable?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)
[![License](https://poser.pugx.org/vnay92/laravel-custom-queue/license?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)


Laravel custom Queue Handler is a simple implementation of the laravel-esque queue handling for all queues and messages that are not part of the Laravel Framework.

There are times when your application would want to consume messages as a JSON, sent on a Queue that does not implement the Laravel Job.

This package aims to solve that use case.

Supports the same commands as Laravel, with the same parameters, with one additional Parameter:
- `custom-queue:work --handler="Class\Path\To\Handler"`

- `custom-queue:listen --handler="Class\Path\To\Handler"`

- `custom-queue:restart`

Currently Supported Queues:
- RabbitMQ

### Laravel Version Compatibility

- Laravel `4.x` is not supported.
- Laravel `5.x.x` is supported from `5.1` in the respective branch.


## Installation

### Laravel 5.x

Install the ``vnay92/laravel-custom-queue`` package:

```bash
$ composer require vnay92/laravel-custom-queue
```

You'll need to add the following to your ``config/app.php``:

```php
'providers' => array(
    // ...
    Vnay92\CustomQueue\CustomQueueServiceProvider::class,
)

```

### Configuration

Create the Custom Queue configuration file (``config/custom-queue.php``):

```bash
$ php artisan vendor:publish --provider="Vnay92\CustomQueue\CustomQueueServiceProvider"
```

And add these properties to `.env` with proper values:

    CUSTOM_QUEUE_DRIVER=rabbitmq

    RABBITMQ_HOST=127.0.0.1
    RABBITMQ_PORT=5672
    RABBITMQ_VHOST=/
    RABBITMQ_LOGIN=guest
    RABBITMQ_PASSWORD=guest
    RABBITMQ_QUEUE=queue_name

The above commands and configurations are used as follows:


```php

/* path/to/project/config/custom-queue.php */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | The API, based on the Laravel queue API supports a variety of back-ends via an unified
    | API, giving you convenient access to each back-end using the same
    | syntax for each one. Here you may set the default queue driver.
    |
    | Supported: "rabbitmq"
    |
    */

    'default' => env('CUSTOM_QUEUE_DRIVER', 'rabbitmq'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    */

    'connections' => [
        'rabbitmq' => [
            'driver'                => 'rabbitmq',
            'host'                  => env('RABBITMQ_HOST', 'localhost'),
            'port'                  => env('RABBITMQ_PORT', 5672),
            'vhost'                 => env('RABBITMQ_VHOST', '/'),
            'login'                 => env('RABBITMQ_LOGIN', 'guest'),
            'password'              => env('RABBITMQ_PASSWORD', 'guest'),
            'queue'                 => env('RABBITMQ_QUEUE', 'custom_default'), // name of the default queue,
            'exchange_declare'      => env('RABBITMQ_EXCHANGE_DECLARE', true), // create the exchange if not exists
            'queue_declare_bind'    => env('RABBITMQ_QUEUE_DECLARE_BIND', true), // create the queue if not exists and bind to the exchange
            'queue_params'          => [
                'passive'           => env('RABBITMQ_QUEUE_PASSIVE', false),
                'durable'           => env('RABBITMQ_QUEUE_DURABLE', true),
                'exclusive'         => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                'auto_delete'       => env('RABBITMQ_QUEUE_AUTODELETE', false),
            ],
            'exchange_params'       => [
                'name'              => env('RABBITMQ_EXCHANGE_NAME', null),
                'type'              => env('RABBITMQ_EXCHANGE_TYPE', 'direct'), // more info at http://www.rabbitmq.com/tutorials/amqp-concepts.html
                'passive'           => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                'durable'           => env('RABBITMQ_EXCHANGE_DURABLE', true), // the exchange will survive server restarts
                'auto_delete'       => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => 'mysql', 'table' => 'failed_jobs',
    ],

];

```

## Example

Create a handler class which implements the `Vnay92\Contracts\HandlerInterface` and implement the `handle()` method.

```php
<?php

namespace App\Services\ExternalMessagesService;

use App\Utility\UserAuth;

/**
 * Class ExternalMessagesService
 *
 * @package App\Services\ExternalMessagesService
 */
class ExternalMessagesService
{
    /**
    * @var array
    */
    private $serviceTableMap = [];

    /**
     * @var UserAuth
     */
    private $userAuth;

    /**
     * ExternalMessagesService constructor.
     *
     * @param UserAuth $userAuth
     */
    public function __construct(UserAuth $userAuth)
    {
        $this->userAuth = $userAuth;

        $this->serviceTableMap = [
            'mail_service' => \App::make(StoreService::class),
        ];
    }

    /**
     * Handle External Service messages.
     *
     * @param array $message
     *
     * @return void
     * @throws \Exception
     */
    public function handle(array $message)
    {
        $source = array_get($message, 'source');
        if (!isset($this->serviceTableMap[$source])) {
            \Log::error('[EXTERNAL_SERVICE] Event Not Recognised: ', [$source]);
            return;
        }

        // set seller context
        if ($this->userAuth->login(null, $message['userId'])) {
            // Handle the data in the respective class.
            return $this->serviceTableMap[$message['source']]->handle($message);
        }

        return;
    }
}
```

Then start listening to the queues as follows:

``$ php artisan custom-queue:work --handler="App\Services\ExternalMessagesService\ExternalMessagesService" --queue=custom_queue``

## Contributing

Dependencies are managed through composer:

```
$ composer install
```

## TODOs
- Provide many Queues Connector
- Tests using PHPUnit
- DocBlokr for all methods and members
- Provide support for each version of Laravel.


## Community

* [Bug Tracker](http://github.com/vnay92/laravel-custom-queue/issues)
* [Code](http://github.com/vnay92/laravel-custom-queue)
