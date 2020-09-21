<?php

namespace Prometheus\Redis;

use Prometheus\AbstractCollectorRegistryTest;
use Prometheus\Storage\Redis;

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
