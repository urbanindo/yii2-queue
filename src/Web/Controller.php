<?php

namespace UrbanIndo\Yii2\Queue\Web;

use UrbanIndo\Yii2\Queue\Job;

/**
 * QueueController is a web controller to post job via url.
 * 
 * To use this use a controller map.
 * 
 *    'controllerMap' => [
 *         'queue' => 'UrbanIndo\Yii2\Queue\Web\Controller',
 *    ]
 * 
 * And then send a POST to the endpoint
 * 
 *   curl -XPOST http://example.com/queue --data route=test/test --data={"data": "data"}
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @author Adinata <mail.dieend@gmail.com>
 */
class Controller extends \yii\web\Controller {

    public $enableCsrfValidation = false;
    public $queueComponent = 'queue';
    
    public function init() {
        parent::init();
        \Yii::$app->getResponse()->format = 'json';
    }
    
    /**
     * @return Job
     */
    private function createJobFromRequest() {
        $route = \Yii::$app->getRequest()->post('route');
        $data = \Yii::$app->getRequest()->post('data', []);

        if (empty($route)) {
            throw new \yii\web\ServerErrorHttpException('Failed to post job');
        }

        if (is_string($data)) {
            $data = \yii\helpers\Json::decode($data);
        }
        return new Job([
            'route' => $route,
            'data' => $data
        ]);
    }

    /**
     * Endpoint to post a job to queue.
     * @return mixed
     */
    public function actionPost() {
        $job = $this->createJobFromRequest();
        /* @var $queue \UrbanIndo\Yii2\Queue\Queue */
        $queue = \Yii::$app->get($this->queueComponent);
        if ($queue->post($job)) {
            return ['status' => 'okay', 'jobId' => $job->id];
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to post job');
        }
    }
    
    /**
     * Endpoint to post a job to multiple queue.
     * @return mixed
     */
    public function actionPostToQueue() {
        $job = $this->createJobFromRequest();
        $index = \Yii::$app->getRequest()->post('index');
        if (!isset($index)) {
            throw new \InvalidArgumentException('Index needed');
        }
        $queue = \Yii::$app->get($this->queueComponent);
        if (!$queue instanceof \UrbanIndo\Yii2\Queue\MultipleQueue) {
            throw new \InvalidArgumentException('Queue is not instance of \UrbanIndo\Yii2\Queue\MultipleQueue');
        }
        /* @var $queue MultipleQueue */
        
        if ($queue->postToQueue($job, $index)) {
            return ['status' => 'okay', 'jobId' => $job->id];
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to post job');
        }
    }

}
