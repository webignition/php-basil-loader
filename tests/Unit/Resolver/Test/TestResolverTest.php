<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilParser\Tests\Unit\Resolver\Test;

use Nyholm\Psr7\Uri;
use webignition\BasilContextAwareException\ContextAwareExceptionInterface;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContext;
use webignition\BasilContextAwareException\ExceptionContext\ExceptionContextInterface;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InputAction;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Assertion\Assertion;
use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\DataSet\DataSetCollection;
use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Identifier\IdentifierCollection;
use webignition\BasilModel\Page\Page;
use webignition\BasilModel\Step\PendingImportResolutionStep;
use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Test\Configuration;
use webignition\BasilModel\Test\Test;
use webignition\BasilModel\Test\TestInterface;
use webignition\BasilModel\Value\ElementValue;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\ObjectNames;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\Action\ActionFactory;
use webignition\BasilModelFactory\AssertionFactory;
use webignition\BasilParser\Exception\UnknownDataProviderException;
use webignition\BasilParser\Exception\UnknownElementException;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Exception\UnknownPageException;
use webignition\BasilParser\Exception\UnknownStepException;
use webignition\BasilParser\Provider\DataSet\DataSetProviderInterface;
use webignition\BasilParser\Provider\DataSet\DataSetProvider;
use webignition\BasilParser\Provider\Page\PageProviderInterface;
use webignition\BasilParser\Provider\Page\PageProvider;
use webignition\BasilParser\Provider\Step\PopulatedStepProvider;
use webignition\BasilParser\Provider\Step\StepProviderInterface;
use webignition\BasilParser\Resolver\Test\TestResolver;
use webignition\BasilParser\Tests\Services\Provider\EmptyDataSetProvider;
use webignition\BasilParser\Tests\Services\Provider\EmptyPageProvider;
use webignition\BasilParser\Tests\Services\Provider\EmptyStepProvider;
use webignition\BasilParser\Tests\Services\TestIdentifierFactory;

class TestResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = TestResolver::createResolver();
    }

    /**
     * @dataProvider resolveSuccessDataProvider
     */
    public function testResolveSuccess(
        TestInterface $test,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider,
        TestInterface $expectedTest
    ) {
        $resolvedTest = $this->resolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);

        $this->assertEquals($expectedTest, $resolvedTest);
    }

    public function resolveSuccessDataProvider(): array
    {
        $actionFactory = ActionFactory::createFactory();
        $assertionFactory = AssertionFactory::createFactory();

        $actionSelectorIdentifier = new ElementIdentifier(
            LiteralValue::createCssSelectorValue('.action-selector')
        );

        $assertionSelectorIdentifier = new ElementIdentifier(
            LiteralValue::createCssSelectorValue('.assertion-selector')
        );

        $namedActionSelectorIdentifier = TestIdentifierFactory::createCssElementIdentifier(
            '.action-selector',
            1,
            'action_selector'
        );

        $namedAssertionSelectorIdentifier = TestIdentifierFactory::createCssElementIdentifier(
            '.assertion-selector',
            1,
            'assertion_selector'
        );

        $pageElementReferenceActionIdentifier = TestIdentifierFactory::createPageElementReferenceIdentifier(
            new ObjectValue(
                ValueTypes::PAGE_ELEMENT_REFERENCE,
                'page_import_name.elements.action_selector',
                'page_import_name',
                'action_selector'
            ),
            'action_selector'
        );

        $pageElementReferenceAssertionIdentifier = TestIdentifierFactory::createPageElementReferenceIdentifier(
            new ObjectValue(
                ValueTypes::PAGE_ELEMENT_REFERENCE,
                'page_import_name.elements.assertion_selector',
                'page_import_name',
                'assertion_selector'
            ),
            'assertion_selector'
        );

        return [
            'empty test' => [
                'test' => new Test('test name', new Configuration('', ''), []),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), []),
            ],
            'configuration is resolved' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', 'page_import_name.url'),
                    []
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(new Uri('http://example.com/'), new IdentifierCollection()),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(
                    'test name',
                    new Configuration('', 'http://example.com/'),
                    []
                ),
            ],
            'empty step' => [
                'test' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step([], []),
                ]),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step([], []),
                ]),
            ],
            'no imports, actions and assertions require no resolution' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => new Step(
                            [
                                $actionFactory->createFromActionString('click ".action-selector"'),
                            ],
                            [
                                $assertionFactory->createFromAssertionString('".assertion-selector" exists')
                            ]
                        ),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click ".action-selector"',
                                ActionTypes::CLICK,
                                $actionSelectorIdentifier,
                                '".action-selector"'
                            )
                        ],
                        [
                            new Assertion(
                                '".assertion-selector" exists',
                                new ElementValue($assertionSelectorIdentifier),
                                AssertionComparisons::EXISTS
                            )
                        ]
                    ),
                ]),
            ],
            'actions and assertions require resolution of page imports' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => new Step(
                            [
                                $actionFactory->createFromActionString(
                                    'click page_import_name.elements.action_selector'
                                ),
                            ],
                            [
                                $assertionFactory->createFromAssertionString(
                                    'page_import_name.elements.assertion_selector exists'
                                )
                            ]
                        ),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com'),
                        new IdentifierCollection([
                            $namedActionSelectorIdentifier,
                            $namedAssertionSelectorIdentifier,
                        ])
                    ),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click page_import_name.elements.action_selector',
                                ActionTypes::CLICK,
                                $namedActionSelectorIdentifier,
                                'page_import_name.elements.action_selector'
                            )
                        ],
                        [
                            new Assertion(
                                'page_import_name.elements.assertion_selector exists',
                                new ElementValue($namedAssertionSelectorIdentifier),
                                AssertionComparisons::EXISTS
                            )
                        ]
                    ),
                ]),
            ],
            'empty step imports step, imported actions and assertions require no resolution' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            ''
                        ),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new Step(
                        [
                            $actionFactory->createFromActionString('click ".action-selector"'),
                        ],
                        [
                            $assertionFactory->createFromAssertionString('".assertion-selector" exists')
                        ]
                    )
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click ".action-selector"',
                                ActionTypes::CLICK,
                                $actionSelectorIdentifier,
                                '".action-selector"'
                            )
                        ],
                        [
                            new Assertion(
                                '".assertion-selector" exists',
                                new ElementValue($assertionSelectorIdentifier),
                                AssertionComparisons::EXISTS
                            )
                        ]
                    ),
                ]),
            ],
            'empty step imports step, imported actions and assertions require element resolution' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => (new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            ''
                        ))->withIdentifierCollection(new IdentifierCollection([
                            $pageElementReferenceActionIdentifier,
                            $pageElementReferenceAssertionIdentifier,
                        ])),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com'),
                        new IdentifierCollection([
                            $namedActionSelectorIdentifier,
                            $namedAssertionSelectorIdentifier,
                        ])
                    ),
                ]),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new Step(
                        [
                            $actionFactory->createFromActionString('click $elements.action_selector'),
                        ],
                        [
                            $assertionFactory->createFromAssertionString('$elements.assertion_selector exists')
                        ]
                    )
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $elements.action_selector',
                                ActionTypes::CLICK,
                                $namedActionSelectorIdentifier,
                                '$elements.action_selector'
                            )
                        ],
                        [
                            new Assertion(
                                '$elements.assertion_selector exists',
                                new ElementValue($namedAssertionSelectorIdentifier),
                                AssertionComparisons::EXISTS
                            )
                        ]
                    ),
                ]),
            ],
            'empty step imports step, imported actions and assertions use inline data' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => (new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            ''
                        ))->withDataSetCollection(new DataSetCollection([
                            new DataSet('0', [
                                'key1' => 'key1value1',
                                'key2' => 'key2value1',
                            ]),
                            new DataSet('1', [
                                'key1' => 'key1value2',
                                'key2' => 'key2value2',
                            ]),
                        ])),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new Step(
                        [
                            $actionFactory->createFromActionString('set ".action-selector" to $data.key1'),
                        ],
                        [
                            $assertionFactory->createFromAssertionString('".assertion-selector" is $data.key2')
                        ]
                    )
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => (new Step(
                        [
                            new InputAction(
                                'set ".action-selector" to $data.key1',
                                $actionSelectorIdentifier,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key1',
                                    ObjectNames::DATA,
                                    'key1'
                                ),
                                '".action-selector" to $data.key1'
                            )
                        ],
                        [
                            new Assertion(
                                '".assertion-selector" is $data.key2',
                                new ElementValue($assertionSelectorIdentifier),
                                AssertionComparisons::IS,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key2',
                                    ObjectNames::DATA,
                                    'key2'
                                )
                            )
                        ]
                    ))->withDataSetCollection(new DataSetCollection([
                        new DataSet('0', [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ]),
                        new DataSet('1', [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ]),
                    ])),
                ]),
            ],
            'empty step imports step, imported actions and assertions use imported data' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            'data_provider_import_name'
                        ),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new Step(
                        [
                            $actionFactory->createFromActionString('set ".action-selector" to $data.key1'),
                        ],
                        [
                            $assertionFactory->createFromAssertionString('".assertion-selector" is $data.key2')
                        ]
                    )
                ]),
                'dataSetProvider' => new DataSetProvider([
                    'data_provider_import_name' => new DataSetCollection([
                        new DataSet('0', [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ]),
                        new DataSet('1', [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ]),
                    ]),
                ]),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => (new Step(
                        [
                            new InputAction(
                                'set ".action-selector" to $data.key1',
                                $actionSelectorIdentifier,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key1',
                                    ObjectNames::DATA,
                                    'key1'
                                ),
                                '".action-selector" to $data.key1'
                            )
                        ],
                        [
                            new Assertion(
                                '".assertion-selector" is $data.key2',
                                new ElementValue($assertionSelectorIdentifier),
                                AssertionComparisons::IS,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key2',
                                    ObjectNames::DATA,
                                    'key2'
                                )
                            )
                        ]
                    ))->withDataSetCollection(new DataSetCollection([
                        new DataSet('0', [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ]),
                        new DataSet('1', [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ]),
                    ])),
                ]),
            ],
            'deferred step import, imported actions and assertions require element resolution' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => (new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            ''
                        ))->withIdentifierCollection(new IdentifierCollection([
                            $pageElementReferenceActionIdentifier,
                            $pageElementReferenceAssertionIdentifier,
                        ])),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('https://example.com'),
                        new IdentifierCollection([
                            TestIdentifierFactory::createCssElementIdentifier('.action-selector', 1, 'action_selector'),
                            TestIdentifierFactory::createCssElementIdentifier(
                                '.assertion-selector',
                                1,
                                'assertion_selector'
                            ),
                        ])
                    ),
                ]),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new PendingImportResolutionStep(
                        new Step([], []),
                        'deferred',
                        ''
                    ),
                    'deferred' => new Step(
                        [
                            new InteractionAction(
                                'click $elements.action_selector',
                                ActionTypes::CLICK,
                                $namedActionSelectorIdentifier,
                                '$elements.action_selector'
                            ),
                        ],
                        [
                            new Assertion(
                                '$elements.assertion_selector exists',
                                new ElementValue($namedAssertionSelectorIdentifier),
                                AssertionComparisons::EXISTS
                            ),
                        ]
                    ),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => new Step(
                        [
                            new InteractionAction(
                                'click $elements.action_selector',
                                ActionTypes::CLICK,
                                $namedActionSelectorIdentifier,
                                '$elements.action_selector'
                            ),
                        ],
                        [
                            new Assertion(
                                '$elements.assertion_selector exists',
                                new ElementValue($namedAssertionSelectorIdentifier),
                                AssertionComparisons::EXISTS
                            ),
                        ]
                    ),
                ]),
            ],
            'deferred step import, imported actions and assertions use imported data' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => (new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            'data_provider_import_name'
                        ))->withIdentifierCollection(new IdentifierCollection([
                            $pageElementReferenceActionIdentifier,
                            $pageElementReferenceAssertionIdentifier,
                        ])),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('https://example.com'),
                        new IdentifierCollection([
                            TestIdentifierFactory::createCssElementIdentifier('.action-selector', 1, 'action_selector'),
                            TestIdentifierFactory::createCssElementIdentifier(
                                '.assertion-selector',
                                1,
                                'assertion_selector'
                            ),
                        ])
                    ),
                ]),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new PendingImportResolutionStep(
                        new Step([], []),
                        'deferred',
                        ''
                    ),
                    'deferred' => new Step(
                        [
                            new InputAction(
                                'set $elements.action_selector to $data.key1',
                                $namedActionSelectorIdentifier,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key1',
                                    ObjectNames::DATA,
                                    'key1'
                                ),
                                '$elements.action_selector to $data.key1'
                            )
                        ],
                        [
                            new Assertion(
                                '$elements.assertion_selector is $data.key2',
                                new ElementValue($namedAssertionSelectorIdentifier),
                                AssertionComparisons::IS,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key2',
                                    ObjectNames::DATA,
                                    'key2'
                                )
                            )
                        ]
                    ),
                ]),
                'dataSetProvider' => new DataSetProvider([
                    'data_provider_import_name' => new DataSetCollection([
                        new DataSet('0', [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ]),
                        new DataSet('1', [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ]),
                    ]),
                ]),
                'expectedTest' => new Test('test name', new Configuration('', ''), [
                    'step name' => (new Step(
                        [
                            new InputAction(
                                'set $elements.action_selector to $data.key1',
                                $namedActionSelectorIdentifier,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key1',
                                    ObjectNames::DATA,
                                    'key1'
                                ),
                                '$elements.action_selector to $data.key1'
                            )
                        ],
                        [
                            new Assertion(
                                '$elements.assertion_selector is $data.key2',
                                new ElementValue($namedAssertionSelectorIdentifier),
                                AssertionComparisons::IS,
                                new ObjectValue(
                                    ValueTypes::DATA_PARAMETER,
                                    '$data.key2',
                                    ObjectNames::DATA,
                                    'key2'
                                )
                            )
                        ]
                    ))->withDataSetCollection(new DataSetCollection([
                        new DataSet('0', [
                            'key1' => 'key1value1',
                            'key2' => 'key2value1',
                        ]),
                        new DataSet('1', [
                            'key1' => 'key1value2',
                            'key2' => 'key2value2',
                        ]),
                    ])),
                ]),
            ],
        ];
    }

    /**
     * @dataProvider resolveThrowsExceptionDataProvider
     */
    public function testResolveThrowsException(
        TestInterface $test,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider,
        string $expectedException,
        string $expectedExceptionMessage,
        ExceptionContext $expectedExceptionContext
    ) {
        try {
            $this->resolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);
        } catch (ContextAwareExceptionInterface $contextAwareException) {
            $this->assertInstanceOf($expectedException, $contextAwareException);
            $this->assertEquals($expectedExceptionMessage, $contextAwareException->getMessage());
            $this->assertEquals($expectedExceptionContext, $contextAwareException->getExceptionContext());
        }
    }

    public function resolveThrowsExceptionDataProvider(): array
    {
        return [
            'UnknownDataProviderException: test.data references a data provider that has not been defined' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            'data_provider_import_name'
                        )
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new Step([], []),
                ]),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownDataProviderException::class,
                'expectedExceptionMessage' => 'Unknown data provider "data_provider_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                ])
            ],
            'UnknownPageException: config.url references page not defined within a collection' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'page_import_name.url'),
                    []
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownPageException::class,
                'expectedExceptionMessage' => 'Unknown page "page_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                ])
            ],
            'UnknownPageException: assertion string references page not defined within a collection' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new Step(
                            [],
                            [
                                (AssertionFactory::createFactory())
                                    ->createFromAssertionString('page_import_name.elements.element_name exists'),
                            ]
                        )
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownPageException::class,
                'expectedExceptionMessage' => 'Unknown page "page_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ExceptionContextInterface::KEY_CONTENT => 'page_import_name.elements.element_name exists',
                ])
            ],
            'UnknownPageException: action string references page not defined within a collection' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new Step(
                            [
                                (ActionFactory::createFactory())
                                    ->createFromActionString('click page_import_name.elements.element_name')
                            ],
                            []
                        )
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownPageException::class,
                'expectedExceptionMessage' => 'Unknown page "page_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ExceptionContextInterface::KEY_CONTENT => 'click page_import_name.elements.element_name',
                ])
            ],
            'UnknownPageElementException: test.elements references element that does not exist within a page' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => (new Step([], []))->withIdentifierCollection(new IdentifierCollection([
                            TestIdentifierFactory::createPageElementReferenceIdentifier(
                                new ObjectValue(
                                    ValueTypes::PAGE_ELEMENT_REFERENCE,
                                    'page_import_name.elements.non_existent',
                                    'page_import_name',
                                    'non_existent'
                                ),
                                'non_existent'
                            ),
                        ])),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com'),
                        new IdentifierCollection()
                    )
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownPageElementException::class,
                'expectedExceptionMessage' => 'Unknown page element "non_existent" in page "page_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                ])
            ],
            'UnknownPageElementException: assertion string references element that does not exist within a page' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new Step(
                            [],
                            [
                                (AssertionFactory::createFactory())
                                    ->createFromAssertionString('page_import_name.elements.non_existent exists'),
                            ]
                        ),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com'),
                        new IdentifierCollection()
                    )
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownPageElementException::class,
                'expectedExceptionMessage' => 'Unknown page element "non_existent" in page "page_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ExceptionContextInterface::KEY_CONTENT => 'page_import_name.elements.non_existent exists',
                ])
            ],
            'UnknownPageElementException: action string references element that does not exist within a page' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new Step(
                            [
                                (ActionFactory::createFactory())
                                    ->createFromActionString('click page_import_name.elements.non_existent')
                            ],
                            []
                        ),
                    ]
                ),
                'pageProvider' => new PageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com'),
                        new IdentifierCollection()
                    )
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownPageElementException::class,
                'expectedExceptionMessage' => 'Unknown page element "non_existent" in page "page_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ExceptionContextInterface::KEY_CONTENT => 'click page_import_name.elements.non_existent',
                ])
            ],
            'UnknownStepException: step.use references step not defined within a collection' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            ''
                        ),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownStepException::class,
                'expectedExceptionMessage' => 'Unknown step "step_import_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                ])
            ],
            'UnknownElementException: action element parameter references unknown step element' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new Step(
                            [
                                (ActionFactory::createFactory())
                                    ->createFromActionString('click $elements.element_name')
                            ],
                            []
                        ),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownElementException::class,
                'expectedExceptionMessage' => 'Unknown element "element_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ExceptionContextInterface::KEY_CONTENT => 'click $elements.element_name',
                ])
            ],
            'UnknownElementException: assertion element parameter references unknown step element' => [
                'test' => new Test(
                    'test name',
                    new Configuration('chrome', 'http://example.com'),
                    [
                        'step name' => new Step(
                            [],
                            [
                                (AssertionFactory::createFactory())
                                    ->createFromAssertionString('$elements.element_name exists'),
                            ]
                        ),
                    ]
                ),
                'pageProvider' => new EmptyPageProvider(),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedException' => UnknownElementException::class,
                'expectedExceptionMessage' => 'Unknown element "element_name"',
                'expectedExceptionContext' =>  new ExceptionContext([
                    ExceptionContextInterface::KEY_TEST_NAME => 'test name',
                    ExceptionContextInterface::KEY_STEP_NAME => 'step name',
                    ExceptionContextInterface::KEY_CONTENT => '$elements.element_name exists',
                ])
            ],
        ];
    }
}
