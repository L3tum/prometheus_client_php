<?php

namespace  Prometheus;

use PHPUnit\Framework\TestCase;
use Prometheus\Exception\MetricNotFoundException;
use Prometheus\Exception\MetricsRegistrationException;
use Prometheus\Storage\Adapter;

abstract class AbstractCollectorRegistryTest extends TestCase
{
    /**
     * @var Adapter
     */
    public $adapter;

    /**
     * @var RenderTextFormat
     */
    private $renderer;

    public function setUp(): void
    {
        $this->configureAdapter();
        $this->renderer = new RenderTextFormat();
    }

    /**
     * @test
     */
    public function itShouldSaveGauges()
    {
        $registry = new CollectorRegistry($this->adapter);

        $g = $registry->registerGauge('test', 'some_metric', 'this is for testing', ['foo']);
        $g->set(35, ['bbb']);
        $g->set(35, ['ddd']);
        $g->set(35, ['aaa']);
        $g->set(35, ['ccc']);

        $registry = new CollectorRegistry($this->adapter);
        $this->assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            $this->equalTo(
                <<<EOF
# HELP test_some_metric this is for testing
# TYPE test_some_metric gauge
test_some_metric{foo="aaa"} 35
test_some_metric{foo="bbb"} 35
test_some_metric{foo="ccc"} 35
test_some_metric{foo="ddd"} 35

EOF
            )
        );
    }

    /**
     * @test
     */
    public function itShouldSaveCounters()
    {
        $registry = new CollectorRegistry($this->adapter);
        $metric = $registry->registerCounter('test', 'some_metric', 'this is for testing', ['foo', 'bar']);
        $metric->incBy(2, ['lalal', 'lululu']);
        $registry->getCounter('test', 'some_metric', ['foo', 'bar'])->inc(['lalal', 'lululu']);
        $registry->getCounter('test', 'some_metric', ['foo', 'bar'])->inc(['lalal', 'lvlvlv']);

        $registry = new CollectorRegistry($this->adapter);
        $this->assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            $this->equalTo(
                <<<EOF
# HELP test_some_metric this is for testing
# TYPE test_some_metric counter
test_some_metric{foo="lalal",bar="lululu"} 3
test_some_metric{foo="lalal",bar="lvlvlv"} 1

EOF
            )
        );
    }

    /**
     * @test
     */
    public function itShouldSaveHistograms()
    {
        $registry = new CollectorRegistry($this->adapter);
        $metric = $registry->registerHistogram(
            'test',
            'some_metric',
            'this is for testing',
            ['foo', 'bar'],
            [0.1, 1, 5, 10]
        );
        $metric->observe(2, ['lalal', 'lululu']);
        $registry->getHistogram('test', 'some_metric', ['foo', 'bar'])->observe(7.1, ['lalal', 'lvlvlv']);
        $registry->getHistogram('test', 'some_metric', ['foo', 'bar'])->observe(13, ['lalal', 'lululu']);
        $registry->getHistogram('test', 'some_metric', ['foo', 'bar'])->observe(7.1, ['lalal', 'lululu']);
        $registry->getHistogram('test', 'some_metric', ['foo', 'bar'])->observe(7.1, ['gnaaha', 'hihihi']);

        $registry = new CollectorRegistry($this->adapter);
        $this->assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            $this->equalTo(
                <<<EOF
# HELP test_some_metric this is for testing
# TYPE test_some_metric histogram
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="0.1"} 0
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="1"} 0
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="5"} 0
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="10"} 1
test_some_metric_bucket{foo="gnaaha",bar="hihihi",le="+Inf"} 1
test_some_metric_count{foo="gnaaha",bar="hihihi"} 1
test_some_metric_sum{foo="gnaaha",bar="hihihi"} 7.1
test_some_metric_bucket{foo="lalal",bar="lululu",le="0.1"} 0
test_some_metric_bucket{foo="lalal",bar="lululu",le="1"} 0
test_some_metric_bucket{foo="lalal",bar="lululu",le="5"} 1
test_some_metric_bucket{foo="lalal",bar="lululu",le="10"} 2
test_some_metric_bucket{foo="lalal",bar="lululu",le="+Inf"} 3
test_some_metric_count{foo="lalal",bar="lululu"} 3
test_some_metric_sum{foo="lalal",bar="lululu"} 22.1
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="0.1"} 0
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="1"} 0
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="5"} 0
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="10"} 1
test_some_metric_bucket{foo="lalal",bar="lvlvlv",le="+Inf"} 1
test_some_metric_count{foo="lalal",bar="lvlvlv"} 1
test_some_metric_sum{foo="lalal",bar="lvlvlv"} 7.1

EOF
            )
        );
    }

    /**
     * @test
     */
    public function itShouldSaveHistogramsWithoutLabels()
    {
        $registry = new CollectorRegistry($this->adapter);
        $metric = $registry->registerHistogram('test', 'some_metric', 'this is for testing');
        $metric->observe(2);
        $registry->getHistogram('test', 'some_metric')->observe(13);
        $registry->getHistogram('test', 'some_metric')->observe(7.1);

        $registry = new CollectorRegistry($this->adapter);
        $this->assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            $this->equalTo(
                <<<EOF
# HELP test_some_metric this is for testing
# TYPE test_some_metric histogram
test_some_metric_bucket{le="0.005"} 0
test_some_metric_bucket{le="0.01"} 0
test_some_metric_bucket{le="0.025"} 0
test_some_metric_bucket{le="0.05"} 0
test_some_metric_bucket{le="0.075"} 0
test_some_metric_bucket{le="0.1"} 0
test_some_metric_bucket{le="0.25"} 0
test_some_metric_bucket{le="0.5"} 0
test_some_metric_bucket{le="0.75"} 0
test_some_metric_bucket{le="1"} 0
test_some_metric_bucket{le="2.5"} 1
test_some_metric_bucket{le="5"} 1
test_some_metric_bucket{le="7.5"} 2
test_some_metric_bucket{le="10"} 2
test_some_metric_bucket{le="+Inf"} 3
test_some_metric_count 3
test_some_metric_sum 22.1

EOF
            )
        );
    }

    /**
     * @test
     */
    public function itShouldIncreaseACounterWithoutNamespace()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry
            ->registerCounter('', 'some_quick_counter', 'just a quick measurement')
            ->inc()
        ;

        $this->assertThat(
            $this->renderer->render($registry->getMetricFamilySamples()),
            $this->equalTo(
                <<<EOF
# HELP some_quick_counter just a quick measurement
# TYPE some_quick_counter counter
some_quick_counter 1

EOF
            )
        );
    }

    /**
     * @test
     */
    public function itShouldForbidRegisteringTheSameCounterTwice()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry->registerCounter('foo', 'metric', 'help');

        $this->expectException(MetricsRegistrationException::class);
        $registry->registerCounter('foo', 'metric', 'help');
    }

    /**
     * @test
     */
    public function itShouldForbidRegisteringTheSameCounterWithDifferentLabels()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry->registerCounter('foo', 'metric', 'help', ['foo', 'bar']);

        $this->expectException(MetricsRegistrationException::class);
        $registry->registerCounter('foo', 'metric', 'help', ['spam', 'eggs']);
    }

    /**
     * @test
     */
    public function itShouldForbidRegisteringTheSameHistogramTwice()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry->registerHistogram('foo', 'metric', 'help');

        $this->expectException(MetricsRegistrationException::class);
        $registry->registerHistogram('foo', 'metric', 'help');
    }

    /**
     * @test
     */
    public function itShouldForbidRegisteringTheSameHistogramWithDifferentLabels()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry->registerCounter('foo', 'metric', 'help', ['foo', 'bar']);

        $this->expectException(MetricsRegistrationException::class);
        $registry->registerCounter('foo', 'metric', 'help', ['spam', 'eggs']);
    }

    /**
     * @test
     */
    public function itShouldForbidRegisteringTheSameGaugeTwice()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry->registerGauge('foo', 'metric', 'help');

        $this->expectException(MetricsRegistrationException::class);
        $registry->registerGauge('foo', 'metric', 'help');
    }

    /**
     * @test
     */
    public function itShouldForbidRegisteringTheSameGaugeWithDifferentLabels()
    {
        $registry = new CollectorRegistry($this->adapter);
        $registry->registerGauge('foo', 'metric', 'help', ['foo', 'bar']);

        $this->expectException(MetricsRegistrationException::class);
        $registry->registerGauge('foo', 'metric', 'help', ['spam', 'eggs']);
    }

    /**
     * @test
     */
    public function itShouldThrowAnExceptionWhenGettingANonExistentMetric()
    {
        $registry = new CollectorRegistry($this->adapter);

        $this->expectException(MetricNotFoundException::class);
        $registry->getGauge('not_here', 'go_away');
    }

    /**
     * @test
     */
    public function itShouldNotRegisterACounterTwice()
    {
        $registry = new CollectorRegistry($this->adapter);
        $counterA = $registry->getOrRegisterCounter('foo', 'bar', 'Help text');
        $counterB = $registry->getOrRegisterCounter('foo', 'bar', 'Help text');

        $this->assertSame($counterA, $counterB);
    }

    /**
     * @test
     */
    public function itShouldNotRegisterAGaugeTwice()
    {
        $registry = new CollectorRegistry($this->adapter);
        $gaugeA = $registry->getOrRegisterGauge('foo', 'bar', 'Help text');
        $gaugeB = $registry->getOrRegisterGauge('foo', 'bar', 'Help text');

        $this->assertSame($gaugeA, $gaugeB);
    }

    /**
     * @test
     */
    public function itShouldNotRegisterAHistogramTwice()
    {
        $registry = new CollectorRegistry($this->adapter);
        $histogramA = $registry->getOrRegisterHistogram('foo', 'bar', 'Help text');
        $histogramB = $registry->getOrRegisterHistogram('foo', 'bar', 'Help text');

        $this->assertSame($histogramA, $histogramB);
    }

    abstract public function configureAdapter();
}
