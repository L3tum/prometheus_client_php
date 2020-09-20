<?php

namespace Prometheus\APC;

use Prometheus\Storage\APC;
use Prometheus\AbstractHistogramTest;

/**
 * See https://prometheus.io/docs/instrumenting/exposition_formats/
 * @requires extension apc
 */
class HistogramTest extends AbstractHistogramTest
{
    public function configureAdapter()
    {
        $this->adapter = new APC();
        $this->adapter->flushAPC();
    }
}
