<?php

$_type = 'Synchronous';

require 'src/Queuesadilla/Backend.php';
require 'src/Queuesadilla/Queue.php';
require 'src/Queuesadilla/Job.php';
require 'src/Queuesadilla/Worker.php';

require 'src/Queuesadilla/Backend/MemoryBackend.php';
require 'src/Queuesadilla/Job/MemoryJob.php';

require 'src/Queuesadilla/Backend/' . $_type . 'Backend.php';
require 'src/Queuesadilla/Job/' . $_type . 'Job.php';

function raise($job) {
  throw new Exception("Screw you");
}

class Output {
  public function output($job) {
    var_dump($job->data());
  }
}

class MyJob {
  public static function run($job) {
    printf("[MyJob] " . $job->data('message') . "\n");
    $sleep = $job->data('sleep');
    if (!empty($sleep)) {
      printf("[MyJob] Sleeping for " . $job->data('sleep') . " seconds\n");
      sleep($job->data('sleep'));
    }
  }
}

$BackendClass = "Queuesadilla\\Backend\\" . $_type . 'Backend';

$backend = new $BackendClass;
$queue = new Queuesadilla\Queue($backend);

$queue->push('MyJob::run', array('sleep' => 3, 'message' => 'hi', 'raise' => false));
$queue->push('raise', array('sleep' => 0, 'message' => 'hi2', 'raise' => true));
$queue->push(array('Output', 'output'), array('sleep' => 1, 'message' => 'hi2u', 'raise' => false));

$worker = new Queuesadilla\Worker($backend, array('max_iterations' => 5));
$worker->work();
