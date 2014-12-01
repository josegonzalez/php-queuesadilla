## Usage:

> The example here uses the MysqlEngine, though you are free to use any of the [stable backends listed here](/php-queuesadilla/supported-systems)

Before queuing a job, you should have a engine to store the jobs as well as an instance of the `Queue` class:

```php
<?php
use josegonzalez\Queuesadilla\Engine\MysqlEngine;
use josegonzalez\Queuesadilla\Queue;

$engine = new MysqlEngine($options);
$queue = new Queue($engine);
?>
```

Next, you will want to create a dummy job:

```php
<?php
function some_job($job)
{
    var_dump($job->data());
}
?>
```

At this point, you can use the `$queue` instance to queue up jobs:

```php
<?php
$queue->push('some_job', [
    'id' => 7,
    'message' => 'hi'
]);
?>
```

Once a job has been pushed, you can construct a worker and execute all jobs in the queue:

```php
<?php
use josegonzalez\Queuesadilla\Engine\MysqlEngine;
use josegonzalez\Queuesadilla\Queue;
use josegonzalez\Queuesadilla\Worker\SequentialWorker;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;

$engine = new MysqlEngine($options);
$logger = new Logger('test', [new ErrorLogHandler]);
$worker = new SequentialWorker($engine, $logger, ['maxIterations' => 5]);
$worker->work();
?>
```

For a more complete example, please see the [example](https://github.com/josegonzalez/php-queuesadilla/blob/master/example.php).
