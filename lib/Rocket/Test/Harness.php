<?php

namespace Rocket\Test;

use Rocket\RocketInterface;
use Rocket\Singleton;
use Rocket\Config\Config;
use Rocket\Config\ConfigTrait;
use Rocket\Redis\RedisTrait;
use Rocket\Redis\Redis;
use Rocket\Plugin\EventTrait;
use Rocket\Worker\Worker;
use Rocket\Plugin\Pump\PumpPlugin;
use Rocket\Plugin\Monitor\MonitorPlugin;
use Rocket\Plugin\Aggregate\AggregatePlugin;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

class Harness implements RocketInterface
{
    use Singleton;
    use ConfigTrait;
    use EventTrait;
    use RedisTrait;

    const TEST_CONFIG_FILENAME = '/../../../config/test_config.json';
    const TEST_LOG_FILENAME = '/../../../test/test.log';

    protected $logger;
    protected $pump;
    protected $monitor;
    protected $aggregate;
    protected $queues;

    public function flushTestDatabases()
    {
        $this->getLogger()->warning('Flushing test databases!');
        $this->getRedis()->getClient()->flushdb();
    }

    public function getLogger()
    {
        if (is_null($this->logger)) {
            $this->logger = new Logger('test');
            $this->logger->pushHandler(new RotatingFileHandler(__DIR__.self::TEST_LOG_FILENAME, 1, Logger::DEBUG));
        }

        return $this->logger;
    }

    public function getLogContext()
    {
        return;
    }

    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = new Config(json_decode(file_get_contents(__DIR__.self::TEST_CONFIG_FILENAME), true));
        }

        return $this->config;
    }

    public function getRedis()
    {
        if (is_null($this->redis)) {
            $this->redis = new Redis();
            $this->redis->setLogger($this->getLogger());
            $this->redis->setConfig($this->getConfig());
            $this->redis->setEventDispatcher($this->getEventDispatcher());
        }

        return $this->redis;
    }

    public function getEventDispatcher()
    {
        if (is_null($this->eventDispatcher)) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function getWorker($workerName, $maxCache = 16)
    {
        $worker = new Worker($this, $workerName);
        $worker->setConfig($this->getConfig())
               ->setRedis($this->getRedis())
               ->setLogger($this->getLogger())
               ->setEventDispatcher($this->getEventDispatcher());

        return $worker;
    }

    public function getNewQueue()
    {
        $queueName = 'queue'.mt_rand();

        return $this->getQueue($queueName);
    }

    public function getQueue($queueName, $maxCache = 16)
    {
        return new \Rocket\Queue\Queue($this, $queueName);
    }

    public function getJob($jobId, $queueName = null, $maxCache = 16)
    {
        return $this->getQueue($queueName, $maxCache)->getJob($jobId);
    }

    public function getPlugin($name)
    {
        if ($name == 'pump') {
            if (!is_null($this->pump)) {
                return $this->pump;
            }
            $plugin = new PumpPlugin($this);
            $this->pump = $plugin;
        } elseif ($name == 'monitor') {
            if (!is_null($this->monitor)) {
                return $this->monitor;
            }
            $plugin = new MonitorPlugin($this);
            $this->monitor = $plugin;
        } elseif ($name == 'aggregate') {
            if (!is_null($this->aggregate)) {
                return $this->aggregate;
            }
            $plugin = new AggregatePlugin($this);
            $this->aggregate = $plugin;
        }

        if ($plugin) {
            $plugin->setConfig($this->getConfig());
            $plugin->setEventDispatcher($this->getEventDispatcher());
            $plugin->setRedis($this->getRedis());
            $plugin->setLogger($this->getLogger());
            $plugin->setLogContext('plugin', $name);

            $plugin->register();

            return $plugin;
        }
    }

    public function getQueueNameByJobId($jobId)
    {
    }

    public function getQueueCount()
    {
        return count($this->queues);
    }

    public function getQueues()
    {
        return $this->queues;
    }

    public function setQueues($queues)
    {
        $this->queues = $queues;
    }
}