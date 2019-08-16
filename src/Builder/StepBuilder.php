<?php

namespace webignition\BasilParser\Builder;

use webignition\BasilModel\Step\StepInterface;
use webignition\BasilDataStructure\Step as StepData;
use webignition\BasilModelFactory\InvalidPageElementIdentifierException;
use webignition\BasilModelFactory\MalformedPageElementReferenceException;
use webignition\BasilModelFactory\StepFactory;
use webignition\BasilParser\Exception\CircularStepImportException;
use webignition\BasilParser\Exception\NonRetrievablePageException;
use webignition\BasilParser\Exception\NonRetrievableStepException;
use webignition\BasilParser\Exception\UnknownElementException;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Exception\UnknownPageException;
use webignition\BasilParser\Exception\UnknownStepException;
use webignition\BasilParser\Provider\DataSet\DataSetProviderInterface;
use webignition\BasilParser\Exception\NonRetrievableDataProviderException;
use webignition\BasilParser\Exception\UnknownDataProviderException;
use webignition\BasilParser\Provider\Page\PageProviderInterface;
use webignition\BasilParser\Provider\Step\StepProviderInterface;
use webignition\BasilParser\Resolver\StepImportResolver;
use webignition\BasilParser\Resolver\StepResolver;

class StepBuilder
{
    private $stepFactory;
    private $stepResolver;
    private $stepImportResolver;

    public function __construct(
        StepFactory $stepFactory,
        StepResolver $stepResolver,
        StepImportResolver $stepImportResolver
    ) {
        $this->stepFactory = $stepFactory;
        $this->stepResolver = $stepResolver;
        $this->stepImportResolver = $stepImportResolver;
    }

    public static function createBuilder(): StepBuilder
    {
        return new StepBuilder(
            StepFactory::createFactory(),
            StepResolver::createResolver(),
            StepImportResolver::createResolver()
        );
    }

    /**
     * @param StepData $stepData
     * @param StepProviderInterface $stepProvider
     * @param DataSetProviderInterface $dataSetProvider
     * @param PageProviderInterface $pageProvider
     *
     * @return StepInterface
     *
     * @throws CircularStepImportException
     * @throws InvalidPageElementIdentifierException
     * @throws MalformedPageElementReferenceException
     * @throws NonRetrievableDataProviderException
     * @throws NonRetrievablePageException
     * @throws NonRetrievableStepException
     * @throws UnknownDataProviderException
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     * @throws UnknownStepException
     */
    public function build(
        StepData $stepData,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider,
        PageProviderInterface $pageProvider
    ): StepInterface {
        $unresolvedStep = $this->stepFactory->createFromStepData($stepData);

        $resolvedStep = $this->stepImportResolver->resolveStepImport(
            $unresolvedStep,
            $stepProvider,
            $dataSetProvider,
            $pageProvider
        );

        $resolvedStep = $this->stepImportResolver->resolveDataProviderImport($resolvedStep, $dataSetProvider);

        return $this->stepResolver->resolvePageElementReferences($resolvedStep, $pageProvider);
    }
}
