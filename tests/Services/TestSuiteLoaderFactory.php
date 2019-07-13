<?php

namespace webignition\BasilParser\Tests\Services;

use webignition\BasilDataStructure\PathResolver;
use webignition\BasilParser\Loader\TestSuiteLoader;

class TestSuiteLoaderFactory
{
    public static function create(): TestSuiteLoader
    {
        return new TestSuiteLoader(
            YamlLoaderFactory::create(),
            TestLoaderFactory::create(),
            PathResolver::create()
        );
    }
}
