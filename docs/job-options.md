### Job Options

Queuing options are configured either at Engine creation or when queuing a job. Options declared when queuing a job take precedence over those at Engine instantiation. All queueing systems support the following options unless otherwise specified:

- `queue`: Name of a queue to place a job on. All queues are dynamic, and need not be declared beforehand.
- `max_attempts`: Max number of attempts a job can be performed until it is marked as dead.
- `attempts_delay`: Seconds to wait after a job with max_attempts will be executed again
- `priority`: Jobs with smaller priority values will be scheduled before jobs with larger priorities. Not available with the `MemoryEngine` or `SynchronousEngine`. Job priorities are constants, and there are 5 priorities:
    - Job::LOW
    - Job::NORMAL
    - Job::MEDIUM
    - Job::HIGH
    - Job::CRITICAL
- `delay`: Seconds to wait before putting the job in the ready queue. The job will be in the "delayed" state during this time.
- `time_to_run`: Max amount of time (in seconds) a job can take to run before it is released to the general queue. Not available with the `MysqlEngine`
- `expires_in`: Max amount of time a job may be in the queue until it is discarded
