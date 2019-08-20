<?php

namespace webignition\BasilParser\Loader;

use webignition\BasilDataStructure\PathResolver;
use webignition\BasilModel\Test\TestInterface;
use webignition\BasilModelFactory\InvalidPageElementIdentifierException;
use webignition\BasilModelFactory\MalformedPageElementReferenceException;
use webignition\BasilParser\Builder\TestBuilder;
use webignition\BasilDataStructure\Test\Test as TestData;
use webignition\BasilParser\Exception\CircularStepImportException;
use webignition\BasilParser\Exception\NonRetrievableDataProviderException;
use webignition\BasilParser\Exception\NonRetrievablePageException;
use webignition\BasilParser\Exception\NonRetrievableStepException;
use webignition\BasilParser\Exception\UnknownDataProviderException;
use webignition\BasilParser\Exception\UnknownElementException;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Exception\UnknownPageException;
use webignition\BasilParser\Exception\UnknownStepException;
use webignition\BasilParser\Exception\YamlLoaderException;
use webignition\BasilParser\Provider\DataSet\DataSetProviderInterface;
use webignition\BasilParser\Provider\DataSet\PopulatedDataSetProvider;
use webignition\BasilParser\Provider\Page\Factory as PageProviderFactory;
use webignition\BasilParser\Provider\Step\Factory as StepProviderFactory;

class TestLoader
{
    private $yamlLoader;
    private $testBuilder;
    private $pathResolver;
    private $stepProviderFactory;
    private $pageProviderFactory;
    private $dataSetLoader;

    public function __construct(
        YamlLoader $yamlLoader,
        TestBuilder $testBuilder,
        PathResolver $pathResolver,
        StepProviderFactory $stepProviderFactory,
        PageProviderFactory $pageProviderFactory,
        DataSetLoader $dataSetLoader
    ) {
        $this->yamlLoader = $yamlLoader;
        $this->testBuilder = $testBuilder;
        $this->pathResolver = $pathResolver;
        $this->stepProviderFactory = $stepProviderFactory;
        $this->pageProviderFactory = $pageProviderFactory;
        $this->dataSetLoader = $dataSetLoader;
    }

    public static function createLoader(): TestLoader
    {
        return new TestLoader(
            YamlLoader::createLoader(),
            TestBuilder::createBuilder(),
            PathResolver::create(),
            StepProviderFactory::createFactory(),
            PageProviderFactory::createFactory(),
            DataSetLoader::createLoader()
        );
    }

    /**
     * @param string $path
     *
     * @return TestInterface
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
     * @throws YamlLoaderException
     */
    public function load(string $path): TestInterface
    {
        $data = $this->yamlLoader->loadArray($path);
        $testData = new TestData($this->pathResolver, $data, $path);

        $imports = $testData->getImports();

        $stepProvider = $this->stepProviderFactory->createDeferredStepProvider($imports->getStepPaths());
        $pageProvider = $this->pageProviderFactory->createDeferredPageProvider($imports->getPagePaths());
        $dataSetProvider = $this->createDataSetProvider($imports->getDataProviderPaths());

        return $this->testBuilder->build($testData, $pageProvider, $stepProvider, $dataSetProvider);
    }

    /**
     * @param array $importPaths
     *
     * @return DataSetProviderInterface
     *
     * @throws NonRetrievableDataProviderException
     */
    private function createDataSetProvider(array $importPaths): DataSetProviderInterface
    {
        $dataSetCollections = [];

        foreach ($importPaths as $importName => $importPath) {
            try {
                $dataSetCollections[$importName] = $this->dataSetLoader->load($importPath);
            } catch (YamlLoaderException $yamlLoaderException) {
                throw new NonRetrievableDataProviderException($importName, $importPath, $yamlLoaderException);
            }
        }

        return new PopulatedDataSetProvider($dataSetCollections);
    }
}
