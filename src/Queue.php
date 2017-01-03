<?php
/**
 * Queue class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue;

use Exception;

/**
 * Queue provides basic functionality for queue provider.
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */
abstract class Queue extends \yii\base\Component
{

    /**
     * Json serializer.
     */
    const SERIALIZER_JSON = 'json';
    
    /**
     * PHP serializer.
     */
    const SERIALIZER_PHP = 'php';

    /**
     * Event executed before a job is posted to the queue.
     */
    const EVENT_BEFORE_POST = 'beforePost';

    /**
     * Event executed before a job is posted to the queue.
     */
    const EVENT_AFTER_POST = 'afterPost';
    
    /**
     * Event executed before a job is being fetched from the queue.
     */
    const EVENT_BEFORE_FETCH = 'beforeFetch';
    
    /**
     * Event executed after a job is being fetched from the queue.
     */
    const EVENT_AFTER_FETCH = 'afterFetch';
    
    /**
     * Event executed before a job is being deleted from the queue.
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * Event executed after a job is being deleted from the queue.
     */
    const EVENT_AFTER_DELETE = 'afterDelete';
    
    /**
     * Event executed before a job is being released from the queue.
     */
    const EVENT_BEFORE_RELEASE = 'beforeRelease';

    /**
     * Event executed after a job is being released from the queue.
     */
    const EVENT_AFTER_RELEASE = 'afterRelease';
    
    /**
     * Event executed before a job is being executed.
     */
    const EVENT_BEFORE_RUN = 'beforeRun';

    /**
     * Event executed after a job is being executed.
     */
    const EVENT_AFTER_RUN = 'afterRun';

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
     *
     * @var \yii\base\Module
     */
    public $module;
    
    /**
     * Choose the serializer.
     * @var string
     */
    public $serializer = 'json';
    
    /**
     * This will release automatically on execution failure. i.e. when
     * the `run` method returns false or catch exception.
     * @var boolean
     */
    public $releaseOnFailure = true;

    /**
     * Initializes the module.
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->module = \Yii::$app->getModule($this->module);
    }

    /**
     * Post new job to the queue. This will trigger event EVENT_BEFORE_POST and
     * EVENT_AFTER_POST.
     *
     * @param Job $job The job.
     * @return boolean Whether operation succeed.
     */
    public function post(Job &$job)
    {
        $this->trigger(self::EVENT_BEFORE_POST, $beforeEvent = new Event(['job' => $job]));
        if (!$beforeEvent->isValid) {
            return false;
        }
        
        $return = $this->postJob($job);
        if (!$return) {
            return false;
        }
        
        $this->trigger(self::EVENT_AFTER_POST, new Event(['job' => $job]));
        return true;
    }
    
    /**
     * Post new job to the queue.  Override this for queue implementation.
     *
     * @param Job $job The job.
     * @return boolean Whether operation succeed.
     */
    abstract protected function postJob(Job $job);

    /**
     * Return next job from the queue. This will trigger event EVENT_BEFORE_FETCH
     * and event EVENT_AFTER_FETCH
     *
     * @return Job|boolean the job or false if not found.
     */
    public function fetch()
    {
        $this->trigger(self::EVENT_BEFORE_FETCH);
        
        $job = $this->fetchJob();
        if ($job == false) {
            return false;
        }
        
        $this->trigger(self::EVENT_AFTER_FETCH, new Event(['job' => $job]));
        return $job;
    }
    
    /**
     * Return next job from the queue. Override this for queue implementation.
     * @return Job|boolean the job or false if not found.
     */
    abstract protected function fetchJob();

    /**
     * Run the job.
     *
     * @param Job $job The job to be executed.
     * @return void
     * @throws \yii\base\Exception Exception.
     */
    public function run(Job $job)
    {
        $this->trigger(self::EVENT_BEFORE_RUN, $beforeEvent = new Event(['job' => $job]));
        if (!$beforeEvent->isValid) {
            return;
        }
        \Yii::info("Running job #: {$job->id}", 'yii2queue');
        try {
            if ($job->isCallable()) {
                $retval = $job->runCallable();
            } else {
                $retval = $this->module->runAction($job->route, $job->data);
            }
        } catch (\Exception $e) {
            if ($job->isCallable()) {
                if (isset($job->header['signature']) && isset($job->header['signature']['route'])) {
                    $id = $job->id . ' ' . \yii\helpers\Json::encode($job->header['signature']['route']);
                } else {
                    $id = $job->id . ' callable';
                }
            } else {
                $id = $job->route;
            }
            $params = json_encode($job->data);
            \Yii::error(
                "Fatal Error: Error running route '{$id}'. Message: {$e->getMessage()}. Parameters: {$params}",
                'yii2queue'
            );
            if ($this->releaseOnFailure) {
                $this->release($job);
            }
            throw new \yii\base\Exception(
                "Error running route '{$id}'. " .
                "Message: {$e->getMessage()}. " .
                "File: {$e->getFile()}[{$e->getLine()}]. Stack Trace: {$e->getTraceAsString()}",
                500
            );
        }
        
        $this->trigger(self::EVENT_AFTER_RUN, new Event(['job' => $job, 'returnValue' => $retval]));
        
        if ($retval !== false) {
            \Yii::info("Deleting job #: {$job->id}", 'yii2queue');
            $this->delete($job);
        } else if ($this->releaseOnFailure) {
            $this->release($job);
        }
    }

    /**
     * Delete the job. This will trigger event EVENT_BEFORE_DELETE and
     * EVENT_AFTER_DELETE.
     *
     * @param Job $job The job to delete.
     * @return boolean whether the operation succeed.
     */
    public function delete(Job $job)
    {
        $this->trigger(self::EVENT_BEFORE_DELETE, $beforeEvent = new Event(['job' => $job]));
        if (!$beforeEvent->isValid) {
            return false;
        }
        
        $return = $this->deleteJob($job);
        if (!$return) {
            return false;
        }
        
        $this->trigger(self::EVENT_AFTER_DELETE, new Event(['job' => $job]));
        return true;
    }
    
    /**
     * Delete the job. Override this for the queue implementation.
     *
     * @param Job $job The job to delete.
     * @return boolean whether the operation succeed.
     */
    abstract protected function deleteJob(Job $job);
    
    /**
     * Release the job. This will trigger event EVENT_BEFORE_RELEASE and
     * EVENT_AFTER_RELEASE.
     *
     * @param Job $job The job to delete.
     * @return boolean whether the operation succeed.
     */
    public function release(Job $job)
    {
        $this->trigger(self::EVENT_BEFORE_RELEASE, $beforeEvent = new Event(['job' => $job]));
        if (!$beforeEvent->isValid) {
            return false;
        }
        
        $return = $this->releaseJob($job);
        if (!$return) {
            return false;
        }
        
        $this->trigger(self::EVENT_AFTER_RELEASE, new Event(['job' => $job]));
        return true;
    }
    
    /**
     * Release the job. Override this for the queue implementation.
     *
     * @param Job $job The job to release.
     * @return boolean whether the operation succeed.
     */
    abstract protected function releaseJob(Job $job);

    /**
     * Deserialize job to be executed.
     *
     * @param string $message The json string.
     * @return Job The job.
     * @throws \yii\base\Exception If there is no route detected.
     */
    protected function deserialize($message)
    {
        $job = $this->deserializeMessage($message);
        if (!isset($job['route'])) {
            throw new \yii\base\Exception('No route detected');
        }
        $route = $job['route'];
        $signature = [];
        if (isset($job['type']) && $job['type'] == Job::TYPE_CALLABLE) {
            $serializer = new \SuperClosure\Serializer();
            $signature['route'] = $route;
            $route = $serializer->unserialize($route);
        }
        $data = \yii\helpers\ArrayHelper::getValue($job, 'data', []);
        $obj = new Job([
            'route' => $route,
            'data' => $data,
        ]);
        $obj->header['signature'] = $signature;
        return $obj;
    }
    
    /**
     * @param array $array The message to be deserialize.
     * @return array
     * @throws Exception Exception.
     */
    protected function deserializeMessage($array)
    {
        switch ($this->serializer) {
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
     * @param Job $job The job to serialize.
     * @return string JSON string.
     */
    protected function serialize(Job $job)
    {
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
     * @param mixed $array Array to serialize.
     * @return array
     * @throws Exception When the message cannot be deserialized.
     */
    protected function serializeMessage($array)
    {
        switch ($this->serializer) {
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
    
    /**
     * Returns the number of queue size.
     * @return integer
     */
    abstract public function getSize();
    
    /**
     * Purge the whole queue.
     * @return boolean
     */
    abstract public function purge();
}
