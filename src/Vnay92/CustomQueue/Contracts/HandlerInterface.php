<?php

namespace Vnay92\CustomQueue\Contracts;

interface HandlerInterface
{
    /**
     * Handler Template that is called from the Custom Queue Worker
     *
     * @param  array  $data  Data from the Queue
     * @return void
     */
    public function handler(array $data = []);
}
