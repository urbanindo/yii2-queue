<?php
/**
 * DeferredEventRoutingBehavior extends
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use yii\db\ActiveRecord;
use UrbanIndo\Yii2\Queue\Queue;

/**
 * DeferredEventRoutingBehavior provides matching between controller in
 * task worker with the appropriate event.
 *
 * @property-read ActiveRecord $owner the owner.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class DeferredEventRoutingBehavior extends \yii\base\Behavior
{
    
    /**
     * The queue that post the deferred event.
     * @var string|array|Queue
     */
    public $queue = 'queue';

    /**
     * List events that handler and the appropriate routing. The routing can be
     * generated via callable or array.
     *
     * e.g.
     *
     * [
     *         self::EVENT_AFTER_SAVE => ['test/index'],
     *         self::EVENT_AFTER_VALIDATE => ['test/index']
     * ]
     *
     * or
     *
     * [
     *         self::EVENT_AFTER_SAVE => function($model) {
     *              return ['test/index', 'id' => $model->id];
     *         }
     *         self::EVENT_AFTER_VALIDATE => function($model) {
     *              return ['test/index', 'id' => $model->id];
     *         }
     * ]
     *
     * @var array
     */
    public $events = [];
    
    /**
     * Initialize the queue.
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->queue = \yii\di\Instance::ensure($this->queue, Queue::className());
    }
    
    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array
     */
    public function events()
    {
        parent::events();
        return array_fill_keys(array_keys($this->events), 'routeEvent');
    }
    
    /**
     * @param \yii\base\Event $event The event to handle.
     * @return void
     */
    public function routeEvent(\yii\base\Event $event)
    {
        $eventName = $event->name;
        $handler = $this->events[$eventName];
        if (is_callable($handler)) {
            $handler = call_user_func($handler, $this->owner);
        }
        $route = $handler[0];
        unset($handler[0]);
        $data = $handler;
        $this->queue->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => $route,
            'data' => $data
        ]));
    }
}
