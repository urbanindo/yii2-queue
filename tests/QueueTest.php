<?php

namespace UrbanIndo\Yii2\QueueTests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queues\MemoryQueue;
use Yii;

class QueueTest extends BaseTestCase
{
    
    public function testQueueCatchingException()
    {
        $this->expectException(\yii\base\Exception::class);
        $queue = Yii::createObject([
            'class' => MemoryQueue::class,
        ]);
         
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
         $queue->post(new Job([
             'route' => function() {
                throw new \Exception('Test');
             }
         ]));
         $this->assertEquals(1, $queue->getSize());
         $job = $queue->fetch();
         $this->assertEquals(0, $queue->getSize());
         $queue->run($job);
    }
}


