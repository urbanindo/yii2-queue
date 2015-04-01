<?php

namespace UrbanIndo\Yii2\Queue;

/**
 * QueueController is a web controller to post job via url.
 *
 * @author adinata
 */
class QueueController extends \yii\web\Controller{
    public $enableCsrfValidation = false;
    public $queueComponent = 'queue';
    
    /**
     * 
     * @return type
     */
    public function actionPost() {
        $route = \Yii::$app->getRequest()->post('route');
        $data = \Yii::$app->getRequest()->post('data');
        if (is_string($data)) {
            $data = \yii\helpers\Json::decode($data);
        } else {
            $data = [];
        }
        /* @var $queue Queue */
        $queue = \Yii::$app->get($this->queueComponent);
        
        \Yii::$app->getResponse()->format = 'json';
        if ($queue->postJob(($job = new Job([
            'route' => $route,
            'data' => $data
        ])))) {
            return ['status' => 'okay', 'jobId' => $job->id];
        } else {
            return ['status' => 'fail'];
        }
        
        
    }
}
