<?php

declare(strict_types=1);

namespace Prometheus;

class MetricFamilySamples
{
    /**
     * @var mixed
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $help;

    /**
     * @var array
     */
    private $labelNames;

    /**
     * @var array
     */
    private $samples = [];

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->type = $data['type'];
        $this->help = $data['help'];
        $this->labelNames = $data['labelNames'];
        foreach ($data['samples'] as $sampleData) {
            $this->samples[] = new Sample($sampleData);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHelp(): string
    {
        return $this->help;
    }

    /**
     * @return Sample[]
     */
    public function getSamples(): array
    {
        return $this->samples;
    }

    public function getLabelNames(): array
    {
        return $this->labelNames;
    }

    public function hasLabelNames(): bool
    {
        return !empty($this->labelNames);
    }
}
