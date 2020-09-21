<?php

namespace Prometheus\APC;

use Prometheus\AbstractCollectorRegistryTest;
use Prometheus\Storage\APC;

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
