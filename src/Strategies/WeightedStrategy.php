<?php

/**
 * WeightedStrategy class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */

namespace UrbanIndo\Yii2\Queue\Strategies;

/**
 * WeightedStrategy that will put weight to the queues.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.25
 */
class WeightedStrategy extends Strategy {

    /**
     * List of weights.
     * 
     * The weight will be wielded to queue with the sampe index number. And the 
     * number of the weight should be the same with the number of the queue.
     * 
     * For example,
     * [10, 8, 5, 2]
     * 
     * means, if the queue 0 will have weight 10, 1 will have 8, and so on.
     * 
     * In other words, the weight will NOT be automatically sorted descending 
     * and NOT be sliced to number of queue.
     * 
     * @var array
     */
    public $weight = [];

    public function init() {
        parent::init();
//        sort($this->weight, SORT_DESC);
//        $this->weight = array_slice($this->weight, 0,
//                count($this->_queue->queues));
    }

    /**
     * Implement this for the strategy of getting job from the queue.
     * @return mixed tuple of job and the queue index.
     */
    protected function getJobFromQueues() {
        $index = self::weightedRandom($this->weight);
        $count = count($this->_queue->queues);
        while ($index < $count) {
            $queue = $this->_queue->getQueue($index);
            $job = $queue->fetch();
            if ($job !== false) {
                return [$job, $index];
            }
            //will continue fetching to the lower priority.
            $index++;
        }
        return false;
    }

    /**
     * Return weighted random.
     *
     * @param array $array array of value and weight.
     * @return string the value.
     */
    private static function weightedRandom($array) {
        $rand = mt_rand(1, (int) array_sum($array));
        foreach ($array as $key => $value) {
            $rand -= $value;
            if ($rand <= 0) {
                return $key;
            }
        }
    }

}
