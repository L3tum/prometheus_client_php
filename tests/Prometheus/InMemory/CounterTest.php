<?php

namespace Prometheus\InMemory;

use Prometheus\Storage\InMemory;
use Prometheus\AbstractCounterTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
class CounterTest extends AbstractCounterTest
{
    public function configureAdapter()
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
