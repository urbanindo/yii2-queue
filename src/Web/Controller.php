<?php
/**
 * Controller class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 */

namespace UrbanIndo\Yii2\Queue\Web;

use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queue;
use UrbanIndo\Yii2\Queue\Queues\MultipleQueue;

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
class Controller extends \yii\web\Controller
{

    /**
     * Disable class file.
     * @var boolean
     */
    public $enableCsrfValidation = false;
    
    /**
     * The queue to process.
     * @var string|array|\UrbanIndo\Yii2\Queue\Queue
     */
    public $queue = 'queue';
    
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        \Yii::$app->getResponse()->format = 'json';
        $this->queue = \yii\di\Instance::ensure($this->queue, Queue::className());
    }
    
    /**
     * @return Job
     * @throws \yii\web\ServerErrorHttpException When malformed request.
     */
    private function createJobFromRequest()
    {
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
     * @throws \yii\web\ServerErrorHttpException When failed to post.
     */
    public function actionPost()
    {
        $job = $this->createJobFromRequest();
        /* @var $queue \UrbanIndo\Yii2\Queue\Queue */
        if ($this->queue->post($job)) {
            return ['status' => 'okay', 'jobId' => $job->id];
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to post job');
        }
    }
    
    /**
     * Endpoint to post a job to multiple queue.
     * @return mixed
     * @throws \InvalidArgumentException Queue has to be instance of \UrbanIndo\Yii2\Queue\MultipleQueue.
     * @throws \yii\web\ServerErrorHttpException When failed to post the job.
     */
    public function actionPostToQueue()
    {
        $job = $this->createJobFromRequest();
        $index = \Yii::$app->getRequest()->post('index');
        if (!isset($index)) {
            throw new \InvalidArgumentException('Index needed');
        }
        $queue = $this->queue;
        if (!$queue instanceof MultipleQueue) {
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
