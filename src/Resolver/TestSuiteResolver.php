<?php

namespace webignition\BasilParser\Resolver;

use webignition\BasilModel\TestSuite\TestSuite;
use webignition\BasilModel\TestSuite\TestSuiteInterface;
use webignition\BasilParser\Exception\MalformedPageElementReferenceException;
use webignition\BasilParser\Exception\NonRetrievableDataProviderException;
use webignition\BasilParser\Exception\NonRetrievablePageException;
use webignition\BasilParser\Exception\NonRetrievableStepException;
use webignition\BasilParser\Exception\UnknownDataProviderException;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Exception\UnknownPageException;
use webignition\BasilParser\Exception\UnknownStepException;
use webignition\BasilParser\Provider\DataSet\DataSetProviderInterface;
use webignition\BasilParser\Provider\Page\PageProviderInterface;
use webignition\BasilParser\Provider\Step\StepProviderInterface;
use webignition\BasilParser\Resolver\Test\TestResolver;

class TestSuiteResolver
{
    private $testResolver;

    public function __construct(TestResolver $testResolver)
    {
        $this->testResolver = $testResolver;
    }

    /**
     * @param TestSuiteInterface $testSuite
     * @param PageProviderInterface $pageProvider
     * @param StepProviderInterface $stepProvider
     * @param DataSetProviderInterface $dataSetProvider
     *
     * @return TestSuiteInterface
     *
     * @throws MalformedPageElementReferenceException
     * @throws NonRetrievableDataProviderException
     * @throws NonRetrievablePageException
     * @throws NonRetrievableStepException
     * @throws UnknownDataProviderException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownStepException
     */
    public function resolve(
        TestSuiteInterface $testSuite,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider
    ): TestSuiteInterface {
        $resolvedTests = [];

        foreach ($testSuite->getTests() as $test) {
            $resolvedTests[] = $this->testResolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);
        }

        return new TestSuite($testSuite->getName(), $resolvedTests);
    }
}