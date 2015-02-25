<?php

/**
 * Job class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue;

/**
 * Job is model for a job message.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */
class Job extends \yii\base\Object {

    const TYPE_REGULAR = 0;
    const TYPE_CALLABLE = 1;

    /**
     * The ID of the message. This should be set on the job receive.
     * @var integer
     */
    public $id;

    /**
     * Stores the header.
     * This can be different for each queue provider.
     * 
     * @var header
     */
    public $header = [];

    /**
     * The route for the job.
     * This can either be string that represents the controller/action or
     * a anonymous function that will be executed.
     * 
     * @var mixed 
     */
    public $route;
    public $data = [];

    /**
     * whether the task is callable.
     * @return boolean
     */
    public function isCallable() {
        return is_callable($this->route);
    }

    /**
     * Run the callable task.
     * 
     * The callable should return true if the job is going to be deleted from
     * queue.
     * 
     * @return boolean
     */
    public function runCallable() {
        $return = call_user_func_array($this->route, $this->data);
        return $return !== false;
    }

}
