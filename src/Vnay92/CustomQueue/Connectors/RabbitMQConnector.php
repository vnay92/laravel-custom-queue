<?php

namespace Vnay92\CustomQueue\Connectors;

use Vnay92\CustomQueue\RabbitMQQueue;

use PhpAmqpLib\Connection\AMQPConnection;
use Illuminate\Queue\Connectors\ConnectorInterface;

class RabbitMQConnector implements ConnectorInterface
{
    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * Establish a queue connection.
     *
     * @param  array $config
     *
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        // create connection with AMQP
        $this->connection = new AMQPConnection($config['host'], $config['port'], $config['login'], $config['password'], $config['vhost']);

        return new RabbitMQQueue(
            $this->connection,
            $config
        );
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
