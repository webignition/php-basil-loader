<?php

namespace webignition\BasilParser\Provider\DataSet;

use webignition\BasilParser\Loader\DataSetLoader;

class Factory
{
    private $dataSetLoader;

    public function __construct(DataSetLoader $dataSetLoader)
    {
        $this->dataSetLoader = $dataSetLoader;
    }

    public function createDeferredDataSetProvider(array $importPaths)
    {
        return new DeferredDataSetProvider($this->dataSetLoader, $importPaths);
    }
}