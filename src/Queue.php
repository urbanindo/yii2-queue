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
    
    const SERIALIZER_JSON = 'json';
    
    const SERIALIZER_PHP = 'php';
    
    /**
     * Choose the serializer.
     * @var string
     */
    public $serializer = 'json';

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
        \Yii::info('Running job', 'yii2queue');
        try {
            if ($job->isCallable()) {
                $retval = $job->runCallable();
            } else {
                $retval = $this->module->runAction($job->route, $job->data);
            }
        } catch (\Exception $e) {
            $route = $this->serialize($job);
            \Yii::error("Fatal Error: Error running route {$route}. Message: {$e->getMessage()}", 'yii2queue');
            throw new \yii\base\Exception("Error running route {$route}. Message: {$e->getMessage()}. File: {$e->getFile()}[{$e->getLine()}]. Stack Trace: {$e->getTraceAsString()}", 500);
        }
        if ($retval !== false) {
            \Yii::info('Deleting job', 'yii2queue');
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
     * @param string $message the json string
     * @return \UrbanIndo\Yii2\Queue\Job the job
     * @throws \yii\base\Exception if there is no route detected.
     */
    protected function deserialize($message) {
        $job = $this->deserializeMessage($message);
        if (!isset($job['route'])) {
            throw new \yii\base\Exception('No route detected');
        }
        $route = $job['route'];
        if (isset($job['type']) && $job['type'] == Job::TYPE_CALLABLE) {
            $type = Job::TYPE_CALLABLE;
            $serializer = new \SuperClosure\Serializer();
            $route = $serializer->unserialize($route);
        } else {
            $type = Job::TYPE_REGULAR;
        }
        $data = \yii\helpers\ArrayHelper::getValue($job, 'data', []);
        return new Job([
            'route' => $route,
            'data' => $data,
        ]);
    }
    
   /**
     * 
     * @param type $array
     * @return type
     */
    protected function deserializeMessage($array) {
        switch($this->serializer) {
            case self::SERIALIZER_PHP:
                $data = unserialize($array);
                break;
            case self::SERIALIZER_JSON:
                $data = \yii\helpers\Json::decode($array);
                break;
        }
        if (empty($data)) {
            throw new Exception('Can not deserialize message');
        }
        return $data;
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
        return $this->serializeMessage($return);
    }

    /**
     * 
     * @param type $array
     * @return type
     */
    protected function serializeMessage($array) {
        switch($this->serializer) {
            case self::SERIALIZER_PHP:
                $data = serialize($array);
                break;
            case self::SERIALIZER_JSON:
                $data = \yii\helpers\Json::encode($array);
                break;
        }
        if (empty($data)) {
            throw new Exception('Can not deserialize message');
        }
        return $data;
    }
}
