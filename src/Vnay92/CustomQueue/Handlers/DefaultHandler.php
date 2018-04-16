<?php

namespace Vnay92\CustomQueue\Handlers;

use Vnay92\CustomQueue\Contracts\HandlerInterface;

class DefaultHsndler implements HandlerInterface
{
    /**
     * The defauld Handler that just logs and returns.
     *
     * @param  array  $data  Message from the Default Logger
     * @return void
     */
    public function handle(array $data = [])
    {
        \Log::info('[CUSTOM_QUEUE_SERVICE_HANDLER] Recieved a message', $data);
    }
}
