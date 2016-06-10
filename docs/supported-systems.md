### Available Systems

Queuesadilla supports the following engine engines:

| Engine              | Stability   | Notes                                   |
|---------------------|-------------|-----------------------------------------|
| `BeanstalkEngine`   | **Stable**  | BeanstalkD                              |
| `MemoryEngine`      | **Stable**  | In-memory                               |
| `MysqlEngine`       | **Stable**  | Backed by PDO/MySQL                         |
| `PostgresEngine`    | **Stable**  | Backed by PDO/Postgres                         |
| `RedisEngine`       | **Stable**  | Redis-based                             |
| `SynchronousEngine` | **Stable**  | Synchronous, in-memory                  |
| `NullEngine`        | **Stable**  | Always returns "true" for any operation |
| `IronEngine`        | *Unstable*  | Needs unit tests                        |
| `AzureEngine`       | Planned     |                                         |
| `MemcachedEngine`   | Planned     |                                         |
| `MongodbEngine`     | Planned     |                                         |
| `PdoEngine`         | Planned     |                                         |
| `PredisEngine`      | Planned     |                                         |
| `RabbitMQEngine`    | Planned     |                                         |
| `SqsEngine`         | Planned     |                                         |
