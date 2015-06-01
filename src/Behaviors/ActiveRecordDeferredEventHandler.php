<?php

/**
 * DeferredActiveRecordEventHandler class file.
 * @author Petra Barus <petra.barus@gmail.com>
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use yii\db\ActiveRecord;

/**
 * DeferredActiveRecordEventHandler is deferred event behavior handler for
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
 */
abstract class ActiveRecordDeferredEventHandler extends DeferredEventHandler {

    public function deferEvent($event) {
        $class = get_class($this->owner);
        $pk = $this->owner->getPrimaryKey();
        $attributes = $this->owner->getAttributes();
        $eventName = $event->name;
        
        $queue = $this->queue;
        $handler = clone $this;
        $handler->queue = null;
        $handler->owner = null;
        /* @var $queue Queue */
        $queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($class, $pk, $attributes, $handler, $eventName) {
                if ($eventName == ActiveRecord::EVENT_AFTER_DELETE) {
                    $object = \Yii::createObject($class);
                    /* @var $object ActiveRecord */
                    $object->setAttributes($attributes, false);
                } else {
                    $object = $class::findOne($pk);
                    if ($object === null) {
                        throw new Exception("Model is not found");
                    }
                }
                $handler->handleEvent($object);
            }
        ]));
    }
}
