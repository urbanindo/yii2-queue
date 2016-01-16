<?php
/**
 * DeferredEventHandler class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use Yii;
use UrbanIndo\Yii2\Queue\Queue;

/**
 * DeferredEventHandler handles the event inside the behavior instance, instead
 * of inside the model.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 */
abstract class DeferredEventHandler extends \yii\base\Behavior
{
    
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
    
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->queue = \yii\di\Instance::ensure($this->queue, Queue::className());
    }
    
    /**
     * @inheritdoc
     * @return array
     */
    public function events()
    {
        return array_fill_keys($this->events, 'deferEvent');
    }
    
    /**
     * @param \yii\base\Event $event The event to handle.
     * @return array
     */
    public function deferEvent(\yii\base\Event $event)
    {
        $event; //unused
        $owner = clone $this->owner;
        $queue = $this->queue;
        $handler = clone $this;
        $handler->queue = null;
        $handler->owner = null;
        /* @var $queue Queue */
        $queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function () use ($owner, $handler) {
                return $handler->handleEvent($owner);
            }
        ]));
    }
    
    /**
     * Handle event.
     * @param mixed $owner The owner of the behavior.
     * @return void
     */
    abstract public function handleEvent($owner);
}
