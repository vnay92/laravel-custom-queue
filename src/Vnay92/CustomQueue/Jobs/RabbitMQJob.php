<?php
/*
    Copyright 2018 Vinay Bharadwaj

    Permission is hereby granted, free of charge, to any person obtaining a copy of this software
    and associated documentation files (the "Software"), to deal in the Software without restriction,
    including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
    subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
    INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
    WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
    OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Vnay92\CustomQueue\Jobs;

use Illuminate\Queue\Jobs\Job;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

use Vnay92\CustomQueue\RabbitMQQueue;

class RabbitMQJob extends Job implements JobContract
{
    protected $queue;
    protected $message;
    protected $channel;
    protected $container;
    protected $connection;

    public function __construct(
        Container $container,
        RabbitMQQueue $connection,
        AMQPChannel $channel,
        $queue,
        AMQPMessage $message
    ) {
        $this->queue = $queue;
        $this->channel = $channel;
        $this->message = $message;
        $this->container = $container;
        $this->connection = $connection;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $payload = json_decode($this->message->body, true);
        $this->resolveAndFire();
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->message->body;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        parent::delete();

        $this->channel->basic_ack($this->message->delivery_info['delivery_tag']);
    }

    /**
     * Get queue name
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int $delay
     *
     * @return void
     */
    public function release($delay = 0)
    {
        $this->delete();

        $body = json_decode($this->message->body, true);

        if(!is_array($body)) {
            return;
        }

        $attempts = $this->attempts();

        // write attempts to body
        $body['custom_meta_data']['attempts'] = $attempts + 1;

        $data = $this->constructCustomMessage($body);

        if ($delay > 0) {
            $this->connection->later($delay, null, $data, $this->getQueue());
        } else {
            $this->connection->push(null, $data, $this->getQueue());
        }
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        $body = json_decode($this->message->body, true);

        return isset($body['custom_meta_data']['attempts']) ? (int) $body['custom_meta_data']['attempts'] : 0;
    }

    /**
     * Json Decode the Message and return the body
     *
     * @return array $message
     */
    public function toArray()
    {
        return array_except(json_decode($this->message->body, true), 'custom_meta_data');
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId()
    {
        return $this->message->get('correlation_id');
    }

    /**
     * Get the Body as a String to be pushed to RabbitMQ
     *
     * @param  array  $body     Data to be pushed
     * @return string $data     json_encoded-ed $body
     */
    public function constructCustomMessage(array $body)
    {
        return json_encode($body);
    }
}
