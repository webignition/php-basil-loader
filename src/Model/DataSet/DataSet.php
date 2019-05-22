<?php

namespace webignition\BasilParser\Model\DataSet;

class DataSet implements DataSetInterface
{
    private $data = [];

    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = (string) $value;
        }
    }

    public function getParameterValue(string $parameterName): ?string
    {
        return $this->data[$parameterName] ?? null;
    }

    /**
     * @return string[]
     */
    public function getParameterNames(): array
    {
        $keys = [];

        foreach (array_keys($this->data) as $key) {
            $keys[] = (string) $key;
        }

        return $keys;
    }
}