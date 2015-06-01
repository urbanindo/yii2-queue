<?php

/**
 * Queue class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue;

/**
 * Queue provides basic functionality for queue provider.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */
abstract class Queue extends \yii\base\Component {

    /**
     * The module where the task is located.
     * 
     * To add the module, create a new module in the config
     * e.g. create a module named 'task'.
     * 
     *   'modules' => [
     *       'task' => [
     *          'class' => 'app\modules\task\Module',
     *       ]
     *   ]
     * 
     * and then add the module to the queue config.
     * 
     *    'components' => [
     *       'queue' => [
     *          'module' => 'task'
     *       ]
     *    ]
     * @var \yii\base\Module
     */
    public $module;

    /**
     * Initializes the module.
     */
    public function init() {
        parent::init();
        $this->module = \Yii::$app->getModule($this->module);
    }

    /**
     * Post new job to the queue.
     * @param Job $job the job.
     * @return boolean whether operation succeed.
     */
    public abstract function post(Job &$job);

    /**
     * Return next job from the queue.
     * @return Job
     */
    public abstract function fetch();

    /**
     * Run the job.
     * 
     * @param Job $job
     */
    public function run(Job $job) {
        if ($job->isCallable()) {
            $retval = $job->runCallable();
        } else {
            try {
                $retval = $this->module->runAction($job->route, $job->data);
            } catch (\Exception $e) {
                throw new \yii\base\Exception("No route detected for {$job->route}",500, $e);
            }
        }
        if ($retval !== false) {
            $this->delete($job);
        }
    }

    /**
     * Delete the job.
     * @param Job $job
     * @return boolean whether the operation succeed.
     */
    public abstract function delete(Job $job);

    /**
     * Deserialize job to be executed.
     * 
     * @param string $json the json string
     * @return \UrbanIndo\Yii2\Queue\Job the job
     * @throws \yii\base\Exception if there is no route detected.
     */
    protected function deserialize($json) {
        $message = \yii\helpers\Json::decode($json);
        if (!isset($message['route'])) {
            throw new \yii\base\Exception('No route detected');
        }
        $route = $message['route'];
        if (isset($message['type']) && $message['type'] == Job::TYPE_CALLABLE) {
            $type = Job::TYPE_CALLABLE;
            $serializer = new \SuperClosure\Serializer();
            $route = $serializer->unserialize($route);
        } else {
            $type = Job::TYPE_REGULAR;
        }
        $data = \yii\helpers\ArrayHelper::getValue($message, 'data', []);
        return new Job([
            'route' => $route,
            'data' => $data,
        ]);
    }

    /**
     * Pack job so that it can be send.
     * 
     * @param Job $job the job.
     * @return string JSON string.
     */
    protected function serialize(Job $job) {
        $return = [];
        if ($job->isCallable()) {
            $return['type'] = Job::TYPE_CALLABLE;
            $serializer = new \SuperClosure\Serializer();
            $return['route'] = $serializer->serialize($job->route);
        } else {
            $return['type'] = Job::TYPE_REGULAR;
            $return['route'] = $job->route;
        }
        $return['data'] = $job->data;
        return \yii\helpers\Json::encode($return);
    }

}
