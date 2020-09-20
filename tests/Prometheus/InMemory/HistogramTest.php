<?php

namespace Prometheus\InMemory;

use Prometheus\AbstractHistogramTest;
use Prometheus\Storage\InMemory;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 */
class HistogramTest extends AbstractHistogramTest
{
    public function configureAdapter()
    {
        $this->adapter = new InMemory();
        $this->adapter->flushMemory();
    }
}
