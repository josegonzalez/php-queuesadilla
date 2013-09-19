Usage:

    $backend = new 'SynchronousBackend';
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
