<?php
/**
 * Event class file.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2016.01.16
 */

namespace UrbanIndo\Yii2\Queue;

/**
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2016.01.16
 */
class Event extends \yii\base\Event
{

    /**
     * @var Job
     */
    public $job;
    
    /**
     * The return value after a job is being executed.
     * @var mixed
     */
    public $returnValue;
    
    /**
     * Whether the next process should continue or not.
     * @var boolean
     */
    public $isValid = true;
}
