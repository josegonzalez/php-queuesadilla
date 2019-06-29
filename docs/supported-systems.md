### Available Systems

Queuesadilla supports the following engine engines:

| Engine              | Stability   | Notes                                   |
|---------------------|-------------|-----------------------------------------|
| `BeanstalkEngine`   | **Stable**  | BeanstalkD                              |
| `MemoryEngine`      | **Stable**  | In-memory                               |
| `MysqlEngine`       | **Stable**  | Backed by PDO/MySQL                     |
| `PdoEngine`         | **Stable**  |                                         |
| `PostgresEngine`    | **Stable**  | Backed by PDO/Postgres                  |
| `PredisEngine`      | **Stable**  | Redis-based, requires `nrk/predis`      |
| `RedisEngine`       | **Stable**  | Redis-based, requires `ext-redis`       |
| `SynchronousEngine` | **Stable**  | Synchronous, in-memory                  |
| `NullEngine`        | **Stable**  | Always returns "true" for any operation |
| `IronEngine`        | *Unstable*  | Needs unit tests                        |
| `RabbitMQEngine`    | *Unstable*  | Needs unit tests                        |
| `AzureEngine`       | Planned     |                                         |
| `SqsEngine`         | Planned     |                                         |
