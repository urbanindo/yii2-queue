<?php

/**
 * DeferredEventRoutingBehavior extends
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use Yii;
use yii\db\ActiveRecord;

/**
 * DeferredEventRoutingBehavior provides matching between controller in
 * task worker with the appropriate event.
 *  
 * @property-read ActiveRecord $owner the owner.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class DeferredEventRoutingBehavior extends \yii\base\Behavior {
    
    /**
     * The queue that post the deferred event.
     * @var \UrbanIndo\Yii2\Queue\Queue
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
     * @var type 
     */
    public $events = [];
    
    /**
     * Initialize the queue.
     * @throws \Exception
     */
    public function init() {
        parent::init();
        $queueName = $this->queue;
        $this->queue = Yii::$app->get($queueName);
        if (!$this->queue instanceof \UrbanIndo\Yii2\Queue\Queue) {
            throw new \Exception("Can not found queue component named '{$queueName}'");
        }
    }
    
    /**
     * Declares event handlers for the [[owner]]'s events.
     * @return array
     */
    public function events() {
        parent::events();
        return array_fill_keys(array_keys($this->events), 'routeEvent');
    }
    
    public function routeEvent($event) {
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
