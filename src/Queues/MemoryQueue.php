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
class MemoryQueue extends \UrbanIndo\Yii2\Queue\Queue {
    
    /**
     * @var Job[]
     */
    private $_queues = [];

    /**
     * @param Job $job
     * @return boolean
     */
    public function delete(Job $job) {
        foreach($this->_queues as $key => $val) {
            if ($val->id == $job->id) {
                unset($this->_queues[$key]);
                $this->_queues = array_values($this->_queues);
                return true;
            }
        }
        return true;
    }

    /**
     * @return Job
     */
    public function fetch() {
        $job = array_pop($this->_queues);
        return $job;
    }

    /**
     * @param Job $job
     */
    public function post(Job &$job) {
        $job->id = mt_rand(0, 65535);
        $this->_queues[] = $job;
        return true;
    }
    
    /**
     * Returns the number of job.
     * @return integer
     */
    public function getQueueLength() {
        return count($this->_queues);
    }

    /**
     * 
     */
    public function emptyQueue() {
        $this->_queues = [];
    }
}
