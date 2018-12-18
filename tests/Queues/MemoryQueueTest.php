<?php

namespace UrbanIndo\Yii2\QueueTests\Queues;

use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queues\DbQueue;
use UrbanIndo\Yii2\QueueTests\TestCase;
use Yii;

class MemoryQueueTest extends TestCase
{
 
    static $counter = 0;
    
    protected function setUp()
    {
        parent::setUp();
        self::$counter = 0;
        $this->mockApplication([
            'components' => [
                'queue' => [
                    'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue',
                ]
            ]
        ]);
    }
    
    /**
     * 
     * @return \UrbanIndo\Yii2\Queue\Queues\MemoryQueue
     */
    protected function getQueue()
    {
        return Yii::$app->queue;
    }
    
    public function testPost()
    {
        $queue = $this->getQueue();
        
        $this->assertEquals(0, $queue->getSize());
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 1;
        }]));
        
        $this->assertEquals(1, $queue->getSize());        
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 1;
        }]));
        
        $this->assertEquals(2, $queue->getSize());
    }
    
    public function testFetch()
    {
        $queue = $this->getQueue();
        
        $this->assertEquals(0, $queue->getSize());
        
        $job = $queue->fetch();
        
        $this->assertFalse($job);
        
        $queue->post(new Job(['route' => function () {
            $this->counter += 1;
        }]));
        
        $this->assertEquals(1, $queue->getSize());
        
        $job = $queue->fetch();
        
        $this->assertEquals(0, $queue->getSize());
        
        $this->assertTrue($job instanceof Job);
    }
    
    public function testRun()
    {
        $queue = $this->getQueue();
        
        $this->assertEquals(0, $queue->getSize());
        
        $job = $queue->fetch();
        
        $this->assertFalse($job);
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 1;
        }]));
        
        $job = $queue->fetch();

        $this->assertTrue($job instanceof Job);
        
        $queue->run($job);
        
        $this->assertEquals(1, self::$counter);
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 2;
        }]));
        
        $job = $queue->fetch();
        
        $queue->run($job);
        
        $this->assertEquals(3, self::$counter);
    }
    
    public function testDelete()
    {
        $queue = $this->getQueue();
        
        $this->assertEquals(0, $queue->getSize());
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 1;
        }]));
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 1;
        }]));
        
        $this->assertEquals(2, $queue->getSize());
        
        $job = $queue->fetch();
        
        $this->assertEquals(1, $queue->getSize());
        
        $queue->delete($job);
        
        $this->assertEquals(1, $queue->getSize());
        
    }
    
    public function testRelease()
    {
        $queue = $this->getQueue();
        
        $this->assertEquals(0, $queue->getSize());
        
        $job = $queue->fetch();
        
        $this->assertFalse($job);
        
        $queue->post(new Job(['route' => function () {
            self::$counter += 1;
        }]));
        
        $job = $queue->fetch();
        
        $this->assertEquals(0, $queue->getSize());
        
        $this->assertTrue($job instanceof Job);
        
        $queue->release($job);
        
        $this->assertEquals(1, $queue->getSize());
        
    }
}
