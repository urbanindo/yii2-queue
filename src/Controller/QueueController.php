<?php

/**
 * QueueController class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue\Controller;

/**
 * QueueController handles console command for running the queue.
 * 
 * To use the controller, update the controllerMap.
 * 
 * return [
 *    // ...
 *     'controllerMap' => [
 *         'queue' => 'UrbanIndo\Yii2\Queue\Controller\QueueController'
 *     ],
 * ];
 * 
 * To run
 * 
 * yii queue
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */
class QueueController extends \yii\console\Controller {

    const DEFAULT_QUEUE_COMPONENT = 'queueu';

    /**
     * The name of the queue.
     * @var string
     */
    public $queue;
    private $_queue;

    private function getQueue() {
        if (!isset($this->_queue)) {
            
        }
        return $this->_queue;
    }

    public function actionListen() {
        $this->stdout("Listening to queue...");
    }

    public function actionPost($route, $data = '{}') {
        $this->stdout("Posting job to queue...");
    }

    public function actionRunTask($route, $data = '{}') {
        $this->stdout("Running task queue...");
    }

    public function actionPeek($count = 1) {
        $this->stdout("Peeking queue...");
        for ($i = 0; $i < $count; $i++) {
            
        }
    }

    public function actionPop($count = 1) {
        $this->stdout("Popping queue...");
        for ($i = 0; $i < $count; $i++) {
            
        }
    }

}
