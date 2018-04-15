# Custom Queue Handler For Laravel

[![Latest Stable Version](https://poser.pugx.org/vnay92/laravel-custom-queue/v/stable?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)
[![Total Downloads](https://poser.pugx.org/vnay92/laravel-custom-queue/downloads?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)
[![Latest Unstable Version](https://poser.pugx.org/vnay92/laravel-custom-queue/v/unstable?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)
[![License](https://poser.pugx.org/vnay92/laravel-custom-queue/license?format=flat-square)](https://packagist.org/packages/vnay92/laravel-custom-queue)


Laravel custom Queue Handler is a simple implementation of the laravel-esque queue handling for all queues and messages that are not part of the Laravel Framework.

There are times when your application would want to consume messages as a JSON, sent on a Queue that does not implement the Laravel Job.

This package aims to solve that use case.

Currently Supported Queues:
- RabbitMQ


## Installation

### Laravel 5.x

Install the ``vnay92/laravel-custom-queue`` package:

```bash
$ composer require vnay92/laravel-custom-queue
```

### Laravel Version Compatibility

- Laravel `4.x` is not supported.
- Laravel `5.x.x` is supported from `5.1` in the respective branch.


If you're on Laravel 5.4 or earlier, you'll need to add the following to your ``config/app.php``:

```php
'providers' => array(
    // ...
    Vnay92\CustomQueue\CustomQueueServiceProvider::class,
)

```

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

## Testing with Artisan

You can test your configuration using the provided ``artisan`` command:

```bash
$ php artisan custom-queue:test
```


## Contributing

Dependencies are managed through composer:

```
$ composer install
```

Tests can then be run via phpunit:

```
$ vendor/bin/phpunit
```


## Community

* [Bug Tracker](http://github.com/vnay92/laravel-custom-queue/issues)
* [Code](http://github.com/vnay92/laravel-custom-queue)
