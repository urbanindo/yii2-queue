<?php
/**
 * RandomStrategy class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Strategies;

use UrbanIndo\Yii2\Queue\Job;

/**
 * RandomStrategy provides random choosing of the queue for getting the job.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class RandomStrategy extends Strategy
{

    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        srand();
    }

    /**
     * The number of attempt before returning false.
     * @var integer
     */
    public $maxAttempt = 5;

    /**
     * Returns the job.
     * @return Job|boolean the job or false if not found.
     */
    protected function getJobFromQueues()
    {
        $attempt = 0;
        $count = count($this->_queue->queues);
        while ($attempt < $this->maxAttempt) {
            $index = rand(0, $count - 1);
            $queue = $this->_queue->getQueue($index);
            $job = $queue->fetch();
            if ($job !== false) {
                return [$job, $index];
            }
            $attempt++;
        }
        return false;
    }
}
