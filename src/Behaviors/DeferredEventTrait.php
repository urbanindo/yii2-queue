<?php
/**
 * ActiveRecordDeferredEventTrait trait
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.06.12
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queue;

/**
 * ActiveRecordDeferredEventBehavior is deferred event function for active record.
 *
 * Due to SuperClosure limitation to serialize classes like PDO, this will
 * only pass the class, primary key.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.06.12
 */
trait DeferredEventTrait
{
    
    /**
     * @return Queue
     */
    public function getQueue()
    {
        return \Yii::$app->queue;
    }
    
      
    /**
     * Defer event.
     *
     * To use this, attach the behavior and call
     *
     *    $model->deferAction(function($model) {
     *          $model->doSomething();
     *    });
     *
     * @param \Closure $callback The callback.
     * @return void
     */
    public function deferAction(\Closure $callback)
    {
        if ($this instanceof ActiveRecord) {
            $job = $this->deferActiveRecordAction($callback);
        } else if ($this instanceof \yii\base\Model) {
            $job = $this->deferModelAction($callback);
        } else {
            $job = $this->deferObjectAction($callback);
        }
        $queue = $this->getQueue();
        $queue->post($job);
    }
    
    /**
     * @param \Closure $callback The callback.
     * @return array
     */
    private function serializeCallback(\Closure $callback)
    {
        $serializer = new \SuperClosure\Serializer();
        $serialized = $serializer->serialize($callback);
        return [$serializer, $serialized];
    }
    
    /**
     * @param \Closure $callback The callback.
     * @return array
     */
    private function deferActiveRecordAction(\Closure $callback)
    {
        $class = get_class($this);
        $pk = $this->getPrimaryKey();
        list($serializer, $serialized) = $this->serializeCallback($callback);
        return new Job([
            'route' => function () use ($class, $pk, $serialized, $serializer) {
                $model = $class::findOne($pk);
                $unserialized = $serializer->unserialize($serialized);
                call_user_func($unserialized, $model);
            }
        ]);
    }
    
    /**
     * @param \Closure $callback The callback to defer.
     * @return Job
     */
    private function deferModelAction(\Closure $callback)
    {
        $class = get_class($this);
        $attributes = $this->getAttributes();
        list($serializer, $serialized) = $this->serializeCallback($callback);
        return new \UrbanIndo\Yii2\Queue\Job([
            'route' => function () use ($class, $attributes, $serialized, $serializer) {
                $model = new $class;
                $model->setAttributes($attributes, false);
                $unserialized = $serializer->unserialize($serialized);
                call_user_func($unserialized, $model);
            }
        ]);
    }
    
    /**
     * @param \Closure $callback The callback.
     * @return Job
     */
    private function deferObject(\Closure $callback)
    {
        $object = $this;
        list($serializer, $serialized) = $this->serializeCallback($callback);
        return new \UrbanIndo\Yii2\Queue\Job([
            'route' => function () use ($object, $serialized, $serializer) {
                $unserialized = $serializer->unserialize($serialized);
                call_user_func($unserialized, $object);
            }
        ]);
    }
}
