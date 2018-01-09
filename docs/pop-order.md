## Pop order

By default, jobs are retrieved using the `priority` column value ascending, you can set job priority
using

```
    $engine->push($data, ['queue' => 'your-queue-name', 'priority' => 4]) # lower values for higher priority
```

if you want to retrieve jobs
using FIFO, change the pop option to

```
    $engine->pop([
        'queue' => 'default',
        'pop_order' => PdoEngine::POP_ORDER_FIFO,
    ]
```
