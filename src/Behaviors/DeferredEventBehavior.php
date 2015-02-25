<?php

/**
 * DeferredEventBehavior class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use Yii;
use Exception;

/**
 * DeferredEventBehavior post a deferred code on event call.
 * 
 * To use this, attach the behavior on the model, and implements the
 * DeferredEventInterface.
 * 
 * NOTE: Due to some limitation on the superclosure, the model shouldn't have
 * unserializable class instances such as PDO etc.
 * 
 * @property-read DeferredEventInterface $owner the owner of this behavior.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class DeferredEventBehavior extends \yii\base\Behavior {

    /**
     * The queue that post the deferred event.
     * @var \UrbanIndo\Yii2\Queue\Queue
     */
    public $queue = 'queue';

    /**
     * List events that
     * @var type 
     */
    public $events = [];

    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array
     */
    public function events() {
        parent::events();
        return array_fill_keys($this->events, 'postDeferredEvent');
    }

    /**
     * Initialize the queue.
     * @throws Exception
     */
    public function init() {
        parent::init();
        $queueName = $this->queue;
        $this->queue = Yii::$app->get($queueName);
        if (!$this->queue instanceof \UrbanIndo\Yii2\Queue\Queue) {
            throw new Exception("Can not found queue component named '{$queueName}'");
        }
    }

    /**
     * Call the behavior owner to handle the deferred event.
     * @param \yii\base\Event $event
     */
    public function postDeferredEvent($event) {
        $object = $this->owner;
        if (!$object instanceof DeferredEventInterface) {
            throw new Exception("Model is not instance of DeferredEventInterface");
        }
        $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($object, $event) {
                if (!$object instanceof DeferredEventInterface) {
                    throw new Exception("Model is not instance of DeferredEventInterface");
                }
                /* @var $object DeferredEventInterface */
                $object->handleDeferredEvent($event);
            }
        ]));
    }

}
