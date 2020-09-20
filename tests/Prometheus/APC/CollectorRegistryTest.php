<?php

namespace Prometheus\APC;

use Prometheus\Storage\APC;
use Prometheus\AbstractCollectorRegistryTest;

/**
 * @requires extension apc
 */
class CollectorRegistryTest extends AbstractCollectorRegistryTest
{
    public function configureAdapter()
    {
        $this->adapter = new APC();
        $this->adapter->flushAPC();
    }
}
