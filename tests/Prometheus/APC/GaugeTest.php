<?php

namespace Prometheus\APC;

use Prometheus\Storage\APC;
use Prometheus\AbstractGaugeTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 * @requires extension apc
 */
class GaugeTest extends AbstractGaugeTest
{
    public function configureAdapter()
    {
        $this->adapter = new APC();
        $this->adapter->flushAPC();
    }
}
