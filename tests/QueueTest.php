<?php

class QueueTest extends PHPUnit_Framework_TestCase {
    
    public function testQueueCatchingException() {
        $this->setExpectedException(\yii\base\Exception::class);
        $queue = Yii::createObject([
            'class' => '\UrbanIndo\Yii2\Queue\Queues\MemoryQueue'
        ]);
         
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
         $queue->post(new UrbanIndo\Yii2\Queue\Job([
             'route' => function() {
                throw new \Exception('Test');
             }
         ]));
         $this->assertEquals(1, $queue->getQueueLength());
         $job = $queue->fetch();
         $this->assertEquals(0, $queue->getQueueLength());
         $queue->run($job);
    }
}


