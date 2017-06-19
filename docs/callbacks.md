## Callbacks

Queuesadilla allows developers to hook into various events in the queue/work cycle using the [league/event](http://event.thephpleague.com/1.0/) package. To do so, you can attach a listener to the object as follows:

```php
<?php
use League\Event\AbstractEvent;
$object = new ClassThatEmitsEvents;
// Using a callable
$object->attachListener($event, function (AbstractEvent $event) {
    // Your Work Here
});

// Using a class
$object->attachListener(new ClassBasedListener);
?>
```

The `AbstractEvent $event`  object that is passed in will always be an instance of the `josegonzalez\Queuesadilla\Event\Event` class, which has the ability the following helper methods:

- `name()`: Returns the current event name
- `subject()`: Returns the subject of the event, normally the object that dispatched said event
- `data()`: Returns any data the emitted event may contain

You can otherwise interact with the events as you normally would with `league/event` package. Please consult those [docs](http://event.thephpleague.com/1.0/) for more information.

### Creating Class Based Listeners

There are two ways to create class-based listeners. The simplest way would be to create a class that extends `League\Event\AbstractEvent`:

```php
<?php
use League\Event\AbstractListener;
use League\Event\AbstractEvent;

class StatsListener extends AbstractListener
{
    public function handle(AbstractEvent $event)
    {
        // do your worst here
    }
}
?>
```

You can also create a listener that can bind to multiple events by extending the `josegonzalez\Queuesadilla\Event\MultiEventListener` class:

```php
<?php
use josegonzalez\Queuesadilla\Event\MultiEventListener;
use League\Event\AbstractEvent;

class StatsListener extends MultiEventListener
{
    public function implementedEvents()
    {
        return [
            'an.event' => 'aFunction',
            'another.event' => 'anotherFunction',
        ];
    }

    public function aFunction(AbstractEvent $event)
    {
        // do your worst here
    }

    public function anotherFunction(AbstractEvent $event)
    {
        // do your worst here
    }
}
?>
```

### Available Events

#### Queuing

When queuing a job, you have the ability to hook into the following events:

- `Queue.afterEnqueue`: Available data includes an `item` array - the data being enqueued - as well as a boolean `success` value that contains whether the job was enqueued. Note that the `item` array will only contain a job `id` if the job was successfully enqueued.

#### Worker

When processing jobs via a worker, you have the ability to hook into the following events:

- `Worker.connectionFailed`: Dispatched when the configured backend could not connect to the backend.
- `Worker.maxIterations`: Dispatched when the configured max number of iterations has been hit.
- `Worker.maxRuntime`: Dispatched when the configured max runtime is reached.
- `Worker.job.seen`: Dispatched after `Engine::pop()` has returned. Will contain an `item` key in it's data, which may be populated with item data if the `pop()` was successful.
- `Worker.job.empty`: Dispatched if the `item` produced from `Engine::pop()` is empty.
- `Worker.job.invalid`: Dispatched if the `item` produced from `Engine::pop()` contains an invalid callable.
- `Worker.job.start`: Dispatched before a job is performed. Data contains a `job` key.
- `Worker.job.exception`: Dispatched if performing the job resulted in any kind of exception. Data contains both a `job` and `exception` key.
- `Worker.job.success`: Dispatched if performing the job was successful. Data contains a `job` key.
- `Worker.job.failure`: Dispatched if performing the job failed. Data contains a `job` key.
