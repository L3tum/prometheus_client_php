<?php

namespace Prometheus\InMemory;

use Prometheus\Storage\InMemory;
use Prometheus\AbstractGaugeTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
class GaugeTest extends AbstractGaugeTest
{
    public function configureAdapter()
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
