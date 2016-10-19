<?php
/**
 * File DummyQueue.php
 * @author adinata@urbanindo.com
 * @since 2016.07.30
 */

namespace UrbanIndo\Yii2\Queue\Queues;

use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queue;
use yii\base\NotSupportedException;

/**
 * Class DummyQueue
 * This class is used for running job created manually without queue.
 * @package UrbanIndo\Yii2\Queue\Queues
 */
class DummyQueue extends Queue
{

    /**
     * Post new job to the queue.
     *
     * @param Job $job The job.
     * @return boolean Whether operation succeed.
     */
    protected function postJob(Job $job)
    {
        $this->run($job);
        return true;
    }

    /**
     * Return next job from the queue. Override this for queue implementation.
     * @return Job|boolean the job or false if not found.
     */
    protected function fetchJob()
    {
        return false;
    }

    /**
     * Delete the job. Override this for the queue implementation.
     *
     * @param Job $job The job to delete.
     * @return boolean whether the operation succeed.
     */
    protected function deleteJob(Job $job)
    {
        return true;
    }

    /**
     * Release the job. Override this for the queue implementation.
     *
     * @param Job $job The job to release.
     * @return boolean whether the operation succeed.
     */
    protected function releaseJob(Job $job)
    {
        return true;
    }

    /**
     * Returns the number of queue size.
     * @return integer
     */
    public function getSize()
    {
        return 0;
    }

    /**
     * Purge the whole queue.
     * @return boolean
     */
    public function purge()
    {
        return true;
    }
}
