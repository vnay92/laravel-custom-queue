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


namespace Vnay92\CustomQueue\Console;

use Vnay92\CustomQueue\Worker;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\Job;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class WorkCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'custom-queue:work';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the next job on a queue';

    /**
     * The default Handler, if none is specified
     * @var string
     */
    protected $defaultHandler = 'Vnay92\CustomQueue\Handlers\DefaultHandler';

    /**
     * The queue worker instance.
     *
     * @var Vnay92\CustomQueue\Worker
     */
    protected $worker;

    /**
     * Create a new queue listen command.
     *
     * @param  Vnay92\CustomQueue\Worker  $worker
     * @return void
     */
    public function __construct(Worker $worker)
    {
        parent::__construct();

        $this->worker = $worker;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if ($this->downForMaintenance() && ! $this->option('daemon')) {
            return $this->worker->sleep($this->option('sleep'));
        }

        $queue = $this->option('queue');

        $delay = $this->option('delay');

        // The memory limit is the amount of memory we will allow the script to occupy
        // before killing it and letting a process manager restart it for us, which
        // is to protect us against any memory leaks that will be in the scripts.
        $memory = $this->option('memory');

        $connection = $this->argument('connection');

        $handler = $this->option('handler') ?: $this->defaultHandler;

        $response = $this->runWorker(
            $connection,
            $queue,
            $handler,
            $delay,
            $memory,
            $this->option('daemon')
        );

        // If a job was fired by the worker, we'll write the output out to the console
        // so that the developer can watch live while the queue runs in the console
        // window, which will also of get logged if stdout is logged out to disk.
        if (! is_null($response['job'])) {
            $this->writeOutput($response['job'], $response['failed']);
        }
    }

    /**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $delay
     * @param  int  $memory
     * @param  bool  $daemon
     * @return array
     */
    protected function runWorker($connection, $queue, $handler, $delay, $memory, $daemon = false)
    {
        if ($daemon) {
            $this->worker->setCache($this->laravel['cache']->driver());

            $this->worker->setDaemonExceptionHandler(
                $this->laravel['Illuminate\Contracts\Debug\ExceptionHandler']
            );

            return $this->worker->daemon(
                $connection,
                $queue,
                $handler,
                $delay,
                $memory,
                $this->option('sleep'),
                $this->option('tries')
            );
        }

        return $this->worker->pop(
            $connection,
            $queue,
            $handler,
            $delay,
            $this->option('sleep'),
            $this->option('tries')
        );
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  bool  $failed
     * @return void
     */
    protected function writeOutput(Job $job, $failed)
    {
        if ($failed) {
            $this->output->writeln('<error>Failed One Message from the Queue</error> ');
        } else {
            $this->output->writeln('<info>Processed One Message from the Queue</info> ');
        }
    }

    /**
     * Determine if the worker should run in maintenance mode.
     *
     * @return bool
     */
    protected function downForMaintenance()
    {
        if ($this->option('force')) {
            return false;
        }

        return $this->laravel->isDownForMaintenance();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of connection', null],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on'],

            ['daemon', null, InputOption::VALUE_NONE, 'Run the worker in daemon mode'],

            ['handler', null, InputOption::VALUE_REQUIRED, 'The handler to handle the message'],

            ['delay', null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0],

            ['force', null, InputOption::VALUE_NONE, 'Force the worker to run even in maintenance mode'],

            ['memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128],

            ['sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3],

            ['tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0],
        ];
    }
}
