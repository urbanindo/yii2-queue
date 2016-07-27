<?php
/**
 * WorkerController class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @author Adinata <mail.dieend@gmail.com>
 */

namespace UrbanIndo\Yii2\Queue\Web;

use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queue;
use Yii;

/**
 * WorkerController is a web controller class that fetches work from queue and
 * then run the job.
 * The motivation comes from the HHVM limitation for running PHP terminal script.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @author Adinata <mail.dieend@gmail.com>
 */
class WorkerController extends \yii\web\Controller
{
    
    /**
     * @var boolean
     */
    public $enableCsrfValidation = false;
    
    /**
     * The default queue component name or component configuration to use when
     * there is no queue param sent in the request.
     * @var string|array
     */
    public $defaultQueue = 'queue';
    
    /**
     * The key name of the request param that contains the name of the queue component
     * to use.
     * This will check the parameter of the POST first. If there is no value for the param,
     * then it will check the GET. If there is still no value, then the queue component
     * name will use the defaultQueue.
     * @var string
     */
    public $queueParamName = 'queue';

    /**
     * Run a task without going to queue.
     * 
     * This is useful to test the task controller. The `route` and `data` will be
     * retrieved from POST data.
     */
    public function actionRunTask()
    {
        $route = \Yii::$app->getRequest()->post('route');
        $data = \Yii::$app->getRequest()->post('data');
        $job = new \UrbanIndo\Yii2\Queue\Job([
            'route' => $route,
            'data' => \yii\helpers\Json::decode($data),
        ]);
        $queue = $this->getQueue();
        return $this->executeJob($queue, $job);
    }

    /**
     * Run a task by request.
     * @return mixed
     */
    public function actionRun()
    {
        $queue = $this->getQueue();
        $job = $queue->fetch();
        return $this->executeJob($queue, $job);
    }

    /**
     * @param Queue $queue Queue the job located.
     * @param Job   $job   Job to be executed.
     * @return array
     */
    protected function executeJob(Queue $queue, Job $job)
    {
        if ($job == false) {
            return ['status' => 'nojob'];
        }
        $start = time();
        $return = [
            'jobId' => $job->id,
            'route' => $job->isCallable() ? 'callable' : $job->route,
            'data' => $job->data,
            'time' => date('Y-m-d H:i:s', $start)
        ];
        try {
            ob_start();
            $queue->run($job);
            $output = ob_get_clean();
            $return2 = [
                'status' => 'success',
                'level' => 'info',
            ];
        } catch (\Exception $exc) {
            $output = ob_get_clean();
            Yii::$app->getResponse()->statusCode = 500;
            $return2 = [
                'status' => 'failed',
                'level' => 'error',
                'reason' => $exc->getMessage(),
                'trace' => $exc->getTraceAsString(),
            ];
        }
        $return3 = [
            'stdout' => $output,
            'duration' => time() - $start,
        ];
        return array_merge($return, $return2, $return3);
    }
    
    /**
     * Returns the queue component.
     * This will check if there is a queue component from.
     * 
     * @return \UrbanIndo\Yii2\Queue\Queue
     */
    protected function getQueue() {
        $queueComponent = $this->getComponentParamFromRequest();
        if (empty($queueComponent)) {
            $queueComponent = $this->defaultQueue;
        }
        return \yii\di\Instance::ensure($queueComponent, '\UrbanIndo\Yii2\Queue\Queue');
    }
    
    /**
     * @return string
     */
    private function getComponentParamFromRequest()
    {
        $request = Yii::$app->getRequest();
        if ($request->isPost) {
            return $request->post($this->queueParamName);
        } else {
            return $request->get($this->queueParamName);
        }
    }
}
