<?php
/**
 * Strategy class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Strategies;

use UrbanIndo\Yii2\Queue\Queues\MultipleQueue;
use UrbanIndo\Yii2\Queue\Job;

/**
 * Strategy is abstract class fo all strategy that is used for MultipleQueue.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
abstract class Strategy extends \yii\base\Object
{

    /**
     * Stores the queue.
     * @var \UrbanIndo\Yii2\Queue\MultipleQueue
     */
    protected $_queue;

    /**
     * Sets the queue.
     * @param MultipleQueue $queue The queue.
     * @return void
     */
    public function setQueue(MultipleQueue $queue)
    {
        $this->_queue = $queue;
    }

    /**
     * Implement this for the strategy of getting job from the queue.
     * @return mixed tuple of job and the queue index.
     */
    abstract protected function getJobFromQueues();

    /**
     * Returns the job.
     * @return Job|boolean The job or false if not found.
     */
    public function fetch()
    {
        $return = $this->getJobFromQueues();
        if ($return === false) {
            return false;
        }
        list($job, $index) = $return;
        /* @var $job Job */
        $job->header[MultipleQueue::HEADER_MULTIPLE_QUEUE_INDEX] = $index;
        return $job;
    }

    /**
     * Delete the job from the queue.
     *
     * @param Job $job The job.
     * @return boolean whether the operation succeed.
     */
    public function delete(Job $job)
    {
        $index = \yii\helpers\ArrayHelper::getValue(
            $job->header,
            MultipleQueue::HEADER_MULTIPLE_QUEUE_INDEX,
            null
        );
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
