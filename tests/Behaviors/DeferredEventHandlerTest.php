<?php

class DeferredEventHandlerTest extends PHPUnit_Framework_TestCase {
    
    public static function setUpBeforeClass() {
        Yii::$app->getDb()->createCommand()->createTable('deferred_event_handler_test', [
            'id' => 'pk',
            'name' => 'string',
        ])->execute();
        Yii::$app->queue->purge();
    }
    
    public function testEventHandlerInSimpleComponent() {
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getSize());
        $component = new DeferredEventHandlerTestComponent();
        $component->recordId = 1;
        $component->triggerEvent();
        
        $model = DeferredEventHandlerTestActiveRecord::findOne($component->recordId);
        $this->assertNotNull($model);
        $this->assertEquals('test', $model->name);
        
        $this->assertEquals(1, $queue->getSize());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getSize());
        $queue->run($job);
        
        $model->refresh();
        $this->assertEquals('done', $model->name);
    }
    
    public function testEventHandlerInSimpleModel() {
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getSize());
        $model = new DeferredEventHandlerTestModel();
        $model->recordId = 2;
        $model->triggerEvent();
        
        $model = DeferredEventHandlerTestActiveRecord::findOne($model->recordId);
        $this->assertNotNull($model);
        $this->assertEquals('test', $model->name);
        
        $this->assertEquals(1, $queue->getSize());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getSize());
        $queue->run($job);
        
        $model->refresh();
        $this->assertEquals('done', $model->name);
    }
}

class DeferredEventHandlerImpl extends \UrbanIndo\Yii2\Queue\Behaviors\DeferredEventHandler {
    public function handleEvent($owner) {
        $owner->updateModel();
        return true;
    }
}

class DeferredEventHandlerTestComponent extends \yii\base\Component {
    
    const EVENT_TEST = 'eventTest';
    
    public $recordId;
    
    public function behaviors() {
        return [
            [
                'class' => DeferredEventHandlerImpl::class,
                'events' => [self::EVENT_TEST],
            ]
        ];
    }
    
    public function triggerEvent() {
        $model = new DeferredEventHandlerTestActiveRecord();
        $model->id = $this->recordId;
        $model->name = "test";
        $model->save(false);
        $this->trigger(self::EVENT_TEST);
    }
    
    public function updateModel() {
        $model = DeferredEventHandlerTestActiveRecord::findOne($this->recordId);
        $model->name = 'done';
        $model->save(false);
    }
    
}

class DeferredEventHandlerTestModel extends \yii\base\Model {
    
    const EVENT_TEST = 'eventTest';
    
    public $recordId;
    
    public function behaviors() {
        return [
            [
                'class' => DeferredEventHandlerImpl::class,
                'events' => [self::EVENT_TEST],
            ]
        ];
    }
    
    public function triggerEvent() {
        $model = new DeferredEventHandlerTestActiveRecord();
        $model->id = $this->recordId;
        $model->name = "test";
        $model->save(false);
        $this->trigger(self::EVENT_TEST);
    }
    
    public function updateModel() {
        $model = DeferredEventHandlerTestActiveRecord::findOne($this->recordId);
        $model->name = 'done';
        $model->save(false);
    }
    
}

class DeferredEventHandlerTestActiveRecord extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'deferred_event_handler_test';
    }
}