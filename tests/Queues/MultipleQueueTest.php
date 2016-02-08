<?php

class MultipleQueueTest extends PHPUnit_Framework_TestCase {
    
    public function test() {
        $queue = Yii::createObject([
            'class' => '\UrbanIndo\Yii2\Queue\Queues\MultipleQueue',
            'queues' => [
                [
                    'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue'
                ],
                [
                    'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue'
                ],
                [
                    'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue'
                ],
                [
                    'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue'
                ]
            ],
            'strategy' => [
                'class' => 'UrbanIndo\Yii2\Queue\Strategies\RandomStrategy',
            ]
        ]);
        
        $this->assertTrue($queue instanceof UrbanIndo\Yii2\Queue\Queues\MultipleQueue);
        /* @var $queue UrbanIndo\Yii2\Queue\MultipleQueue */
        $this->assertCount(4, $queue->queues);
        foreach($queue->queues as $tqueue) {
            $this->assertTrue($tqueue instanceof \UrbanIndo\Yii2\Queue\Queues\MemoryQueue);
        }
        $this->assertTrue($queue->strategy instanceof \UrbanIndo\Yii2\Queue\Strategies\Strategy);
        $this->assertTrue($queue->strategy instanceof \UrbanIndo\Yii2\Queue\Strategies\RandomStrategy);
        
        $queue0 = $queue->getQueue(0);
        $this->assertTrue($queue0 instanceof \UrbanIndo\Yii2\Queue\Queues\MemoryQueue);
        $queue4 = $queue->getQueue(4);
        $this->assertNull($queue4);
        
        $njob = $queue->strategy->fetch();
        $this->assertFalse($njob);
        $i = 0;
        $queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use (&$i) {
                $i += 1;
            }
        ]));
        do {
            //this some times will exist
            $fjob1 = $queue->fetch();
        } while ($fjob1 == false);
        $this->assertTrue($fjob1 instanceof \UrbanIndo\Yii2\Queue\Job);
        /* @var $fjob1 Job */
        $index = $fjob1->header[\UrbanIndo\Yii2\Queue\Queues\MultipleQueue::HEADER_MULTIPLE_QUEUE_INDEX];
        $this->assertContains($index, range(0, 3));
        $fjob1->runCallable();
        $this->assertEquals(1, $i);
        
        $queue->postToQueue(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use (&$i) {
                $i += 1;
            }
        ]), 3);
        
        do {
            //this some times will exist
            $fjob2 = $queue->fetch();
        } while ($fjob2 == false);
        $this->assertTrue($fjob2 instanceof \UrbanIndo\Yii2\Queue\Job);
        $index2 = $fjob2->header[\UrbanIndo\Yii2\Queue\Queues\MultipleQueue::HEADER_MULTIPLE_QUEUE_INDEX];
        $this->assertEquals(3, $index2);
        $fjob2->runCallable();
        $this->assertEquals(2, $i);
    }
}
