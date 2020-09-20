<?php

namespace Prometheus\InMemory;

use Prometheus\AbstractCollectorRegistryTest;
use Prometheus\Storage\InMemory;

class CollectorRegistryTest extends AbstractCollectorRegistryTest
{
    public function configureAdapter()
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
