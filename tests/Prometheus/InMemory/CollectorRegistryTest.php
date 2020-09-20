<?php

namespace Prometheus\InMemory;

use Prometheus\Storage\InMemory;
use Prometheus\AbstractCollectorRegistryTest;

class CollectorRegistryTest extends AbstractCollectorRegistryTest
{
    public function configureAdapter()
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
