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
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        $sql = sprintf('DELETE FROM %s WHERE id = ?', $this->quoteIdentifier($this->config('table')));

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
        return $this->acknowledge($item);
    }

    /**
     * {@inheritDoc}
     */
    public function pop($options = [])
    {
        $queue = $this->setting($options, 'queue');
        $selectSql = implode(" ", [
            sprintf(
                'SELECT id, %s FROM %s',
                $this->quoteIdentifier('data'),
                $this->quoteIdentifier($this->config('table'))
            ),
            sprintf('WHERE %s = ? AND %s != 1', $this->quoteIdentifier('queue'), $this->quoteIdentifier('locked')),
            'AND (expires_at IS NULL OR expires_at > ?)',
            'AND (delay_until IS NULL OR delay_until < ?)',
            'ORDER BY priority ASC LIMIT 1 FOR UPDATE',
        ]);
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
        $item['options'] = $options;
        $data = json_encode($item);

        $sql = 'INSERT INTO %s (%s, %s, %s, %s, %s) VALUES (?, ?, ?, ?, ?)';
        $sql = sprintf(
            $sql,
            $this->quoteIdentifier($this->config('table')),
            $this->quoteIdentifier('data'),
            $this->quoteIdentifier('queue'),
            $this->quoteIdentifier('priority'),
            $this->quoteIdentifier('expires_at'),
            $this->quoteIdentifier('delay_until')
        );
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $data, PDO::PARAM_STR);
        $sth->bindParam(2, $queue, PDO::PARAM_STR);
        $sth->bindParam(3, $priority, PDO::PARAM_INT);
        $sth->bindParam(4, $expiresAt, PDO::PARAM_STR);
        $sth->bindParam(5, $delayUntil, PDO::PARAM_STR);
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
        $sql = implode(" ", [
            sprintf(
                'SELECT %s FROM %s',
                $this->quoteIdentifier('queue'),
                $this->quoteIdentifier($this->config('table'))
            ),
            sprintf('GROUP BY %s', $this->quoteIdentifier('queue')),
        ]);
        $sth = $this->connection()->prepare($sql);
        $sth->execute();
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (empty($results)) {
            return [];
        }
        return array_map(function ($result) {
            return trim($result['queue']);
        }, $results);
    }

    /**
     * {@inheritDoc}
     */
    public function release($item, $options = [])
    {
        $sql = sprintf('UPDATE %s SET locked = 0 WHERE id = ?', $this->config('table'));
        $sth = $this->connection()->prepare($sql);
        $sth->bindParam(1, $item['id'], PDO::PARAM_INT);
        $sth->execute();
        return $sth->rowCount() == 1;
    }

    /**
     * Quotes a database identifier (a column name, table name, etc..) to
     * be used safely in queries without the risk of using reserved words
     *
     * Method taken from CakePHP 3.2.10
     *
     * @param string $identifier The identifier to quote.
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
}
