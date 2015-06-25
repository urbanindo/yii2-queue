<?php

/**
 * Strategy class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Strategies;

/**
 * Strategy is abstract class fo all strategy that is used for MultipleQueue.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
abstract class Strategy extends \yii\base\Object {

    const HEADER_MULTIPLE_QUEUE_INDEX = 'MultipleQueueIndex';

    /**
     * Stores the queue.
     * @var \UrbanIndo\Yii2\Queue\MultipleQueue
     */
    protected $_queue;

    /**
     * Sets the queue.
     * @param \UrbanIndo\Yii2\Queue\MultipleQueue $queue
     */
    public function setQueue(\UrbanIndo\Yii2\Queue\MultipleQueue $queue) {
        $this->_queue = $queue;
    }

    /**
     * Implement this for the strategy of getting job from the queue.
     * @return mixed tuple of job and the queue index.
     */
    protected abstract function getJobFromQueues();

    /**
     * Returns the job.
     * @return \UrbanIndo\Yii2\Queue\Job|boolean the job or false if not found.
     */
    public function fetch() {
        $return = $this->getJobFromQueues();
        if ($return === false) {
            return false;
        }
        list($job, $index) = $return;
        /* @var $job \UrbanIndo\Yii2\Queue\Job */
        $job->header[self::HEADER_MULTIPLE_QUEUE_INDEX] = $index;
        return $job;
    }

    /**
     * Delete the job from the queue.
     * 
     * @param \UrbanIndo\Yii2\Queue\Job $job
     * @return boolean whether the operation succeed.
     */
    public function delete(\UrbanIndo\Yii2\Queue\Job $job) {
        $index = \yii\helpers\ArrayHelper::getValue($job->header,
                        self::HEADER_MULTIPLE_QUEUE_INDEX, null);
        if (!isset($index)) {
            return false;
        }
        $queue = $this->_queue->getQueue($index);
        if (!isset($index)) {
            return false;
        }
        return $queue->delete($job);
    }

}
