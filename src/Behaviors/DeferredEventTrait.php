<?php

/**
 * ActiveRecordDeferredEventTrait trait
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.06.12
 */

namespace UrbanIndo\Yii2\Queue\Behaviors;

/**
 * ActiveRecordDeferredEventBehavior is deferred event function for active record.
 * 
 * Due to SuperClosure limitation to serialize classes like PDO, this will
 * only pass the class, primary key.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.06.12
 */
trait DeferredEventTrait {
    
    /**
     * @return \UrbanIndo\Yii2\Queue\Queue
     */
    public function getQueue() {
        return \Yii::$app->queue;
    }
    
      
    /**
     * Defer event 
     * 
     * To use this, attach the behavior and call
     *    
     *    $model->deferAction(function($model) {
     *          $model->doSomething();
     *    });
     * @param callable $callback
     */
    public function deferAction($callback) {
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
    
    private function serializeCallback($callback) {
        $serializer = new \SuperClosure\Serializer();
        $serialized = $serializer->serialize($callback);
        return [$serializer, $serialized];
    }
    
    private function deferActiveRecordAction($callback) {
        $class = get_class($this);
        $pk = $this->getPrimaryKey();
        list($serializer, $serialized) = $this->serializeCallback($callback);
        return new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($class, $pk, $serialized, $serializer) {
                $model = $class::findOne($pk);
                $unserialized = $serializer->unserialize($serialized);
                call_user_func($unserialized, $model);
            }
        ]);
    }
    
    private function deferModelAction($callback) {
        $class = get_class($this);
        $attributes = $this->getAttributes();
        list($serializer, $serialized) = $this->serializeCallback($callback);
        return new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($class, $attributes, $serialized, $serializer) {
                $model = new $class;
                $model->setAttributes($attributes, false);
                $unserialized = $serializer->unserialize($serialized);
                call_user_func($unserialized, $model);
            }
        ]);
    }
    
    private function deferObject($callback) {
        $object = $this;
        list($serializer, $serialized) = $this->serializeCallback($callback);
        return new \UrbanIndo\Yii2\Queue\Job([
            'route' => function() use ($object, $serialized, $serializer) {
                $unserialized = $serializer->unserialize($serialized);
                call_user_func($unserialized, $object);
            }
        ]);
    }
  
}
