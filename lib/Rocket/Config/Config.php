<?php

namespace Rocket\Config;

class Config implements ConfigInterface
{
    const PATH_DELIMITER = '.';
    const WILD_CARD = '*';

    protected $config;

    public function __construct($config = null)
    {
        $this->config = $config;

        if (!is_array($this->config)) {
            $this->config = [];
        }
    }

    public function get($path, $default = null)
    {
        $value = $this->getValue($this->config, explode(self::PATH_DELIMITER, $path));

        if ($default !== null && $value === null) {
            $value = $default;
        } elseif ($value === null) {
            throw new ConfigException('Configuration value '.$path.' required');
        }

        return $value;
    }

    protected function getValue(&$data, $path, $depth = 0)
    {
        if (!is_array($data)) {
            return;
        }

        if ($path[$depth] == self::WILD_CARD) {
            $matches = array();
            foreach ($data as $key => $val) {
                $match = $this->getValue($data[$key], $path, $depth+1);
                $matches[] = $match;
            }

            return $matches;
        } elseif ($depth == count($path)-1) {
            return $data[$path[$depth]];
        } elseif (array_key_exists($path[$depth], $data)) {
            return $this->getValue($data[$path[$depth]], $path, $depth+1);
        }

        return;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getRedisConnections()
    {
        return $this->get('redis_connections');
    }

    public function getApplicationName()
    {
        return $this->get('application_name');
    }

    public function getDefaultQueueName()
    {
        return $this->get('default_queue_name', 'DEFAULT_QUEUE');
    }

    public function getQueuesDefaultWaitingLimit()
    {
        return $this->get('queues.default_waiting_limit', 0);
    }

    public function getQueuesWaitingLimit($queueName)
    {
        return $this->get('queues.waiting_limits.'.$queueName, $this->getQueuesDefaultWaitingLimit());
    }

    public function getQueuesDefaultMinRunningLimit()
    {
        return $this->get('queues.default_min_running_limit', 0);
    }

    public function getQueuesMinRunningLimit($queueName)
    {
        return $this->get('queues.min_running_limits.'.$queueName, $this->getQueuesDefaultMinRunningLimit());
    }

    public function getQueuesDefaultMaxRunningLimit()
    {
        return $this->get('queues.default_max_running_limit', 0);
    }

    public function getQueuesMaxRunningLimit($queueName)
    {
        return $this->get('queues.max_running_limits.'.$queueName, $this->getQueuesDefaultMaxRunningLimit());
    }

    public function getStatisticsPeriodSize()
    {
        return $this->get('statistics.period_size', 300); //5 minutes
    }

    public function getStatisticsPeriodCount()
    {
        return $this->get('statistics.period_count', 12); //1 hour
    }

    public function getMonitorWaitingJobMax()
    {
        return $this->get('monitor.waiting_job_max', 0);
    }

    public function getMonitorDeliveredJobMax()
    {
        return $this->get('monitor.waiting_job_max', 0);
    }

    public function getMonitorDefaultRunningJobMax()
    {
        return $this->get('monitor.default_running_job_max', 0);
    }

    public function getMonitorCompletedJobTTL()
    {
        return $this->get('monitor.completed_job_ttl');
    }

    public function getMonitorFailedJobTTL()
    {
        return $this->get('monitor.failed_job_ttl');
    }

    public function getMonitorCancelledJobTTL()
    {
        return $this->get('monitor.cancelled_job_ttl');
    }

    public function getRouterDefaultExpr()
    {
        return $this->get('router.default_expr');
    }

    public function getRouterRules()
    {
        return $this->get('router.rules');
    }

    public function getWorkerJobWaitTimeout()
    {
        return $this->get('worker.job_wait_timeout', 10);
    }

    public function getWorkerMaxQueuesToPump()
    {
        return $this->get('worker.max_queues_to_pump', 4);
    }

    public function getWorkerMaxJobsToPump()
    {
        return $this->get('worker.max_jobs_to_pump', 4);
    }

    public function getWorkerMaxEventsToHandle()
    {
        return $this->get('worker.max_events_to_handle', 10);
    }

    public function getWorkerMaxSchedJobsToQueue()
    {
        return $this->get('worker.max_sched_jobs_to_queue', 6);
    }

    public function getWorkerCommandTTL()
    {
        return $this->get('worker.command_ttl', 600);
    }

    public function getWorkerResolveTimeout()
    {
        return $this->get('worker.resolve_timeout', 60);
    }

    public function getWorkerMaxInactivity()
    {
        return $this->get('worker.max_inactivity', 60 * 60 * 24);
    }

    public function getTotalWorkerCount()
    {
        return $this->get('total_worker_count', 2);
    }

    public function getTopCacheTTL()
    {
        return $this->get('top.cache_ttl', 30);
    }
}