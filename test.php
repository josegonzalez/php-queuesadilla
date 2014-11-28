<?php

$_type = 'Synchronous';

require 'vendor/autoload.php';

use josegonzalez\Queuesadilla\Engine\SynchronousEngine;
use josegonzalez\Queuesadilla\Queue;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

function raise($job)
{
    $job;
    throw new Exception("Screw you");
}

class Output
{
    public function perform($job)
    {
        var_dump($job->data());
    }

    public static function run($job)
    {
        printf("[MyJob] " . $job->data('message') . "\n");
        $sleep = $job->data('sleep');
        if (!empty($sleep)) {
            printf("[MyJob] Sleeping for " . $job->data('sleep') . " seconds\n");
            sleep($job->data('sleep'));
        }
    }
}

$EngineClass = "josegonzalez\\Queuesadilla\\Engine\\" . $_type . 'Engine';

$logger = new Logger('test');
$logger->pushHandler(new ErrorLogHandler);
$engine = new $EngineClass($logger);
$queue = new Queue($engine);

$queue->push('MyJob::run', ['sleep' => 3, 'message' => 'hi', 'raise' => false]);
$queue->push('raise', ['sleep' => 0, 'message' => 'hi2', 'raise' => true]);
$queue->push(['MyJob', 'perform'], ['sleep' => 1, 'message' => 'hi2u', 'raise' => false]);

$worker = new SequentialWorker($engine, $logger, ['maxIterations' => 5]);
$worker->work();
