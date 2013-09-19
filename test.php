<?php

$_type = 'Synchronous';

require 'Backend.php';
require 'Queue.php';
require 'Job.php';
require 'Worker.php';

require 'Backend/MemoryBackend.php';
require 'Job/MemoryJob.php';

require 'Backend/' . $_type . 'Backend.php';
require 'Job/' . $_type . 'Job.php';

require 'jobs.php';

$BackendClass = $_type . 'Backend';

$backend = new $BackendClass;
$queue = new Queue($backend);

$queue->push('MyJob::run', array('sleep' => 3, 'message' => 'hi', 'raise' => false));
$queue->push('raise', array('sleep' => 0, 'message' => 'hi2', 'raise' => true));
$queue->push(array('Output', 'output'), array('sleep' => 1, 'message' => 'hi2u', 'raise' => false));

$worker = new Worker($backend, array('max_iterations' => 5));
$worker->work();
