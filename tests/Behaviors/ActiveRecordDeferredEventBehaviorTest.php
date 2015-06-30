<?php

class ActiveRecordDeferredEventBehaviorTest extends PHPUnit_Framework_TestCase {
    
    protected function setUp() {
        Yii::$app->getDb()->createCommand()->createTable('test_active_record_deferred_event_behaviors', [
            'id' => 'pk',
            'name' => 'string',
        ])->execute();
        Yii::$app->queue->emptyQueue();
    }
    
    public function testEventHandler() {
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getQueueLength());
        $object1 = new TestActiveRecord();
        $this->assertTrue($object1 instanceof TestActiveRecord);
        $object1->id = 1;
        $object1->name = 'start';
        $object1->save();
        $this->assertEquals(1, $queue->getQueueLength());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getQueueLength());
        $queue->run($job);
        $sameObject1 = TestActiveRecord::findOne(1);
        $this->assertEquals('done', $sameObject1->name);
        //
        $object1->name = 'test';
        $object1->save(false);
        $this->assertEquals(1, $queue->getQueueLength());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getQueueLength());
        $queue->run($job);
        $sameObject1 = TestActiveRecord::findOne(1);
        $this->assertEquals('updated', $sameObject1->name);
        
        $object2 = new TestActiveRecord();
        $this->assertTrue($object2 instanceof TestActiveRecord);
        $object2->id = 2;
        $object2->name = 'start';
        $object2->scenario = 'test';
        $object2->save();
        $this->assertEquals(1, $queue->getQueueLength());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getQueueLength());
        $queue->run($job);
        $sameObject2 = TestActiveRecord::findOne(2);
        $this->assertEquals('test', $sameObject2->name);
        
    }
    
}

class TestActiveRecord extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'test_active_record_deferred_event_behaviors';
    }
    
    public function behaviors() {
        return [
            [
                'class' => UrbanIndo\Yii2\Queue\Behaviors\ActiveRecordDeferredEventBehavior::class,
                'events' => [
                    self::EVENT_AFTER_INSERT => 'deferAfterInsert',
                    self::EVENT_AFTER_UPDATE => 'deferAfterUpdate',
                    self::EVENT_AFTER_DELETE => 'deferAfterDelete',
                ]
            ]
        ];
    }
    
    public function scenarios() {
        return [
            'default' => ['name', 'id'],
            'test' => ['name', 'id'],
        ];
    }

    public function deferAfterInsert() {
        $this->name = $this->scenario == 'test' ? 'test' : 'done';
        $this->updateAttributes(['name']);
    }
    
    public function deferAfterUpdate() {
        $this->name = 'updated';
        $this->updateAttributes(['name']);
    }
    
}