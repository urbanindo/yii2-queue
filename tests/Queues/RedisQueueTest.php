<?php

namespace UrbanIndo\Yii2\QueueTests\Queues;

use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queues\RedisQueue;
use UrbanIndo\Yii2\QueueTests\TestCase;
use Faker\Factory;
use Yii;
use yii\redis\Connection;

class RedisQueueTest extends TestCase
{
    static $counter = 0;
    
    protected function setUp()
    {
        parent::setUp();
        RedisQueueTest::$counter = 0;
        $faker = Factory::create();
        $queueName = 'queue_' . $faker->firstNameMale;
        $this->mockApplication([
            'components' => [
                'redis' => [
                    'class' => Connection::class,
                    'hostname' => 'localhost',
                    'port' => 6379,
                ],
                'queue' => [
                    'class' => RedisQueue::class,
                    'key' => $queueName,
                ]
            ]
        ]);
    }
    
    /**
     * 
     * @return \UrbanIndo\Yii2\Queue\Queues\RedisQueue
     */
    public function getQueue()
    {
        return Yii::$app->queue;
    }
    
    public function getCountItems()
    {
        $queue = $this->getQueue();
        $key = $queue->key;
        return Yii::$app->redis->llen($key);
    }
    
    public function testPost()
    {
        $queue = $this->getQueue();
        $this->assertEquals(0, $this->getCountItems());
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 1;
        }]));
        $this->assertEquals(1, $this->getCountItems());
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 1;
        }]));
        $this->assertEquals(2, $this->getCountItems());
    }
    
    public function testFetch()
    {
        $queue = $this->getQueue();
        $key = $queue->key;
        $this->assertEquals(0, $this->getCountItems());
        
        $job = $queue->fetch();
        $this->assertFalse($job);
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 1;
        }]));
        $this->assertEquals(1, $this->getCountItems());
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 1;
        }]));
        $this->assertEquals(2, $this->getCountItems());
        
        $job = $queue->fetch();
        $this->assertTrue($job instanceof Job);

        $this->assertEquals(1, $this->getCountItems());
    }
    
    public function testRun()
    {
        $queue = $this->getQueue();
        
        $job = $queue->fetch();
        
        $this->assertFalse($job);
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 1;
        }]));
        
        $job = $queue->fetch();
        
        $this->assertTrue($job instanceof Job);
        
        $queue->run($job);
        
        $this->assertEquals(1, RedisQueueTest::$counter);
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 2;
        }]));
        
        $job = $queue->fetch();
        
        $queue->run($job);
        
        $this->assertEquals(3, RedisQueueTest::$counter);
    }
    
    public function testRelease()
    {
        $queue = $this->getQueue();
        $key = $queue->key;
        $this->assertEquals(0, $this->getCountItems());
        
        $queue->post(new Job(['route' => function () {
            RedisQueueTest::$counter += 1;
        }]));
        $this->assertEquals(1, $this->getCountItems());
        
        $job = $queue->fetch();
        
        $this->assertTrue($job instanceof Job);

        $this->assertEquals(0, $this->getCountItems());
        
        $queue->release($job);
        
        $this->assertEquals(1, $this->getCountItems());
        
        $job = $queue->fetch();
        
        $this->assertEquals(0, $this->getCountItems());
    }
    
}
