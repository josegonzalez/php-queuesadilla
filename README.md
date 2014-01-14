# PHP Queuesadilla [![Build Status](https://travis-ci.org/josegonzalez/php-queuesadilla.png?branch=master)](https://travis-ci.org/josegonzalez/php-queuesadilla) [![Coverage Status](https://coveralls.io/repos/josegonzalez/php-queuesadilla/badge.png?branch=master)](https://coveralls.io/r/josegonzalez/php-queuesadilla?branch=master)

A job/worker system built that supports various queuing systems

## Installation

TODO

## Usage:

    $backend = new SynchronousBackend;
    $queue = new Queue($backend);

    $queue->push('MyJob::run', array('sleep' => 3, 'message' => 'hi', 'raise' => false));
    $queue->push('raise', array('sleep' => 0, 'message' => 'hi2', 'raise' => true));
    $queue->push(array('Output', 'output'), array('sleep' => 1, 'message' => 'hi2u', 'raise' => false));

    $worker = new Worker($backend, array('max_iterations' => 5));
    $worker->work();

Output:

    [SynchronousBackend Worker] Starting worker, max iterations 1
    [MyJob] hi
    [MyJob] Sleeping for 3 seconds
    [SynchronousBackend Worker] Success!
    [SynchronousBackend Worker] Max iterations reached, exiting
    [SynchronousBackend Worker] Starting worker, max iterations 1
    [SynchronousBackend Worker] Exception! Screw you
    [SynchronousBackend Worker] Failed!
    [SynchronousBackend Worker] Max iterations reached, exiting
    [SynchronousBackend Worker] Starting worker, max iterations 1
    [SynchronousBackend Worker] Invalid callable for job!
    [SynchronousBackend Worker] Failed!
    [SynchronousBackend Worker] Max iterations reached, exiting
    [SynchronousBackend Worker] Starting worker, max iterations 5
    [SynchronousBackend Worker] No job!
    [SynchronousBackend Worker] No job!
    [SynchronousBackend Worker] No job!
    [SynchronousBackend Worker] No job!
    [SynchronousBackend Worker] No job!
    [SynchronousBackend Worker] Max iterations reached, exiting

### Available Systems

Queuesadilla supports the following backend engines:

- BeanstalkD via `BeanstalkBackend`
- IronMQ via `IronBackend`
- In Memory via `MemoryBackend`
- Mysql via `MysqlBackend`
- Redis via `RedisBackend`
- Synchronous via `SynchronousBackend`
- Test via `TestBackend`

### Queuing jobs

Before queuing a job, you should have a backend to store the jobs as well as an instance of the `Queue` class:

```php
<?php
use josegonzalez\Queuesadilla\Backend\MysqlBackend;
use josegonzalez\Queuesadilla\Queue;

$backend = new MysqlBackend($options);
$queue = new Queue($backend);
?>
```

At this point, you can use the `$queue` instance to queue up jobs:

```
<?php
$queue->push('some_job', array('id' => 7, 'message' => 'hi'));
?>
```

### Creating Jobs Callables

Jobs are simply PHP callables. They take an instance of a `Job` class, which is a wrapper around the metadata for a given job. Job callables should be available to the worker processes via includes, autoloading, etc.

#### Bare Functions

You can create global functions for job workers:

```php
<?php
function some_job($job) {
  $id = $job->data('id');
  $message = $job->data('message');

  $post = Post::get($id);
  $post->message = $message;
  $post->save();
}

// Queue up the job
$queue->push('some_job', array('id' => 7, 'message' => 'hi2u'));
?>
```

#### Static Functions

Static functions are also an option. State may or may not be cleared, so keep this in mind:

```php
<?php
class SomeClass {
  public static function staticMethod($job) {
    $id = $job->data('id');
    $message = $job->data('message');

    $post = Post::get($id);
    $post->message = $message;
    $post->save();
  }
}
// Queue up the job
$queue->push('SomeClass::staticMethod', array('id' => 7, 'message' => 'hi2u'));
?>
```

#### Object Instances

We can also create completely new instances of a class that will execute a job.

```php
<?php
class SomeClass {
  public function instanceMethod($job) {
    $id = $job->data('id');
    $message = $job->data('message');

    $post = Post::get($id);
    $post->message = $message;
    $post->save();
  }
}
// Queue up the job
$queue->push(array('SomeClass', 'instanceMethod'), array('id' => 7, 'message' => 'hi2u'));
?>
```

### Job Options

Queuing options are configured either at Backend creation or when queuing a job. Options declared when queuing a job take precedence over those at Backend instantiation. All queueing systems support the following options unless otherwise specified:

- `queue`: Name of a queue to place a job on. All queues are dynamic, and need not be declared beforehand.
- `attempts`: Max number of attempts a job can be performed until it is marked as dead.
- `priority`: Jobs with smaller priority values will be scheduled before jobs with larger priorities. Job priorities are constants, and there are 5 priorities:
    - Job::LOW
    - Job::NORMAL
    - Job::MEDIUM
    - Job::HIGH
    - Job::CRITICAL
- `delay`: Seconds to wait before putting the job in the ready queue. The job will be in the "delayed" state during this time.
- `time_to_run`: Max amount of time (in seconds) a job can take to run before it is released to the general queue. Not available with the `MysqlBackend`
- `expires_in`: Max amount of time a job may be in the queue until it is discarded
