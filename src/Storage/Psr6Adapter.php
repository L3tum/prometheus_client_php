<?php

declare(strict_types=1);

use Prometheus\MetricFamilySamples;
use Prometheus\Storage\Adapter;
use Psr\Cache\CacheItemPoolInterface;

class Psr6Adapter implements Adapter
{
    const PROMETHEUS_PREFIX = 'prom';

    const KEY_COLLECTION_HISTOGRAMS = self::PROMETHEUS_PREFIX . ':KeyCollectionHistograms';
    const KEY_COLLECTION_GAUGES = self::PROMETHEUS_PREFIX . ':KeyCollectionGauges';
    const KEY_COLLECTION_COUNTERS = self::PROMETHEUS_PREFIX . ':KeyCollectionCounters';

    /**
     * @var CacheItemPoolInterface
     */
    protected $pool;

    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @return MetricFamilySamples[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function collect(): array
    {
        $metrics = $this->collectHistograms();
        $metrics = array_merge($metrics, $this->collectGauges());

        return array_merge($metrics, $this->collectCounters());
    }

    /**
     * @return MetricFamilySamples[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function collectHistograms(): array
    {
        $globalKeyItem = $this->pool->getItem(self::KEY_COLLECTION_HISTOGRAMS);
        $keys = json_decode($globalKeyItem);
        $histograms = [];

        // Collect all the buckets into their respective histograms
        foreach ($keys as $key) {
            $item = $this->pool->getItem($key);
            $metaData = json_decode(base64_decode($key), true);
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
                'buckets' => $metaData['buckets'],
            ];

            $histoHash = $data['name'] . json_encode($metaData['labelNames']);

            if (!array_key_exists($histoHash, $histograms)) {
                // Add the Inf bucket so we can compute it later on
                $data['buckets'][] = '+Inf';
                $histograms[$histoHash] = $data;
                $histograms[$histoHash]['bucket_values'] = [];
                $histograms[$histoHash]['samples'] = [];
            }

            $histograms[$histoHash]['bucket_values'][$metaData['bucket']] = $item->get();
        }

        $metrics = [];

        // Iterate through all histograms
        foreach ($histograms as $hash => $histogram) {
            $acc = 0;
            foreach ($histogram['bucket_values'] as $bucketName => $bucketValue) {
                if (!isset($bucketValue)) {
                    $histogram['samples'][] = [
                        'name' => $histogram['metadata']['name'] . '_bucket',
                        'labelNames' => ['le'],
                        'labelValues' => array_merge($histogram['metadata']['labelValues']),
                        'value' => $acc
                    ];
                } else {
                    $acc += $bucketValue;
                    $histogram['samples'][] = [
                        'name' => $histogram['metadata']['name'] . '_bucket',
                        'labelNames' => ['le'],
                        'labelValues' => array_merge($histogram['metadata']['labelValues']),
                        'value' => $acc
                    ];
                }
            }

            // Add count
            $histogram['samples'][] = [
                'name' => $histogram['metadata']['name'] . '_count',
                'labelNames' => [],
                'labelValues' => $histogram['metadata']['labelValues'],
                'value' => $acc
            ];

            // Add sum
            $histogram['samples'][] = [
                'name' => $histogram['metadata']['name'] . '_sum',
                'labelNames' => [],
                'labelValues' => $histogram['metadata']['labelValues'],
                'value' => $histogram['buckets']['sum']
            ];

            unset($histogram['bucket_values']);
            $metrics[] = new MetricFamilySamples($histogram);
        }

        return $metrics;
    }

    /**
     * @return MetricFamilySamples[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function collectGauges(): array
    {
        $gauges = [];

        $gaugeKeyCollection = $this->pool->getItem(self::KEY_COLLECTION_GAUGES);
        $gaugeKeys = json_decode($gaugeKeyCollection->get());

        foreach ($gaugeKeys as $counterKey) {
            $gaugeItem = $this->pool->getItem($counterKey);
            $metaData = json_decode(base64_decode($counterKey));
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
            ];

            $gaugeHash = $data['name'] . json_encode($data['labelNames']);

            if (!array_key_exists($gaugeHash, $gauges)) {
                $gauges[$gaugeHash] = $data;
                $gauges[$gaugeHash]['samples'] = [];
            }

            $gauges[$gaugeHash]['samples'][] = [
                'name' => $data['name'],
                'labelNames' => [],
                'labelValues' => $metaData['labelValues'],
                'value' => $gaugeItem->get(),
            ];
        }

        $metrics = [];

        foreach ($gauges as $gauge) {
            $this->sortSamples($gauge['samples']);
            $metrics[] = new MetricFamilySamples($gauge);
        }

        return $metrics;
    }

    private function sortSamples(array &$samples): void
    {
        usort($samples, function ($a, $b) {
            return strcmp(implode('', $a['labelValues']), implode('', $b['labelValues']));
        });
    }

    /**
     * @return MetricFamilySamples[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function collectCounters(): array
    {
        $counters = [];

        $counterKeyCollection = $this->pool->getItem(self::KEY_COLLECTION_COUNTERS);
        $counterKeys = json_decode($counterKeyCollection->get());

        foreach ($counterKeys as $counterKey) {
            $counterItem = $this->pool->getItem($counterKey);
            $metaData = json_decode(base64_decode($counterKey));
            $data = [
                'name' => $metaData['name'],
                'help' => $metaData['help'],
                'type' => $metaData['type'],
                'labelNames' => $metaData['labelNames'],
            ];

            $counterHash = $data['name'] . json_encode($data['labelNames']);

            if (!array_key_exists($counterHash, $counters)) {
                $counters[$counterHash] = $data;
                $counters[$counterHash]['samples'] = [];
            }

            $counters[$counterHash]['samples'][] = [
                'name' => $data['name'],
                'labelNames' => [],
                'labelValues' => $metaData['labelValues'],
                'value' => $counterItem->get(),
            ];
        }

        $metrics = [];

        foreach ($counters as $counter) {
            $this->sortSamples($counter['samples']);
            $metrics[] = new MetricFamilySamples($counter);
        }

        return $metrics;
    }

    public function updateHistogram(array $data): void
    {
        // Get the sum bucket
        $sumItem = $this->pool->getItem($this->encodeKey($data, 'sum'));

        // If sum does not exist, assume a new histogram and store the metadata
        if (!$sumItem->isHit()) {
            // Set initial value
            $sumItem->set(0);
            $this->registerNewHistogramBucketKey($sumItem->getKey());
        }

        // Increment value
        $sumItem->set($sumItem->get() + $data['value']);
        $this->pool->saveDeferred($sumItem);

        // Figure out in which bucket the observation belongs
        $bucketToIncrease = '+Inf';
        foreach ($data['buckets'] as $bucket) {
            if ($data['value'] <= $bucket) {
                $bucketToIncrease = $bucket;
                break;
            }
        }

        // Get bucket
        $bucketItem = $this->pool->getItem($this->encodeKey($data, $bucketToIncrease));

        // Initialize the bucket if not exist
        if (!$bucketItem->isHit()) {
            $bucketItem->set(0);
            $this->registerNewHistogramBucketKey($bucketItem->getKey());
        }

        // Increment bucket value
        $bucketItem->set($bucketItem->get() + 1);
        $this->pool->saveDeferred($bucketItem);

        // Save everything to cache
        $this->pool->commit();
    }

    private function encodeKey(array $data, string $bucket = ''): string
    {
        $metricsMetaData = $data;
        $metricsMetaData['bucket'] = $bucket;
        unset($metricsMetaData['value']);
        unset($metricsMetaData['command']);

        return implode(':', [
            self::PROMETHEUS_PREFIX,
            base64_encode(json_encode($metricsMetaData))
        ]);
    }

    private function registerNewHistogramBucketKey(string $key)
    {
        $this->registerNewKey($key, self::KEY_COLLECTION_HISTOGRAMS);
    }

    private function registerNewKey(string $key, string $keyCollection)
    {
        $globalKeyItem = $this->pool->getItem($keyCollection);

        if (!$globalKeyItem->isHit()) {
            $globalKeyItem->set(json_encode([]));
        }

        $value = json_decode($globalKeyItem->get());
        $value[] = $key;
        $globalKeyItem->set(json_encode($value));
        $this->pool->save($globalKeyItem);
    }

    public function updateGauge(array $data): void
    {
        $valueItem = $this->pool->getItem($this->encodeKey($data));

        if ($data['command'] == Adapter::COMMAND_SET) {
            // Just override any value present
            $this->registerNewGaugeKey($valueItem->getKey());

            $valueItem->set($data['value']);
            $this->pool->saveDeferred($valueItem);
        } else {
            // New gauge
            if (!$valueItem->isHit()) {
                // Set initial value
                $valueItem->set(0);
                $this->registerNewGaugeKey($valueItem->getKey());
            }

            // Increment value
            $valueItem->set($valueItem->get() + $data['value']);
            $this->pool->saveDeferred($valueItem);
        }

        $this->pool->commit();
    }

    private function registerNewGaugeKey(string $key)
    {
        $this->registerNewKey($key, self::KEY_COLLECTION_GAUGES);
    }

    public function updateCounter(array $data): void
    {
        $valueItem = $this->pool->getItem($this->encodeKey($data));

        // New counter
        if (!$valueItem->isHit()) {
            // Set initial value
            $valueItem->set(0);
            $this->registerNewCounterKey($valueItem->getKey());
        }

        // Increment value
        $valueItem->set($valueItem->get() + $data['value']);
        $this->pool->saveDeferred($valueItem);

        $this->pool->commit();
    }

    private function registerNewCounterKey(string $key)
    {
        $this->registerNewKey($key, self::KEY_COLLECTION_COUNTERS);
    }
}
