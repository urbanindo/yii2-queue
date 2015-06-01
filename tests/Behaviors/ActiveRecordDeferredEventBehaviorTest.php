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
        $object = new TestActiveRecord();
        $this->assertTrue($object instanceof TestActiveRecord);
        $object->id = 1;
        $object->name = 'start';
        $object->save();
        $this->assertEquals(1, $queue->getQueueLength());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getQueueLength());
        $queue->run($job);
        $sameObject = TestActiveRecord::findOne(1);
        $this->assertEquals('done', $sameObject->name);
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
                ]
            ]
        ];
    }
    
    public function deferAfterInsert() {
        $this->name = 'done';
        $this->update(false);
    }
    
}