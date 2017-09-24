<?php
/**
 * ProcessRunner class file.
 *
 * @author Marek Petras <mark@markpetras.eu>
 * @since 2017.08.01
 */

namespace UrbanIndo\Yii2\Queue;

use IteratorAggregate;
use ArrayIterator;
use Symfony\Component\Process\Process;
use Yii;
use yii\helpers\Console;
use yii\base\InvalidConfigException;

/**
 * The process runner is responsible for all the threads management
 * Listens to the queue, based on the config launches x number of processes
 * Cleans zombies after they are done, in single threaded mode, runs processes in foreground
 *
 * @author Marek Petras <mark@markpetras.eu>
 * @since 2017.08.01
 */
class ProcessRunner extends \yii\base\Component implements IteratorAggregate
{
    /**
     * @var string $cwd working directory to launch the sub processes in; default to current
     */
    protected $cwd = null;

    /**
     * @var array $env enviromental vars to be passed to the sub process
     */
    protected $env = [];

    /**
     * @var string $scriptPath the yii executable
     */
    private $_scriptPath = null;

    /**
     * @var Queue $queue queue
     */
    private $_queue;

    /**
     * @var array $procs current processes
     */
    private $procs = [];

    /**
     * queue setter
     * @param Queue $queue the job queue
     * @return self
     */
    public function setQueue( Queue $queue )
    {
        $this->_queue = $queue;
        return $this;
    }

    /**
     * queue getter
     * @return Queue
     */
    public function getQueue()
    {
        return $this->_queue;
    }

    /**
     * set yii executable
     * @param string $scriptPath real path to the file
     * @return self
     * @throws InvalidConfigException on non existent file
     */
    public function setScriptPath( $scriptPath )
    {
        if ( !is_executable($scriptPath) ) {
            throw new InvalidConfigException('Invalid script path:' . $scriptPath);
        }

        $this->_scriptPath = $scriptPath;
        return $this;
    }

    /**
     * retreive current script path
     * @return string script path
     * @throws InvalidConfigException on non existent file
     */
    public function getScriptPath()
    {
        if ( !is_executable($this->_scriptPath) ) {
            throw new InvalidConfigException('Invalid script path:' . $this->_scriptPath);
        }

        return $this->_scriptPath;
    }

    /**
     * IteratorAggregate implementation
     * @return ArrayIterator running processes
     */
    public function getIterator()
    {
        return new ArrayIterator($this->procs);
    }

    /**
     * listen to the queue, launch processes based on queue settings
     * clean up, catch signals, propagate to sub processes if required or wait for completion
     * launches new jobs from the queue when current < maxprocs
     * @param string $cwd current working dir
     * @param int $timeout timeout to be passed on to the sub processes
     * @param array $env enviromental variables for the sub proc
     * @return void
     */
    public function listen( $cwd = null, $timeout = 0, array $env = null )
    {
        $this->cwd = $cwd;
        $this->env = $env;

        $this->initSignalHandler();

        declare(ticks = 1);

        while (true) {

            // determine the size of the queue
            $queueSize = $this->getQueueSize();

            $this->stdout(sprintf('queueSize: %d , opened: %d , limit: %d ',
                    $queueSize,$this->getOpenedProcsCount(),$this->getMaxProcesses()).PHP_EOL);

            // check for defunct processes
            $this->cleanUpProcs();

            // if we have queue and open spots, launch new ones
            if ( $queueSize ) {
                if ( $this->getCanOpenNew() ) {
                    $this->stdout("Running new  process...\n");
                    $this->runProcess(
                            $this->buildCommand()
                        );
                }
                else {
                    $this->stdout(sprintf('Nothing to do, Waiting for processes to finish; queueSize: %d , opened: %d , limit: %d ',
                        $queueSize,$this->getOpenedProcsCount(),$this->getMaxProcesses()).PHP_EOL);
                    sleep($this->queue->waitSecondsIfNoProcesses); // wait x seconds then try cleaning up
                }
            }
            else {
                if ( $this->queue->waitSecondsIfNoQueue > 0 ) {
                    $this->stdout('NO Queue, Waiting '.$this->queue->waitSecondsIfNoQueue.' to save cpu...' . PHP_EOL);
                    sleep($this->queue->waitSecondsIfNoQueue);
                }
            }

            // sleep if we want to between lanuching new processes
            if ($this->getSleepTimeout() > 0) {
                sleep($this->getSleepTimeout());
            }
        }
    }

    /**
     * run the sub process, register it with others,
     * if we are in single threaded mode, wait for it to finish before moving on
     * @param string $command the command to exec
     * @param string $cwd
     * @return void
     */
    public function runProcess( $command )
    {
        $process = new Process(
            $command,
            $this->cwd ? $this->cwd : getcwd(),
            $this->env
        );

        $this->stdout('Running ' . $command . ' (mode: ' . ($this->getIsSingleThreaded() ? 'single' : 'multi') . ')' . PHP_EOL);

        $process->setTimeout($this->getTimeout());
        $process->setIdleTimeout($this->getIdleTimeout());
        $process->start();

        $this->addProcess($process);

        if ( $this->getIsSingleThreaded() ) {

            $this->stdout('Running in sync mode' . PHP_EOL);

            $pid = $process->getPid();

            $process->wait(function($type,$data){
                $method = 'std'.$type;
                $this->{$method}($data);
            });

            $this->stdout('Done, cleaning:'  . $pid . PHP_EOL);

            $this->cleanUpProc($process, $pid);
        }
    }

    /**
     * add the process to the currently running to be cleaned up after finish
     * @param Process $process the process object
     * @return self
     */
    public function addProcess( Process $process )
    {
        $this->procs[$process->getPid()] = $process;
        return $this;
    }

    /**
     * clean up defunct processes running in background
     * @return void
     */
    public function cleanUpProcs()
    {
        if ( is_array($this->procs) && ($cntProcs = count($this->procs)) > 0 ) {

            $this->stdout('Currently see ' . $cntProcs . ' processes' . PHP_EOL);

            foreach ( $this->procs as $pid => $proc) {
                $this->cleanUpProc($proc,$pid);
            }
        }
    }

    /**
     * build the command to launch sub process
     * @return string command
     */
    protected function buildCommand()
    {
        // using setsid to stop signal propagation to allow background processes to finish even if we receive a signal
        return "setsid " . PHP_BINARY . " {$this->scriptPath} {$this->getCommand()}";
    }

    /**
     * check if process is still running, if not get stdout/error and clean up process
     * @param Process $process the background process
     * @param int $pid process pid
     * @return void
     */
    public function cleanUpProc(Process $process, $pid)
    {
        $process->checkTimeout();

        if ( !$process->isRunning() ) {

            $this->stdout('Cleanning up ' . $pid . PHP_EOL);

            $process->stop();

            $out = $process->getOutput();
            $err = $process->getErrorOutput();

            if ($process->isSuccessful()) {
                $this->stdout('Success' . PHP_EOL);

                // we already display output as it is piped in in single threaded mode
                if ( !$this->getIsSingleThreaded() ) {
                    $this->stdout($out . PHP_EOL);
                    $this->stdout($err . PHP_EOL);
                }

            } else {
                $this->stdout('Error' . PHP_EOL);
                $this->stderr($out . PHP_EOL);
                $this->stderr($err . PHP_EOL);
                Yii::warning($out, 'yii2queue');
                Yii::warning($err, 'yii2queue');
            }

            unset($this->procs[$pid]);
        }
    }

    /**
     * wait for all to finish
     * @return void
     */
    public function cleanUpAll( $signal = null, $propagate = false )
    {
        $this->stdout('Cleaning processes: ' . $this->getOpenedProcsCount() . PHP_EOL);

        while ( $this->getOpenedProcsCount() ) {

            foreach ( $this->procs as $pid => $process ) {

                if ( $process->isRunning()
                    && $process->getPid()
                    && $propagate
                    && $signal )
                {
                    $this->stdout(sprintf('Sending signal %d to pid %d', $signal, $pid) . PHP_EOL);
                    
                    try {
                        $process->signal($signal);
                    } catch ( \Symfony\Component\Process\Exception\LogicException $e ) {
                        $this->stdout('Process was already stopped.');
                    }
                }

                $this->cleanUpProc($process, $pid);
            }

            sleep(1);
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
                    // wait for procs to finish then quit
                    $this->stderr('Caught SIGTERM, cleaning up'.PHP_EOL);
                    $this->cleanUpAll($signal, $this->getPropagateSignals());
                    Yii::error('Caught SIGTERM', 'yii2queue');
                    exit;
                case SIGINT:
                    // wait for procs to finish then quit
                    $this->stderr('Caught SIGINT, cleaning up'.PHP_EOL);
                    $this->cleanUpAll($signal, $this->getPropagateSignals());
                    Yii::error('Caught SIGINT', 'yii2queue');
                    exit;
            }
        };
        pcntl_signal(SIGTERM, $signalHandler);
        pcntl_signal(SIGINT, $signalHandler);
    }

    /**
     * get the idle timeout to be passed on to Process
     * @return ?int idle timeout
     */
    protected function getIdleTimeout()
    {
        return $this->getQueue()->idleTimeout;
    }

    /**
     * get the timeout from the queue, seconds after which the process will timeout
     * @return ?int timeout in seconds
     */
    protected function getTimeout()
    {
        return $this->getQueue()->timeout;
    }

    /**
     * get sleep timeout to be slept after eaech process is launched
     * @return ?int sleep timeout in seconds
     */
    protected function getSleepTimeout()
    {
        return $this->getQueue()->sleepTimeout;
    }

    /**
     * check if we are running in single thread mode
     * @return bool
     */
    protected function getIsSingleThreaded()
    {
        return $this->getMaxProcesses() === 1;
    }

    /**
     * retrieve the size of the queue
     * @return int size
     */
    protected function getQueueSize()
    {
        return intval($this->getQueue()->getSize());
    }

    /**
     * retrieve number of opened processes
     * @return int
     */
    protected function getOpenedProcsCount()
    {
        return is_array($this->procs) ? count($this->procs) : 0;
    }

    /**
     * retrieve max processes from the queue
     * @return int maximum number of concurent processes
     */
    protected function getMaxProcesses()
    {
        return $this->getQueue()->maxProcesses;
    }

    /**
     * check if we can open new ones
     * @return bool
     */
    protected function getCanOpenNew()
    {
        return $this->getOpenedProcsCount() < $this->getMaxProcesses();
    }

    /**
     * if we should propagate signals to children
     * @return bool
     */
    protected function getPropagateSignals()
    {
        return $this->getQueue()->propagateSignals;
    }

    /**
     * get the command to launch the process
     * @return string command
     */
    protected function getCommand()
    {
        return $this->getQueue()->command;
    }

    /**
     * @inheritdoc
     */
    protected function stdout($string)
    {
        return Console::stdout($string);
    }

    /**
     * @inheritdoc
     */
    protected function stderr($string)
    {
        return Console::stderr($string);
    }
}
