<?php

namespace Prometheus\APC;

use Prometheus\AbstractGaugeTest;
use Prometheus\Storage\APC;

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
