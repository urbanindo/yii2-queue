<?php
/**
 * QueueController class file.
 *
 * @author Petra Barus <petra.barus@gmail.com>
 * @since 2015.02.24
 */

namespace UrbanIndo\Yii2\Queue\Console;

use Yii;
use UrbanIndo\Yii2\Queue\Job;
use UrbanIndo\Yii2\Queue\Queue;
use yii\base\InvalidParamException;

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
 * OR
 *
 * return [
 *    // ...
 *     'controllerMap' => [
 *         'queue' => [
 *              'class' => 'UrbanIndo\Yii2\Queue\Console\QueueController',
 *              'sleepTimeout' => 1
 *          ]
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
class Controller extends \yii\console\Controller
{

    /**
     * @var string|array|Queue the name of the queue component. default to 'queue'.
     */
    public $queue = 'queue';
    
    /**
     * @var integer sleep timeout for infinite loop in second
     */
    public $sleepTimeout = 0;

    /**
     * @var string the name of the command.
     */
    private $_name = 'queue';
    
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        
        if (!is_numeric($this->sleepTimeout)) {
            throw new InvalidParamException('($sleepTimeout) must be an number');
        }

        if ($this->sleepTimeout < 0) {
            throw new InvalidParamException('($sleepTimeout) must be greater or equal than 0');
        }
        
        $this->queue = \yii\di\Instance::ensure($this->queue, Queue::className());
    }

    /**
     * @inheritdoc
     * @param string $actionID The action id of the current request.
     * @return array the names of the options valid for the action
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID), [
            'queue'
        ]);
    }

    /**
     * Returns the script path.
     * @return string
     */
    protected function getScriptPath()
    {
        return getcwd() . DIRECTORY_SEPARATOR . $_SERVER['argv'][0];
    }

    /**
     * This will continuously run new subprocesses to fetch job from the queue.
     *
     * @param string  $cwd     The working directory.
     * @param integer $timeout Timeout.
     * @param array   $env     The environment to passed to the sub process.
     * The format for each element is 'KEY=VAL'.
     * @return void
     */
    public function actionListen($cwd = null, $timeout = null, $env = [])
    {
        $this->stdout("Listening to queue...\n");
        $this->initSignalHandler();
        $command = PHP_BINARY . " {$this->getScriptPath()} {$this->_name}/run";
        declare(ticks = 1);
        while (true) {
            $this->stdout("Running new process...\n");
            $this->runQueueFetching($command, $cwd, $timeout, $env);
            if ($this->sleepTimeout > 0) {
                sleep($this->sleepTimeout);
            }
        }
        $this->stdout("Exiting...\n");
    }

    /**
     * Run the queue fetching process.
     * @param string  $command The command.
     * @param string  $cwd     The working directory.
     * @param integer $timeout The timeout.
     * @param array   $env     The environment to be passed.
     * @return void
     */
    protected function runQueueFetching(
        $command,
        $cwd = null,
        $timeout = null,
        array $env = []
    ) {
        $process = new \Symfony\Component\Process\Process(
            $command,
            isset($cwd) ? $cwd : getcwd(),
            $env,
            null,
            $timeout
        );
        $process->setTimeout($timeout);
        $process->setIdleTimeout(null);
        $process->run();

        $out = $process->getOutput();
        $err = $process->getErrorOutput();

        if ($process->isSuccessful()) {
            $this->stdout($out . PHP_EOL);
            $this->stdout($err . PHP_EOL);
        } else {
            $this->stderr($out . PHP_EOL);
            $this->stderr($err . PHP_EOL);
            Yii::warning($out, 'yii2queue');
            Yii::warning($err, 'yii2queue');
        }

    }

    /**
     * Initialize signal handler for the process.
     * @return void
     */
    protected function initSignalHandler()
    {
        $signalHandler = function ($signal) {
            switch ($signal) {
                case SIGTERM:
                    $this->stderr('Caught SIGTERM');
                    Yii::error('Caught SIGTERM', 'yii2queue');
                    exit;
                case SIGKILL:
                    $this->stderr('Caught SIGKILL');
                    Yii::error('Caught SIGKILL', 'yii2queue');
                    exit;
                case SIGINT:
                    $this->stderr('Caught SIGINT');
                    Yii::error('Caught SIGINT', 'yii2queue');
                    exit;
            }
        };
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);
    }

    /**
     * Fetch a job from the queue.
     * @return void
     */
    public function actionRun()
    {
        $job = $this->queue->fetch();
        if ($job !== false) {
            $this->stdout("Running job #: {$job->id}" . PHP_EOL);
            $this->queue->run($job);
        } else {
            $this->stdout("No job\n");
        }
    }

    /**
     * Post a job to the queue.
     * @param string $route The route.
     * @param string $data  The data in JSON format.
     * @return void
     */
    public function actionPost($route, $data = '{}')
    {
        $this->stdout("Posting job to queue...\n");
        $job = $this->createJob($route, $data);
        $this->queue->post($job);
    }

    /**
     * Run a task without going to queue.
     *
     * This is useful to test the task controller.
     *
     * @param string $route The route.
     * @param string $data  The data in JSON format.
     * @return void
     */
    public function actionRunTask($route, $data = '{}')
    {
        $this->stdout('Running task queue...');
        $job = $this->createJob($route, $data);
        $this->queue->run($job);
    }

    /**
     * @return void
     */
    public function actionTest()
    {
        $this->queue->post(new Job([
            'route' => 'test/test',
            'data' => ['halohalo' => 10, 'test2' => 100],
        ]));
    }

    /**
     * Create a job from route and data.
     *
     * @param string $route The route.
     * @param string $data  The JSON data.
     * @return Job
     */
    protected function createJob($route, $data = '{}')
    {
        return new Job([
            'route' => $route,
            'data' => \yii\helpers\Json::decode($data),
        ]);
    }

    /**
     * Peek messages from queue that are still active.
     *
     * @param integer $count Number of messages to peek.
     * @return void
     */
    public function actionPeek($count = 1)
    {
        $this->stdout('Peeking queue...');
        for ($i = 0; $i < $count; $i++) {
            $job = $this->queue->fetch();
            if ($job !== false) {
                $this->stdout("Peeking job #: {$job->id}" . PHP_EOL);
                $this->stdout(\yii\helpers\Json::encode($job));
            }
        }
    }

    /**
     * Purging messages from queue that are still active.
     *
     * @param integer $count Number of messages to delete.
     * @return void
     */
    public function actionPurge($count = 1)
    {
        $this->stdout('Purging queue...');
        $queue = $this->queue;
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
     * @param string $value The value.
     * @return void
     */
    public function setName($value)
    {
        $this->_name = $value;
    }
}
