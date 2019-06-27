<?php
namespace josegonzalez\Queuesadilla\Engine;

use josegonzalez\Queuesadilla\Engine\MongoEngine;
use MongoDB\BSON\ObjectId;
use Psr\Log\NullLogger;

class MongoEngineTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \josegonzalez\Queuesadilla\Engine\MongoEngine
     */
    protected $Engine;
    /**
     * @var NullLogger
     */
    private $Logger;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $mongoUrl = env('MONGO_URL');
        if (empty($mongoUrl)) {
            $this->markTestSkipped("The env 'MONGO_URL' is not defined for this TestCase");
        }
        $this->Logger = new NullLogger();
        $this->Engine = new MongoEngine($this->Logger, [
            'uri' => $mongoUrl,
            'database' => env('MONGO_DATABASE'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        try {
            $db = $this->Engine->connection();
            if ($db) {
                $db->dropCollection('default_queues');
            }
        } catch(\Exception $e) {

        }
        unset($this->Engine, $this->Logger);
    }


    /**
     * Test connect method
     *
     * @return void
     */
    public function testConnect()
    {
        $this->assertTrue($this->Engine->connect());
    }

    /**
     * Test connect method
     *
     * @return void
     */
    public function testConnectCallableForDatabase()
    {
        $mock = $this->getMockBuilder(\MongoDB\Database::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->Engine = new MongoEngine($this->Logger, [
            'database' => function() use ($mock) {
                return $mock;
            }
        ]);

        $this->assertTrue($this->Engine->connect());
        $this->assertSame($mock, $this->Engine->connection());
    }


    /**
     * Test connect method with connection failure
     *
     * @return void
     */
    public function testConnectFailure()
    {
        $this->Engine = new MongoEngine($this->Logger, [
            'database' => function() {
                throw new \InvalidArgumentException('Invalid connection');
            }
        ]);
        $this->assertFalse($this->Engine->connect());
    }

    /**
     * Test push method
     */
    public function testPush()
    {
        $this->loadDocuments();
        $queue = 'test_push';
        $item = [
            'class' => '\\App\\Job\\MyCustomJob::download',
            'args' => [
                [
                    'item_id' => '10',
                    'label' => 'hello'
                ]
            ],
            'queue_time' => 1560453842.6822
        ];
        $options = [
            'queue' => $queue,
        ];
        $filter = [
            'queue' => 'test_push',
            'args.item_id' => '10'
        ];
        $this->assertEmpty($this->Engine->getCollection()->findOne($filter));
        $this->assertTrue($this->Engine->push($item, $options));
        $message = $this->Engine->getCollection()->findOne($filter);
        $this->assertNotEmpty($message);

        $expected = [
            'queue' => 'test_push',
            'priority' => 0,
            'expiresAt' => null,
            'delayUntil' => null,
            'attempts' => 0,
            'options' => [
                'attempts_delay' => null
            ]
        ] + $item;
        $actual = json_decode(json_encode($message), true);
        unset($actual['_id']);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test pop method
     *
     * @return void
     */
    public function testPop()
    {
        $this->loadDocuments();
        $actual = $this->Engine->pop([
            'queue' => 'test_push'
        ]);
        $this->assertNotEmpty($actual);
        $this->assertEquals('5c877c5500555163fa502812', $actual['id']);
        $this->assertEquals(1, $actual['locked']);
    }

    /**
     * Test pop method
     *
     * @return void
     */
    public function testPopWithDelay()
    {
        $this->loadDocuments();
        $this->Engine->getCollection()->updateOne(
            [
                '_id' => new ObjectId('5c877c5500555163fa502812')
            ],
            [
                '$set' => ['delayUntil' => (new \DateTime('+1 hour'))->getTimestamp()]
            ]
        );
        $actual = $this->Engine->pop([
            'queue' => 'test_push'
        ]);
        $this->assertNotEmpty($actual);
        $this->assertEquals('5d877c5500555163fa502812', $actual['id']);
        $this->assertEquals(1, $actual['locked']);
    }

    /**
     * Test pop method
     *
     * @return void
     */
    public function testPopWithExpiresAt()
    {
        $this->loadDocuments();
        $this->Engine->getCollection()->updateOne(
            [
                '_id' => new ObjectId('5c877c5500555163fa502812')
            ],
            [
                '$set' => ['expiresAt' => (new \DateTime('-1 hour'))->getTimestamp()]
            ]
        );

        $actual = $this->Engine->pop([
            'queue' => 'test_push'
        ]);
        $this->assertNotEmpty($actual);
        $this->assertEquals('5d877c5500555163fa502812', $actual['id']);
        $this->assertEquals(1, $actual['locked']);
    }

    /**
     * Test acknowledge method
     *
     * @return void
     */
    public function testAcknowledge()
    {
        $this->loadDocuments();
        $filter = [
            'queue' => 'test_push',
            '_id' => new ObjectId('4c877c5500555163fa502812')
        ];
        $this->assertNotEmpty($this->Engine->getCollection()->findOne($filter));
        $actual = $this->Engine->acknowledge([
            'queue' => 'test_push',
            'id' => '4c877c5500555163fa502812'
        ]);
        $this->assertTrue($actual);
        $this->assertEmpty($this->Engine->getCollection()->findOne($filter));
        $this->assertNotEmpty($this->Engine->getCollection()->findOne());
    }

    /**
     * Test reject method
     *
     * @return void
     */
    public function testReject()
    {
        $this->loadDocuments();
        $filter = [
            'queue' => 'test_push',
            '_id' => new ObjectId('4c877c5500555163fa502812')
        ];
        $this->assertNotEmpty($this->Engine->getCollection()->findOne($filter));
        $actual = $this->Engine->reject([
            'queue' => 'test_push',
            'id' => '4c877c5500555163fa502812'
        ]);
        $this->assertTrue($actual);
        $this->assertEmpty($this->Engine->getCollection()->findOne($filter));
        $this->assertNotEmpty($this->Engine->getCollection()->findOne());
    }

    /**
     * Test release method. should remove when attempts is zero
     *
     * @return void
     */
    public function testReleaseShouldRemove()
    {
        $this->loadDocuments();
        $filter = [
            'queue' => 'test_push',
            '_id' => new ObjectId('4c877c5500555163fa502812')
        ];
        $this->assertNotEmpty($this->Engine->getCollection()->findOne($filter));
        $actual = $this->Engine->release([
            'queue' => 'test_push',
            'id' => '4c877c5500555163fa502812',
            'attempts' => 0
        ]);
        $this->assertTrue($actual);
        $this->assertEmpty($this->Engine->getCollection()->findOne($filter));
        $this->assertNotEmpty($this->Engine->getCollection()->findOne());
    }

    /**
     * Test release method
     *
     * @return void
     */
    public function testRelease()
    {
        $this->loadDocuments();
        $filter = [
            'queue' => 'test_push',
            '_id' => new ObjectId('4c877c5500555163fa502812'),
            'locked' => 1,
        ];
        $before = $this->Engine->getCollection()->findOne($filter);
        $this->assertNotEmpty($before);
        $actual = $this->Engine->release([
            'queue' => 'test_push',
            'id' => '4c877c5500555163fa502812',
            'attempts' => 10
        ]);
        $this->assertTrue($actual);
        $filter = [
            'queue' => 'test_push',
            '_id' => new ObjectId('4c877c5500555163fa502812'),
            'locked' => 0,
        ];
        $this->assertNotEmpty($this->Engine->getCollection()->findOne($filter));
    }

    /**
     * Test queues method
     *
     */
    public function testQueues()
    {
        $this->loadDocuments();
        $expected = [
            'other_test_push',
            'test_push'
        ];
        $actual = $this->Engine->queues();
        asort($actual);
        $actual = array_values($actual);
        $this->assertEquals($expected, $actual);
    }

    protected function loadDocuments()
    {
        $documents = [
            [
                "_id" => "4c877c5500555163fa502812",
                "created" => 1561402100,
                'queue' => 'test_push',
                'locked' => 1,
                'delayUntil' => null,
                'expiresAt' => null,
                'class' => 'custom_callable',
                'args' => [
                    [
                        'item_id' => '1',
                        'label' => 'song',
                    ]
                ],
                'queue_time' => 1560451000.6822
            ],
            [
                "_id" => "5c877c5500555163fa502812",
                "created" => 1561402897,
                'queue' => 'test_push',
                'locked' => 0,
                'delayUntil' => null,
                'expiresAt' => null,
                'class' => 'custom_callable',
                'args' => [
                    [
                        'item_id' => '1000',
                        'label' => 'help'
                    ]
                ],
                'queue_time' => 1560453840.6822
            ],
            [
                "_id" => "5d877c5500555163fa502812",
                "created" => 1561402999,
                'queue' => 'test_push',
                'locked' => 0,
                'delayUntil' => null,
                'expiresAt' => null,
                'class' => 'custom_callable',
                'args' => [
                    [
                        'item_id' => '1010',
                        'label' => 'help'
                    ]
                ],
                'queue_time' => 1560453999.6822
            ],
            [
                "_id" => "99777c5500555163fa502812",
                "created" => 1561402897,
                'queue' => 'other_test_push',
                'locked' => 0,
                'delayUntil' => null,
                'expiresAt' => null,
                'class' => 'custom_callable',
                'args' => [
                    [
                        'item_id' => '1001',
                        'label' => 'help a'
                    ]
                ],
                'queue_time' => 1560453840.6822
            ],
        ];
        $database = $this->Engine->connection();
        foreach ($documents as $document) {
            if (isset($document['_id']) && (!$document['_id'] instanceof ObjectId)) {
                $document['_id'] = new ObjectId($document['_id']);
            }
            $database->selectCollection('default_queues')->insertOne($document);
        }
    }
}
