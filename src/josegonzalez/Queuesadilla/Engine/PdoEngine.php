<?php
namespace josegonzalez\Queuesadilla\Engine;

use DateInterval;
use DateTime;
use PDO;
use PDOException;
use josegonzalez\Queuesadilla\Engine\Base;

abstract class PdoEngine extends Base
{
    /**
     * @const JOB_STATUS_SUCCESS
     */
    const JOB_STATUS_SUCCESS = 'success';
    
    /**
     * @const JOB_STATUS_FAILED
     */
    const JOB_STATUS_FAILED = 'failed';

    /**
     * @const JOB_STATUS_NEW
     */
    const JOB_STATUS_NEW = 'new';

    /**
     * @const JOB_STATUS_STALLED
     */
    const JOB_STATUS_STALLED = 'stalled';
    
    /**
     *  String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $startQuote = '';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $endQuote = '';

    /**
     * Used to construct the PDO connection
     *
     * @return \PDO
     */
    abstract public function connect();

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item, $status = null)
    {
        if (empty($status)) {
            $status = static::JOB_STATUS_SUCCESS;
        }

        if (!parent::acknowledge($item)) {
            return false;
        }

        if (!empty($this->settings['keepJob']) && $this->settings['keepJob']) {
            $sql = sprintf(
                'UPDATE %s SET status = "' . $status . '", executed_date = NOW() WHERE id = ?',
                $this->quoteIdentifier($this->config('table'))
            );
        } else {
            $sql = sprintf('DELETE FROM %s WHERE id = ?', $this->quoteIdentifier($this->config('table')));
        }

        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $item['id'], PDO::PARAM_INT);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

    /**
     * {@inheritDoc}
     */
    public function reject($item)
    {
        return $this->acknowledge($item, static::JOB_STATUS_STALLED);
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');

        $this->cleanup($queue);

        $selectSql = implode(
            " ",
            [
            sprintf(
                'SELECT id, %s, attempts FROM %s',
                $this->quoteIdentifier('data'),
                $this->quoteIdentifier($this->config('table'))
            ),
            sprintf('WHERE %s = ? AND %s != 1', $this->quoteIdentifier('queue'), $this->quoteIdentifier('locked')),
            'AND (expires_at IS NULL OR expires_at > ?)',
            'AND (delay_until IS NULL OR delay_until < ?)',
            'ORDER BY priority ASC LIMIT 1 FOR UPDATE',
            ]
        );
        $updateSql = sprintf('UPDATE %s SET locked = 1 WHERE id = ?', $this->quoteIdentifier($this->config('table')));

        $datetime = new DateTime;
        $dtFormatted = $datetime->format('Y-m-d H:i:s');

        try {
            $sth = $this->connection()->prepare($selectSql);
            $sth->bindParam(1, $queue, PDO::PARAM_STR);
            $sth->bindParam(2, $dtFormatted, PDO::PARAM_STR);
            $sth->bindParam(3, $dtFormatted, PDO::PARAM_STR);

            $this->connection()->beginTransaction();
            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_ASSOC);

            if (!empty($result)) {
                $sth = $this->connection()->prepare($updateSql);
                $sth->bindParam(1, $result['id'], PDO::PARAM_INT);
                $sth->execute();
                $this->connection()->commit();
                if ($sth->rowCount() == 1) {
                    $data = json_decode($result['data'], true);
                    return [
                        'id' => $result['id'],
                        'class' => $data['class'],
                        'args' => $data['args'],
                        'queue' => $queue,
                        'options' => $data['options'],
                        'attempts' => (int)$result['attempts']
                    ];
                }
            }
            $this->connection()->commit();
        } catch (PDOException $e) {
            $this->logger()->error($e->getMessage());
            $this->connection()->rollBack();
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function push($item, $options = [])
    {
        if (!is_array($options)) {
            $options = ['queue' => $options];
        }

        $delay = $this->setting($options, 'delay');
        $expiresIn = $this->setting($options, 'expires_in');
        $queue = $this->setting($options, 'queue');
        $priority = $this->setting($options, 'priority');
        $attempts = $this->setting($options, 'attempts');
        $attemptsDelay = $this->setting($options, 'attempts_delay');

        $delayUntil = null;
        if ($delay !== null) {
            $datetime = new DateTime;
            $delayUntil = $datetime->add(new DateInterval(sprintf('PT%sS', $delay)))->format('Y-m-d H:i:s');
        }

        $expiresAt = null;
        if ($expiresIn !== null) {
            $datetime = new DateTime;
            $expiresAt = $datetime->add(new DateInterval(sprintf('PT%sS', $expiresIn)))->format('Y-m-d H:i:s');
        }

        unset($options['queue']);
        unset($options['attempts']);
        $item['options'] = $options;
        $item['options']['attempts_delay'] = $attemptsDelay;
        $data = json_encode($item);

        $sql = 'INSERT INTO %s (%s, %s, %s, %s, %s, %s) VALUES (?, ?, ?, ?, ?, ?)';
        $sql = sprintf(
            $sql,
            $this->quoteIdentifier($this->config('table')),
            $this->quoteIdentifier('data'),
            $this->quoteIdentifier('queue'),
            $this->quoteIdentifier('priority'),
            $this->quoteIdentifier('expires_at'),
            $this->quoteIdentifier('delay_until'),
            $this->quoteIdentifier('attempts')
        );
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $data, PDO::PARAM_STR);
        $sth->bindParam(2, $queue, PDO::PARAM_STR);
        $sth->bindParam(3, $priority, PDO::PARAM_INT);
        $sth->bindParam(4, $expiresAt, PDO::PARAM_STR);
        $sth->bindParam(5, $delayUntil, PDO::PARAM_STR);
        $sth->bindParam(6, $attempts, PDO::PARAM_INT);
        $sth->execute();

        if ($sth->rowCount() == 1) {
            $this->lastJobId = $this->connection()->lastInsertId();
        }
        return $sth->rowCount() == 1;
    }

    /**
     * {@inheritDoc}
     */
    public function queues()
    {
        $sql = implode(
            " ",
            [
            sprintf(
                'SELECT %s FROM %s',
                $this->quoteIdentifier('queue'),
                $this->quoteIdentifier($this->config('table'))
            ),
            sprintf('GROUP BY %s', $this->quoteIdentifier('queue')),
            ]
        );
        $sth = $this->connection()->prepare($sql);
        $sth->execute();
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return [];
        }
        return array_map(
            function ($result) {
                return trim($result['queue']);
            },
            $results
        );
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        if (isset($item['attempts']) && $item['attempts'] === 0) {
            return $this->reject($item);
        }

        $fields = [
            [
                'type' => PDO::PARAM_INT,
                'key' => 'locked',
                'value' => 0,
            ],
        ];

        if (isset($item['delay'])) {
            $dateInterval = new DateInterval(sprintf('PT%sS', $item['delay']));
            $datetime = new DateTime;
            $delayUntil = $datetime->add($dateInterval)
                ->format('Y-m-d H:i:s');
            $fields[] = [
                'type' => PDO::PARAM_STR,
                'key' => 'delay_until',
                'value' => $delayUntil,
            ];
        }
        if (isset($item['attempts']) && $item['attempts'] > 0) {
            $fields[] = [
                'type' => PDO::PARAM_INT,
                'key' => 'attempts',
                'value' => (int)$item['attempts'],
            ];
        }
        if (!empty($this->settings['keepJob']) && (bool)$this->settings['keepJob'] === true) {
            $fields[] = [
                'type' => PDO::PARAM_STR,
                'key' => 'status',
                'value' => static::JOB_STATUS_FAILED
            ];
            $fields[] = [
                'type' => PDO::PARAM_STR,
                'key' => 'executed_date',
                'value' => $datetime->format('Y-m-d H:i:s')
            ];
        }
        $updateSql = [];
        foreach ($fields as $config) {
            $updateSql[] = sprintf('%1$s = :%1$s', $config['key']);
        }
        $sql = sprintf(
            'UPDATE %s SET %s WHERE id = :id',
            $this->config('table'),
            implode(', ', $updateSql)
        );
        $sth = $this->connection()->prepare($sql);
        foreach ($fields as $config) {
            $sth->bindValue(sprintf(':%s', $config['key']), $config['value'], $config['type']);
        }
        $sth->bindValue(':id', (int)$item['id'], PDO::PARAM_INT);
        $sth->execute();

        return $sth->rowCount() == 1;
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * Method taken from CakePHP 3.2.10
     *
     * @param  string $identifier The identifier to quote.
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        $identifier = trim($identifier);

        if ($identifier === '*') {
            return '*';
        }

        if ($identifier === '') {
            return '';
        }

        // string
        if (preg_match('/^[\w-]+$/', $identifier)) {
            return $this->startQuote . $identifier . $this->endQuote;
        }

        // string.string
        if (preg_match('/^[\w-]+\.[^ \*]*$/', $identifier)) {
            $items = explode('.', $identifier);
            return $this->startQuote . implode($this->endQuote . '.' . $this->startQuote, $items) . $this->endQuote;
        }

        // string.*
        if (preg_match('/^[\w-]+\.\*$/', $identifier)) {
            return $this->startQuote . str_replace('.*', $this->endQuote . '.*', $identifier);
        }

        return $identifier;
    }

    /**
     * Check if expired jobs are present in the database and reject them
     *
     * @param  string $queue name of the queue
     * @return void
     */
    protected function cleanup($queue)
    {
        $sql = implode(
            " ",
            [
            sprintf(
                'SELECT id FROM %s',
                $this->quoteIdentifier($this->config('table'))
            ),
            sprintf('WHERE %s = ?', $this->quoteIdentifier('queue')),
            'AND expires_at < ?'
            ]
        );

        $datetime = new DateTime;
        $dtFormatted = $datetime->format('Y-m-d H:i:s');

        try {
            $sth = $this->connection()->prepare($sql);
            $sth->bindParam(1, $queue, PDO::PARAM_STR);
            $sth->bindParam(2, $dtFormatted, PDO::PARAM_STR);

            $sth->execute();
            $result = $sth->fetch(PDO::FETCH_ASSOC);

            if (!empty($result)) {
                $this->reject(
                    [
                    'id' => $result['id'],
                    'queue' => $queue
                    ]
                );
            }
        } catch (PDOException $e) {
            $this->logger()->error($e->getMessage());
        }
    }
}
