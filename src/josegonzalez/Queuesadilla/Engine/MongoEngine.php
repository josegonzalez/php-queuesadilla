<?php
namespace josegonzalez\Queuesadilla\Engine;

use DateInterval;
use DateTime;
use josegonzalez\Queuesadilla\Engine\Base;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Operation\FindOneAndUpdate;

/**
 * Class MongoEngine
 *
 * @method \MongoDB\Database connection()
 */
class MongoEngine extends Base
{
    protected $baseConfig = [
        'scheme' => 'mongodb',
        'host' => '127.0.0.1',
        'port' => 27017,
        'database' => null,
        'collection' => 'default_queues'
    ];
    /**
     * @var \MongoDB\Collection
     */
    private $collection;

    public function connect()
    {
        try {
            $database = $this->config('database');
            if (is_callable($database)) {
                $this->connection = $database($this->config());

                return true;
            }
            $dsn = sprintf(
                "%s://%s:%s",
                $this->config('scheme'),
                $this->config('host'),
                $this->config('port')
            );
            $client = new Client($dsn);
            $this->connection = $client->selectDatabase($this->config('database'));

            return true;
        } catch (\Exception $e) {
            $this->logger()->error($e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function reject($item)
    {
        return $this->acknowledge($item);
    }

    /**
     * @inheritDoc
     */
    public function pop($options = [])
    {
        $document =  $this->getCollection()->findOneAndUpdate(
            $this->getPopFilter($options),
            [
                '$set' => ['locked' => 1],
            ],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ]
        );

        if (!$document) {
            return null;
        }
        $document['id'] = (string)$document['_id'];
        $item = json_decode(json_encode($document), true);

        return $item;
    }

    /**
     * @inheritDoc
     */
    public function push($item, $options = [])
    {
        if (!is_array($options)) {
            $options = ['queue' => $options];
        }
        unset($item['id']);
        try {
            $delay = $this->setting($options, 'delay');
            $expiresIn = $this->setting($options, 'expires_in');
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

            $data = [
                'queue' => $this->setting($options, 'queue'),
                'priority' => $this->setting($options, 'priority'),
                'expiresAt' => $expiresAt,
                'delayUntil' => $delayUntil,
                'attempts' => $this->setting($options, 'attempts')
            ];
            unset($options['queue']);
            unset($options['attempts']);
            $item['options'] = $options;
            $item['options']['attempts_delay'] = $attemptsDelay;

            $insertedId = $this->getCollection()->insertOne($data + $item)->getInsertedId();
            if ($insertedId) {
                $this->lastJobId = (string)$insertedId;

                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function acknowledge($item)
    {
        if (!parent::acknowledge($item)) {
            return false;
        }

        try {
            return (bool)$this->getCollection()->findOneAndDelete(
                [
                    'queue' => $item['queue'],
                    '_id' => new ObjectId($item['id'])
                ]
            );
        } catch (\Exception $e) {
            return false;
        }
    }
    /**
     * @inheritDoc
     */
    public function queues()
    {
        return $this->getCollection()->distinct('queue');
    }

    /**
     * @inheritDoc
     */
    public function release($item, $options = [])
    {
        if (isset($item['attempts']) && $item['attempts'] === 0) {
            return $this->reject($item);
        }
        $update = [
            'locked' => 0
        ];
        if (isset($item['delay'])) {
            $dateInterval = new DateInterval(sprintf('PT%sS', $item['delay']));
            $datetime = new DateTime;
            $update['delay_until'] = $datetime->add($dateInterval)->getTimestamp();
        }
        if (isset($item['attempts']) && $item['attempts'] > 0) {
            $update['attempts'] = (int)$item['attempts'];
        }

        return (bool)$this->getCollection()->updateOne(
            [
                '_id' => new ObjectId($item['id'])
            ],
            ['$set' => $update]
        )->getModifiedCount();
    }

    /**
     * @param $options
     * @return array
     */
    protected function getPopFilter($options)
    {
        $timeNow = (new DateTime())->getTimestamp();

        return [
            '$and' => [
                [
                    'queue' => $this->setting($options, 'queue'),
                    'locked' => [
                        '$ne' => 1
                    ],
                ],
                [
                    '$or' => [
                        ['delayUntil' => ['$lt' => $timeNow]],
                        ['delayUntil' => null]
                    ]
                ],
                [
                    '$or' => [
                        ['expiresAt' => ['$gt' => $timeNow]],
                        ['expiresAt' => null]
                    ]
                ],
            ]
        ];
    }

    /**
     * Get the collection instance
     *
     * @return \MongoDB\Collection
     */
    public function getCollection()
    {
        if ($this->collection === null) {
            $this->collection = $this->connection()->selectCollection($this->config('collection'));
        }

        return $this->collection;
    }
}
