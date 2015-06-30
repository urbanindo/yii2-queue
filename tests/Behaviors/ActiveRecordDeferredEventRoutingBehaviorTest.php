<?php

class ActiveRecordDeferredEventRoutingBehaviorTest extends PHPUnit_Framework_TestCase {
    
    protected function setUp() {
        Yii::$app->getDb()->createCommand()->createTable('test_active_record_deferred_event_routing', [
            'id' => 'pk',
            'name' => 'string',
        ])->execute();
        Yii::$app->queue->emptyQueue();
    }
    
    public function testEventRouting() {
        
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getQueueLength());
        $model = new DeferredEventRoutingBehaviorTestActiveRecord();
        $model->id = 5;
        $model->save(false);
        $model->trigger('eventTest');
        $this->assertEquals(1, $queue->getQueueLength());
        
        $job = $queue->fetch();
        $this->assertEquals('test/index', $job->route);
        $this->assertFalse($job->isCallable());
        $this->assertEquals(0, $queue->getQueueLength());
        $this->assertEquals([
            'id' => 5,
        ], $job->data);
        $model->trigger('eventTest2');
        $this->assertEquals(1, $queue->getQueueLength());
        $job = $queue->fetch();
        $this->assertEquals('test/halo', $job->route);
        $this->assertFalse($job->isCallable());
        $this->assertEquals(0, $queue->getQueueLength());
        $this->assertEquals([
            'halo' => 5
        ], $job->data);
        
    }
}

class DeferredEventRoutingBehaviorTestActiveRecord extends \yii\db\ActiveRecord {
    
    const EVENT_TEST = 'eventTest';
    const EVENT_TEST2 = 'eventTest2';
    
    public static function tableName() {
        return 'test_active_record_deferred_event_routing';
    }
    
    public function behaviors() {
        return [
            [
                'class' => 'UrbanIndo\Yii2\Queue\Behaviors\ActiveRecordDeferredEventRoutingBehavior',
                'events' => [
                    self::EVENT_TEST => ['test/index'],
                    self::EVENT_TEST2 => function($model) {
                        return ['test/halo', 'halo' => $model->id];
                    }
                ]
            ]
        ];
    }
    
    
}