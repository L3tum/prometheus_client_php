<?php

declare(strict_types=1);

namespace Prometheus;

class Sample
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $labelNames;

    /**
     * @var array
     */
    private $labelValues;

    /**
     * @var int|float
     */
    private $value;

    /**
     * Sample constructor.
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->labelNames = $data['labelNames'];
        $this->labelValues = $data['labelValues'];
        $this->value = $data['value'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabelNames(): array
    {
        return (array) $this->labelNames;
    }

    public function getLabelValues(): array
    {
        return (array) $this->labelValues;
    }

    /**
     * @return int|float
     */
    public function getValue(): string
    {
        return (string) $this->value;
    }

    public function hasLabelNames(): bool
    {
        return !empty($this->labelNames);
    }
}
