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
        $pk = $this->owner->getPrimaryKey();
        $attributes = $this->owner->getAttributes();
        $eventName = $event->name;
        $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($class, $pk, $attributes, $eventName) {
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
                if (!$object instanceof DeferredEventInterface) {
                    throw new Exception("Model is not instance of DeferredEventInterface");
                }
                /* @var $object DeferredEventInterface */
                return $object->handleDeferredEvent($eventName);
            }
        ]));
    }

}
