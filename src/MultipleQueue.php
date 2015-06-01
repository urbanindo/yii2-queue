<?php

/**
 * MultipleQueue class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue;

use UrbanIndo\Yii2\Queue\Job;

/**
 * MultipleQueue is a queue abstraction that handles multiple queue at once.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class MultipleQueue extends Queue {

    /**
     * Stores the queue.
     * @var Queue[]
     */
    public $queues = [];

    /**
     * The job fetching strategy.
     * @var Strategies\Strategy
     */
    public $strategy;

    /**
     * Initialize the queue.
     */
    public function init() {
        parent::init();
        $queueObjects = [];
        foreach ($this->queues as $id => $queue) {
            $queueObjects[$id] = \Yii::createObject($queue);
        }
        $this->queues = $queueObjects;
        if (!isset($this->strategy)) {
            $this->strategy = [
                'class' => Strategies\RandomStrategy::class,
            ];
        }
        $this->strategy = \Yii::createObject($this->strategy);
    }

    /**
     * @param integer $index
     * @return Queue|null the queue or null if not exists.
     */
    public function getQueue($index) {
        return \yii\helpers\ArrayHelper::getValue($this->queues, $index);
    }

    /**
     * Delete the job.
     * @param Job $job
     * @return boolean whether the operation succeed.
     */
    public function delete(Job $job) {
        return $this->strategy->delete($job);
    }

    /**
     * Return next job from the queue.
     * @return Job
     */
    public function fetch() {
        return $this->strategy->fetch();
    }

    /**
     * Post new job to the queue.
     * @param Job $job the job.
     * @return boolean whether operation succeed.
     */
    public function post(Job &$job) {
        return $this->postToQueue($job, 0);
    }

    /**
     * Post new job to a specific queue.
     * @param Job $job the job.
     * @param integer $index the queue index.
     * @return boolean whether operation succeed.
     */
    public function postToQueue(Job &$job, $index) {
        $queue = $this->getQueue($index);
        if ($queue === null) {
            return false;
        }
        return $queue->post($job);
    }

}
