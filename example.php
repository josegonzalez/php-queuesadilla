<?php

require 'vendor/autoload.php';

use League\Event\AbstractEvent;
use josegonzalez\Queuesadilla\Queue;
use josegonzalez\Queuesadilla\Worker\Listener\DummyListener;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

function debug($var)
{
    $template = <<<TEXT
########## DEBUG ##########
%s
###########################

TEXT;
    printf($template, print_r($var, true));
}

function envDefault($key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        $value = $default;
    }

    return $value;
}

function raise($job)
{
    $job;
    throw new Exception("Screw you");
}

class MyJob
{
    public function perform($job)
    {
        debug($job->data());
    }

    public static function run($job)
    {
        $logger = new Logger('MyJob', [new ErrorLogHandler]);
        $logger->info($job->data('message'));
        $sleep = $job->data('sleep');
        if (!empty($sleep)) {
            $logger->info("Sleeping for " . $job->data('sleep') . " seconds");
            sleep($job->data('sleep'));
        }
    }
}

$_type = envDefault('ENGINE_CLASS', 'Memory');
$_worker = envDefault('WORKER_CLASS', 'Sequential');

$EngineClass = "josegonzalez\\Queuesadilla\\Engine\\" . $_type . 'Engine';
$WorkerClass = "josegonzalez\\Queuesadilla\\Worker\\" . $_worker . "Worker";

// Setup a few loggers
$logger = new Logger('Test', [new ErrorLogHandler]);
$callbackLogger = new Logger('Queue.afterEnqueue', [new ErrorLogHandler]);
$dummyLogger = new Logger('Worker.DummyListener', [new ErrorLogHandler]);

// Instantiate necessary classes
$engine = new $EngineClass($logger);
$queue = new Queue($engine);
$worker = new $WorkerClass($engine, $logger, ['maxIterations' => 5]);

// Add some callbacks
$queue->attachListener('Queue.afterEnqueue', function (AbstractEvent $event) use ($callbackLogger) {
    $data = $event->data();
    $item = $data['item'];
    $success = $data['success'];
    if ($data['success']) {
        $callbackLogger->info(sprintf("Job enqueued: %s", json_encode($item['class'])));
        return;
    }
    $callbackLogger->info(sprintf("Job failed to be enqueued: %s", json_encode($item['class'])));
});
$worker->attachListener(new DummyListener($dummyLogger));

// Push some jobs onto the queue
$queue->push('MyJob::run', ['sleep' => 3, 'message' => 'hi', 'raise' => false]);
$queue->push('raise', ['sleep' => 0, 'message' => 'hi2', 'raise' => true]);
$queue->push(['MyJob', 'perform'], ['sleep' => 1, 'message' => 'hi2u2', 'raise' => false]);

// Work
$worker->work();
debug($worker->stats());
