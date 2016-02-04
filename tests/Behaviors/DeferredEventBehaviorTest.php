<?php


class DeferredEventBehaviorTest extends PHPUnit_Framework_TestCase  {
    
    protected function setUp() {
        Yii::$app->getDb()->createCommand()->createTable('test_deferred_event_behaviors', [
            'id' => 'pk',
            'name' => 'string',
        ])->execute();
        Yii::$app->queue->purge();
    }
    
    public function testEventHandler() {
        $queue = Yii::$app->queue;
        /* @var $queue \UrbanIndo\Yii2\Queue\Queues\MemoryQueue */
        $this->assertEquals(0, $queue->getSize());
        
        $model = new TestModel();
        $model->recordId = 1;
        $model->createRecord();
        $model->triggerEvent();
        
        $this->assertEquals(1, $queue->getSize());
        $job = $queue->fetch();
        $this->assertEquals(0, $queue->getSize());
        $queue->run($job);
        
        $sameModel = DeferredEventBehaviorTestActiveRecord::findOne($model->recordId);
        $this->assertEquals('done', $sameModel->name);
    }
    
}

class TestModel extends \yii\base\Model {
    
    const EVENT_TEST = 'eventTest';
    
    public $recordId;
    
    public function behaviors() {
        return [
            [
                'class' => \UrbanIndo\Yii2\Queue\Behaviors\DeferredEventBehavior::class,
                'events' => [
                    self::EVENT_TEST => 'deferEvent',
                ]
            ]
        ];
    }
    
    public function createRecord() {
        //See the execution via database.
        $model = new DeferredEventBehaviorTestActiveRecord();
        $model->id = $this->recordId;
        $model->name = 'test';
        $model->save(false);
    }
    
    public function triggerEvent() {
        $this->trigger(self::EVENT_TEST);
    }
    
    public function deferEvent() {
        $model = DeferredEventBehaviorTestActiveRecord::findOne($this->recordId);
        $model->name = 'done';
        $model->save(false);
    }
    
}

class DeferredEventBehaviorTestActiveRecord extends \yii\db\ActiveRecord {
    
    public static function tableName() {
        return 'test_deferred_event_behaviors';
    }
}