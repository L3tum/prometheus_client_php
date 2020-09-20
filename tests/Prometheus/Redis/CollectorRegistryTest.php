<?php

namespace Prometheus\Redis;

use Prometheus\Storage\Redis;
use Prometheus\AbstractCollectorRegistryTest;

/**
 * @requires extension redis
 */
class CollectorRegistryTest extends AbstractCollectorRegistryTest
{
    public function configureAdapter()
    {
        $this->adapter = new Redis(['host' => REDIS_HOST]);
        $this->adapter->flushRedis();
    }
}
