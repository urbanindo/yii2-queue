<?php
/**
 * MemoryQueue class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.06.01
 */

namespace UrbanIndo\Yii2\Queue\Queues;

use UrbanIndo\Yii2\Queue\Job;

/**
 * MemoryQueue stores queue in the local variable.
 *
 * This will only work for one request.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.06.01
 */
class MemoryQueue extends \UrbanIndo\Yii2\Queue\Queue
{
    
    /**
     * @var Job[]
     */
    private $_jobs = [];

    /**
     * @param Job $job The job to delete.
     * @return boolean Whether the deletion succeed.
     */
    public function deleteJob(Job $job)
    {
        foreach ($this->_jobs as $key => $val) {
            if ($val->id == $job->id) {
                unset($this->_jobs[$key]);
                $this->_jobs = array_values($this->_jobs);
                return true;
            }
        }
        return true;
    }

    /**
     * @return Job The job fetched from queue.
     */
    public function fetchJob()
    {
        if ($this->getQueueLength() == 0) {
            return false;
        }
        $job = array_pop($this->_jobs);
        return $job;
    }

    /**
     * @param Job $job The job to be posted to the queueu.
     * @return boolean Whether the post succeed.
     */
    public function postJob(Job &$job)
    {
        $job->id = mt_rand(0, 65535);
        $this->_jobs[] = $job;
        return true;
    }
    
    /**
     * Returns the number of job.
     * @return integer
     */
    public function getQueueLength()
    {
        return count($this->_jobs);
    }

    /**
     * Empty the whole queue items.
     * @return void
     */
    public function emptyQueue()
    {
        $this->_jobs = [];
    }
    
    /**
     * Returns the jobs posted to the queue.
     * @return Job[]
     */
    public function getJobs()
    {
        return $this->_jobs;
    }
}
