<?php

namespace UrbanIndo\Yii2\QueueTests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queue;
use UrbanIndo\Yii2\Queue\Queues\MemoryQueue;
use Yii;
use yii\base\Event;

class EventTest extends BaseTestCase
{

    public $counter;

    public function testOn()
    {
        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_POST,
                function ($event) {
                    $this->counter += 1;
            }
        );
        
        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_FETCH,
                function ($event) {
                    $this->counter += 2;
            }
        );
        
        Event::on(
            Queue::class,
            Queue::EVENT_AFTER_DELETE,
                function ($event) {
                    $this->counter += 3;
            }
        );

        $queue = Yii::createObject([
            'class' => MemoryQueue::class,
        ]);

        $this->assertEquals($this->counter, 0);
        
        /* @var $queue Queues\MemoryQueue */
        $queue->post(new Job([
            'route' => function() {
                //Do something
            }
        ]));
        
        $this->assertEquals($this->counter, 1);
        
        $job = $queue->fetch();
        
        $this->assertEquals($this->counter, 3);
        
        $queue->delete($job);
        
        $this->assertEquals($this->counter, 6);
    }

}
