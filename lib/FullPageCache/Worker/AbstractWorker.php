<?php
namespace Lg\FullPageCache\Worker;

abstract class AbstractWorker {

    /**
     * daemon name
     * @var string
     */
    protected $name;

    /**
     * interval of data fetching in seconds
     * @var double
     */
    protected $workInterval;

    /**
     * the last hash of the deployment
     * @var string
     */
    protected $deploymentCheckHash;

    /**
     * the location of the file containing the deployment hash
     * @var string
     */
    protected $deploymentHashFile;

    /**
     * the location of the file containing the deployment hash
     * @var string
     */
    protected $memoryLimit;

    /**
     * max number of seconds for worker to live
     * @var int
     */
    protected $timeLimit;

    /**
     * start timestamp
     * @var int
     */
    protected $startTime;

    /**
     * @param string $name - daemon name
     * @param double $workInterval - interval of data fetching in seconds
     * @param string $deploymentHashFile - the location of the file containing the deployment hash
     * @param int|null $memoryLimit - MiB
     * @param int|null $timeLimit - seconds
     */
    public function __construct($name, $workInterval, $deploymentHashFile, $memoryLimit = null, $timeLimit = null) {
        $this->name = $name;
        $this->workInterval = $workInterval;
        $this->deploymentHashFile = $deploymentHashFile;
        $this->deploymentCheckHash = $this->getDeploymentHash();
        $this->memoryLimit = $memoryLimit;
        $this->timeLimit = $timeLimit;
        $this->startTime = time();
    }

    protected function getDeploymentHash() {
        if(file_exists($this->deploymentHashFile)) {
            return trim(file_get_contents($this->deploymentHashFile));
        }
        return '';
    }

    /**
     * @return bool
     */
    protected function detectDeployment() {
        return (
            $this->getDeploymentHash() != $this->deploymentCheckHash
        );
    }

    /**
     * @return bool
     */
    protected function detectMemoryLimit() {
        if($this->memoryLimit) {
            return $this->getMemoryConsumptionMiB() > $this->memoryLimit;
        }
        return false;
    }

    /**
     * get total mem consumption from system
     *
     * @return int
     */
    protected function getMemoryConsumptionMiB() {
        $pid = getmypid();
        $mem = intval(trim(shell_exec('ps -p '.$pid.' --no-headers -orss'))); // result in in kb
        $mem /= 1024; // MiB
        $mem = intval(ceil($mem));
        return $mem;
    }

    /**
     * @return bool
     */
    protected function detectTimeLimit() {
        if($this->timeLimit) {
            return (time() - $this->startTime) > $this->timeLimit;
        }
        return false;
    }

    abstract protected function work();

    protected function init() {}

    public function shutdown() {}

    /**
     * main loop
     */
    public function run() {
        try {
            $this->init();
            $nextIntervalWait = 0;
            while(true) {
                if($this->detectDeployment()) {
                    echo "detected deployment\n";
                    return;
                }
                usleep($nextIntervalWait);
                $intervalBegin = microtime(true);

                $this->work();

                $nextIntervalWait = round(max(0,$this->workInterval-(microtime(true)-$intervalBegin))*1000000);

                // check memory after one run, so even if poorly configured, it can do its job
                if($this->detectMemoryLimit()) {
                    echo "detected memory limit\n";
                    return;
                }
                // check time limit after one run, so even if poorly configured, it can do its job
                if($this->detectTimeLimit()) {
                    echo "detected time limit\n";
                    return;
                }
            }
        } catch(Exception $e) {
            error_log((string)$e);
            return;
        }
    }
}