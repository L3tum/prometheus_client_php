<?php

namespace Prometheus\APC;

use Prometheus\Storage\APC;
use Prometheus\AbstractCounterTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 * @requires extension apc
 */
class CounterTest extends AbstractCounterTest
{
    public function configureAdapter()
    {
        $this->adapter = new APC();
        $this->adapter->flushAPC();
    }
}
