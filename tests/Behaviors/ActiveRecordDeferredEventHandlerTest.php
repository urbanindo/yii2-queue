<?php

class ActiveRecordDeferredEventHandlerTest extends PHPUnit_Framework_TestCase {
    
    public static function setUpBeforeClass() {
        Yii::$app->getDb()->createCommand()->createTable('deferred_active_record_event_handler_test', [
            'id' => 'pk',
            'name' => 'string',
        ])->execute();
        Yii::$app->queue->emptyQueue();
    }
    
    public function testEventHandlerInActiveRecord() {
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getQueueLength());
        $model = new ActiveRecordDeferredEventHandlerTestActiveRecord();
        $model->id = 1;
        $model->name = 'test';
        $model->save(false);
        $this->assertEquals(1, $queue->getQueueLength());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getQueueLength());
        $queue->run($job);
        $model->refresh();
        $this->assertEquals('done', $model->name);
    }
    
}

class ActiveRecordDeferredEventHandlerImpl extends \UrbanIndo\Yii2\Queue\Behaviors\ActiveRecordDeferredEventHandler {
    public function handleEvent($owner) {
        $owner->updateModel();
        return true;
    }
}

class ActiveRecordDeferredEventHandlerTestActiveRecord extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'deferred_active_record_event_handler_test';
    }
    
    public function behaviors() {
        return [
            [
                'class' => ActiveRecordDeferredEventHandlerImpl::class,
                'events' => [self::EVENT_AFTER_INSERT],
            ]
        ];
    }
    
    public function updateModel() {
        $this->name = 'done';
        $this->update(false);
    }
}