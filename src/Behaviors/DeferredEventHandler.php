<?php

/**
 * DeferredEventHandler class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 */
namespace UrbanIndo\Yii2\Queue\Behaviors;

use Yii;
use Exception;

/**
 * DeferredEventHandler handles the event inside the behavior instance, instead
 * of inside the model.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 */
abstract class DeferredEventHandler extends \yii\base\Behavior {
    
    /**
     * The queue that post the deferred event.
     * @var \UrbanIndo\Yii2\Queue\Queue
     */
    public $queue = 'queue';
    
    /**
     * Declares the events of the object that is being handled.
     * 
     * @var array
     */
    public $events = [];
    
    public function init() {
        $queueName = $this->queue;
        $this->queue = Yii::$app->get($queueName);
        if (!$this->queue instanceof \UrbanIndo\Yii2\Queue\Queue) {
            throw new Exception("Can not found queue component named '{$queueName}'");
        }
    }
    
    /**
     * @inheritdoc
     */
    public function events() {
        return array_fill_keys($this->events, 'deferEvent');
    }
    
    public function deferEvent($event) {
        $owner = clone $this->owner;
        $queue = $this->queue;
        $handler = clone $this;
        $handler->queue = null;
        $handler->owner = null;
        /* @var $queue Queue */
        $queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($owner, $handler) {
                return $handler->handleEvent($owner);
            }
        ]));
    }
    
    abstract public function handleEvent($owner);
    
}
