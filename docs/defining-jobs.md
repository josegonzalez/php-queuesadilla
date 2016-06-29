## Creating Jobs

Jobs are simply PHP callables. Job callables should be available to the worker processes via includes, autoloading, etc.

Job callables receive a `Job` instance (not to be confused with your own own jobs), which is a wrapper around the metadata for a given job. The two useful methods of this `Job` instance are:

- `attempts()`: Contains the number of attempts a job has has left before being discarded.
- `data($key = null, $default = null)`: Returns the job payload. If the first argument is passed, then the method will return only that key in the job payload if it exists. You can also fallback to a `$default` value if said key does not exist in the payload

### Bare Functions

> Note: When queuing up a function-based job, the job must be available in the namespace that you instantiate the Worker class in. If you are unsure, include a fully-qualified namespace.

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
$queue->push('\some_job', [
    'id' => 7,
    'message' => 'hi2u'
]);
?>
```

### Static Functions

> Note: When queuing up a class-based job, the job must be available in the namespace that you instantiate the Worker class in. If you are unsure, include a fully-qualified namespace.

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
$queue->push('\SomeClass::staticMethod', [
    'id' => 7,
    'message' => 'hi2u'
]);
?>
```

### Object Instances

> Note: When queuing up a object-based job, the job must be available in the namespace that you instantiate the Worker class in. If you are unsure, include a fully-qualified namespace.

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
$queue->push(['SomeClass', 'instanceMethod'], [
    'id' => 7,
    'message' => 'hi2u',
]);
?>
```
