<?php

/**
 * ActiveRecordDeferredEventBehavior extends
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use yii\db\ActiveRecord;

/**
 * ActiveRecordDeferredEventBehavior is deferred event behavior handler for
 * ActiveRecord.
 * 
 * Due to SuperClosure limitation to serialize classes like PDO, this will
 * only pass the class, primary key, or attributes to the closure. The closure
 * then will operate on the object that refetched from the database from primary
 * key or object whose attribute repopulated in case of EVENT_AFTER_DELETE.
 *  
 * @property-read ActiveRecord $owner the owner.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class ActiveRecordDeferredEventBehavior extends DeferredEventBehavior {

    public $events = [
        ActiveRecord::EVENT_AFTER_INSERT,
        ActiveRecord::EVENT_AFTER_UPDATE,
        ActiveRecord::EVENT_AFTER_DELETE,
    ];

    /**
     * Default events that usually use deferred.
     * @return array
     */
    public static function getDefaultEvents() {
        return [
            ActiveRecord::EVENT_AFTER_INSERT,
            ActiveRecord::EVENT_AFTER_UPDATE,
            ActiveRecord::EVENT_AFTER_DELETE,
        ];
    }

    /**
     * Call the behavior owner to handle the deferred event.
     * 
     * Since there is a limitation on the SuperClosure on PDO, the closure will
     * operate the object that is re-fetched from the database using primary key.
     * In the case of the after delete, since the row is already deleted from
     * the table, the closure will operate from the object whose attributes 
     * @param \yii\base\Event $event
     */
    public function postDeferredEvent($event) {
        $class = get_class($this->owner);
        $eventName = $event->name;
        $handlers = ($this->_hasEventHandlers) ? $this->events : false;
        if (isset($this->_serializer)) {
            $serializer = $this->_serializer;
        } else {
            $serializer = null;
        }
        $scenario = $this->owner->scenario;
        if ($eventName == ActiveRecord::EVENT_AFTER_DELETE) {
            $attributes = $this->owner->getAttributes();
            $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
                'route' => function() use ($class, $attributes, $handlers, $eventName, $serializer, $scenario) {
                    $object = \Yii::createObject($class);
                    /* @var $object ActiveRecord */
                    $object->scenario = $scenario;
                    $object->setAttributes($attributes, false);
                    if ($handlers) {
                        $handler = $handlers[$eventName];
                        if ($serializer !== null) {
                            try {
                                $unserialized = $serializer->unserialize($handler);
                                $unserialized($object);
                            } catch (Exception $exc) {
                                return call_user_func([$object, $handler]);
                            }
                        } else {
                            return call_user_func([$object, $handler]);
                        }
                    } else if ($object instanceof DeferredEventInterface) {
                        /* @var $object DeferredEventInterface */
                        return $object->handleDeferredEvent($eventName);
                    } else {
                        throw new Exception("Model is not instance of DeferredEventInterface");
                    }
                }
            ]));
        } else {
            $pk = $this->owner->getPrimaryKey();
            $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
                'route' => function() use ($class, $pk, $handlers, $eventName, $serializer, $scenario) {
                    $object = $class::findOne($pk);
                    $object->scenario = $scenario;
                    if ($object === null) {
                        throw new Exception("Model is not found");
                    }
                    if ($handlers) {
                        $handler = $handlers[$eventName];
                        if ($serializer !== null) {
                            try {
                                $unserialized = $serializer->unserialize($handler);
                                $unserialized($object);
                            } catch (Exception $exc) {
                                return call_user_func([$object, $handler]);
                            }
                        } else {
                            return call_user_func([$object, $handler]);
                        }
                    } else if ($object instanceof DeferredEventInterface) {
                        /* @var $object DeferredEventInterface */
                        return $object->handleDeferredEvent($eventName);
                    } else {
                        throw new Exception("Model is not instance of DeferredEventInterface");
                    }
                }
            ]));
        }
    }

}
