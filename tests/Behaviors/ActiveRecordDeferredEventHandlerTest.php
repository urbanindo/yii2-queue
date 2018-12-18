<?php

namespace UrbanIndo\Yii2\QueueTests\Behaviors;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Yii;

class ActiveRecordDeferredEventHandlerTest extends BaseTestCase
{
    
    public static function setUpBeforeClass()
    {
        Yii::$app->getDb()->createCommand()->createTable('deferred_active_record_event_handler_test', [
            'id' => 'pk',
            'name' => 'string',
        ])->execute();
        Yii::$app->queue->purge();
    }
    
    public function testEventHandlerInActiveRecord() {
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getSize());
        $object1 = new ActiveRecordDeferredEventHandlerTestActiveRecord();
        $object1->id = 1;
        $object1->name = 'test';
        $object1->save(false);
        $this->assertEquals(1, $queue->getSize());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getSize());
        $queue->run($job);
        $object1->refresh();
        $this->assertEquals('done', $object1->name);
        
        
        $this->assertEquals(0, $queue->getSize());
        $object2 = new ActiveRecordDeferredEventHandlerTestActiveRecord();
        $object2->id = 2;
        $object2->name = 'test';
        $object2->scenario = 'test';
        $object2->save(false);
        $this->assertEquals(1, $queue->getSize());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getSize());
        $queue->run($job);
        $object2->refresh();
        $this->assertEquals('test', $object2->name);
        
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

    public function scenarios() {
        return [
            'default' => ['name', 'id'],
            'test' => ['name', 'id'],
        ];
    }
    
    public function updateModel() {
        $this->name = $this->scenario == 'test' ? 'test' : 'done';
        $this->update(false);
    }
}