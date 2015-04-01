<?php

/**
 * QueueController class file.
 * 
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue\Console;

/**
 * QueueController handles console command for running the queue.
 * 
 * To use the controller, update the controllerMap.
 * 
 * return [
 *    // ...
 *     'controllerMap' => [
 *         'queue' => 'UrbanIndo\Yii2\Queue\Console\QueueController'
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

    /**
     * @var string the name of the queue component. default to 'queue'.
     */
    public $queue = 'queue';

    /*
     * @var string the name of the command.
     */
    private $_name = 'queue';

    /**
     * @var \UrbanIndo\Yii2\Queue\Queue stores the queue component.
     */
    private $_queue = null;

    /**
     * @inheritdoc
     */
    public function options($actionID) {
        return array_merge(parent::options($actionID),
                [
            'queue'
        ]);
    }

    /**
     * Returns the queue component.
     * 
     * @return \UrbanIndo\Yii2\Queue\Queue
     */
    private function getQueue() {
        if (!isset($this->_queue)) {
            $this->_queue = \Yii::$app->get($this->queue);
        }
        return $this->_queue;
    }

    /**
     * Returns the script path.
     * @return string
     */
    private function getScriptPath() {
        return getcwd() . DIRECTORY_SEPARATOR . $_SERVER['argv'][0];
    }

    /**
     * This will continuously run new subprocesses to fetch job from the queue.
     * 
     * @param string $cwd the working directory.
     * @param integer $timeout timeout.
     * @param array $env the environment to passed to the sub process. The format for each element is 'KEY=VAL'
     */
    public function actionListen($cwd = null, $timeout = null, $env = []) {
        $this->stdout("Listening to queue...\n");
        $this->initSignalHandler();
        $command = PHP_BINARY . " {$this->getScriptPath()} {$this->_name}/run";
        declare(ticks = 1);
        while (true) {
            $this->stdout("Running new process...\n");
            $this->runQueueFetching($command, $cwd, $timeout, $env);
        }
        $this->stdout("Exiting...");
    }

    /**
     * Run the queue fetching process.
     * @param string $command the command.
     * @param string $cwd the working directory
     * @param integer $timeout the timeout
     * @param array $env the environment to be passed.
     */
    private function runQueueFetching($command, $cwd = null, $timeout = null,
            $env = []) {
        $process = new \Symfony\Component\Process\Process($command,
                isset($cwd) ? $cwd : getcwd(), $env, null, $timeout);
        $process->setTimeout($timeout);
        $process->setIdleTimeout(null);
        $process->run();
        if ($process->isSuccessful()) {
            //TODO logging.
            $this->stdout($process->getOutput() . PHP_EOL);
            $this->stdout($process->getErrorOutput(). PHP_EOL);
        } else {
            //TODO logging.
            $this->stdout($process->getOutput() . PHP_EOL);
            $this->stdout($process->getErrorOutput(). PHP_EOL);
        }
    }

    /**
     * Initialize signal handler for the process.
     */
    private function initSignalHandler() {
        $signalHandler = function ($signal) {
            switch ($signal) {
                case SIGTERM:
                    $this->stderr("Caught SIGTERM");
                    exit;
                case SIGKILL:
                    $this->stderr("Caught SIGKILL");
                    exit;
                case SIGINT:
                    $this->stderr("Caught SIGINT");
                    exit;
            }
        };
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);
    }

    /**
     * Fetch a job from the queue.
     */
    public function actionRun() {
        $queue = $this->getQueue();
        $job = $queue->fetch();
        if ($job !== false) {
            $this->stdout("Running job #: {$job->id}" . PHP_EOL);
            $queue->run($job);
        } else {
            $this->stdout("No job\n");
        }
    }

    /**
     * Post a job to the queue.
     * @param string $route the route.
     * @param string $data the data in JSON format.
     */
    public function actionPost($route, $data = '{}') {
        $this->stdout("Posting job to queue...\n");
        $job = $this->createJob($route, $data);
        $this->getQueue()->post($job);
    }

    /**
     * Run a task without going to queue.
     * 
     * This is useful to test the task controller.
     * 
     * @param string $route the route.
     * @param string $data the data in JSON format.
     */
    public function actionRunTask($route, $data = '{}') {
        $this->stdout("Running task queue...");
        $job = $this->createJob($route, $data);
        $this->getQueue()->run($job);
    }

    public function actionTest() {
        $this->getQueue()->post(new \UrbanIndo\Yii2\Queue\Job([
            'route' => 'test/test',
            'data' => ['halohalo' => 10, 'test2' => 100],
        ]));
    }

    /**
     * Create a job from route and data.
     * 
     * @param string $route the route.
     * @param string $data the JSON data.
     * @return \UrbanIndo\Yii2\Queue\Job
     */
    private function createJob($route, $data = '{}') {
        return new \UrbanIndo\Yii2\Queue\Job([
            'route' => $route,
            'data' => \yii\helpers\Json::decode($data),
        ]);
    }

    /**
     * Peek messages from queue that are still active.
     * 
     * @param integer number of messages.
     */
    public function actionPeek($count = 1) {
        $this->stdout("Peeking queue...");
        $queue = $this->getQueue();
        for ($i = 0; $i < $count; $i++) {
            $job = $queue->fetch();
            if ($job !== false) {
                $this->stdout("Peeking job #: {$job->id}" . PHP_EOL);
                $this->stdout(\yii\helpers\Json::encode($job));
            }
        }
    }

    /**
     * Purging messages from queue that are still active.
     * 
     * @param integer number of messages.
     */
    public function actionPurge($count = 1) {
        $this->stdout("Purging queue...");
        $queue = $this->getQueue();
        for ($i = 0; $i < $count; $i++) {
            $job = $queue->fetch();
            if ($job !== false) {
                $this->stdout("Purging job #: {$job->id}" . PHP_EOL);
                $queue->delete($job);
            }
        }
    }

    /**
     * Sets the name of the command. This should be overriden in the config.
     * @param string $value the value.
     */
    public function setName($value) {
        $this->_name = $value;
    }

}
