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
     * List events that handled by the behavior.
     * 
     * This has two formats. The first one is "index", 
     * 
     *     [self::EVENT_AFTER_SAVE, EVENT_AFTER_VALIDATE]]
     * 
     * and the second one is "key=>value". e.g. 
     * 
     *     [
     *         self::EVENT_AFTER_SAVE => 'deferAfterSave', 
     *         self::EVENT_AFTER_VALIDATE => 'deferAfterValidate'
     *     ]
     * 
     * For the first one, the object should implement DeferredEventInterface.
     * As for the second one, the handler will use the respective method of the
     * event.
     * 
     * e.g.
     * 
     *     [
     *         self::EVENT_AFTER_SAVE => 'deferAfterSave', 
     *         self::EVENT_AFTER_VALIDATE => 'deferAfterValidate'
     *     ]
     * 
     * the model should implement
     * 
     *     public function deferAfterSave(){
     *     }
     * 
     * Note that the method doesn't receive $event just like any event handler.
     * This is because the $event object can be too large for the queue.
     * Also note that object that run the method is a clone.
     * 
     * @var type 
     */
    public $events = [];

    /**
     * Whether each events has its own event handler in the owner.
     * @var boolean
     */
    protected $_hasEventHandlers = false;

    /**
     * Whether has serialized event.handler.
     * @var \SuperClosure\Serializer 
     */
    protected $_serializer;

    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array
     */
    public function events() {
        parent::events();
        if (!$this->_hasEventHandlers) {
            return array_fill_keys($this->events, 'postDeferredEvent');
        } else {
            return array_fill_keys(array_keys($this->events),
                    'postDeferredEvent');
        }
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
        $this->_hasEventHandlers = !\yii\helpers\ArrayHelper::isIndexed($this->events,
                        true);
        if ($this->_hasEventHandlers) {
            foreach ($this->events as $attr => $handler) {
                if (is_callable($handler)) {
                    if (!isset($this->_serializer)) {
                        $this->_serializer = new \SuperClosure\Serializer();
                    }
                    $this->events[$attr] = $this->_serializer->serialize($handler);
                }
            }
        }
    }

    /**
     * Call the behavior owner to handle the deferred event.
     * @param \yii\base\Event $event
     */
    public function postDeferredEvent($event) {
        $object = clone $this->owner;
        if (!$this->_hasEventHandlers && !$object instanceof DeferredEventInterface) {
            throw new Exception("Model is not instance of DeferredEventInterface");
        }
        $handlers = ($this->_hasEventHandlers) ? $this->events : false;
        $eventName = $event->name;
        if (isset($this->_serializer)) {
            $serializer = $this->_serializer;
        } else {
            $serializer = null;
        }
        $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($object, $eventName, $handlers, $serializer) {
                if ($handlers) {
                    $handler = $handlers[$eventName];
                    if ($serializer !== null) {
                        try {
                            $unserialized = $serializer->unserialize($handler);
                            $unserialized($object);
                        } catch (Exception $exc) {
                            return call_user_method($handler, $object);
                        }
                    } else {
                        return call_user_method($handler, $object);
                    }
                } else if ($object instanceof DeferredEventInterface) {
                    /* @var $object DeferredEventInterface */
                    return $object->handleDeferredEvent($eventName);
                } else {
                    throw new Exception("Model doesn't have handlers for the event or is not instance of DeferredEventInterface");
                }
            }
        ]));
    }

}
