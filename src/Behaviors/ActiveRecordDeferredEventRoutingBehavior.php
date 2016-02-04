<?php
/**
 * ActiveRecordDeferredRoutingBehavior extends
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use yii\db\ActiveRecord;

/**
 * ActiveRecordDeferredRoutingBehavior provides matching between controller in
 * task worker with the appropriate event.
 *
 * @property-read ActiveRecord $owner the owner.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class ActiveRecordDeferredEventRoutingBehavior extends DeferredEventRoutingBehavior
{
    
    /**
     * The attribute name.
     * @var string
     */
    public $pkAttribute = 'id';
    
    /**
     * Whether to add the primary key to the data.
     * @var boolean
     */
    public $addPkToData = true;
    
    /**
     * @param \yii\base\Event $event The event to handle.
     * @return void
     */
    public function routeEvent(\yii\base\Event $event)
    {
        /* @var $owner ActiveRecord */
        
        $eventName = $event->name;
        $handler = $this->events[$eventName];
        if (is_callable($handler)) {
            $handler = call_user_func($handler, $this->owner);
        } else if ($this->addPkToData) {
            $pk = $this->owner->getPrimaryKey();
            if (is_array($pk)) {
                $handler = array_merge($handler, $pk);
            } else {
                $handler[$this->pkAttribute] = $pk;
            }
        }
        $route = $handler[0];
        unset($handler[0]);
        $handler['scenario'] = $this->owner->getScenario();
        $data = $handler;
        $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => $route,
            'data' => $data
        ]));
    }
}
