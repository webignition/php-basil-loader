<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilParser\Tests\Unit\Resolver\Test;

use Nyholm\Psr7\Uri;
use webignition\BasilModel\Action\ActionTypes;
use webignition\BasilModel\Action\InteractionAction;
use webignition\BasilModel\Assertion\Assertion;
use webignition\BasilModel\Assertion\AssertionComparisons;
use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Page\Page;
use webignition\BasilModel\Step\PendingImportResolutionStep;
use webignition\BasilModel\Step\Step;
use webignition\BasilModel\Test\Configuration;
use webignition\BasilModel\Test\Test;
use webignition\BasilModel\Test\TestInterface;
use webignition\BasilModel\Value\Value;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilParser\Provider\DataSet\DataSetProviderInterface;
use webignition\BasilParser\Provider\DataSet\EmptyDataSetProvider;
use webignition\BasilParser\Provider\DataSet\PopulatedDataSetProvider;
use webignition\BasilParser\Provider\Page\EmptyPageProvider;
use webignition\BasilParser\Provider\Page\PageProviderInterface;
use webignition\BasilParser\Provider\Page\PopulatedPageProvider;
use webignition\BasilParser\Provider\Step\EmptyStepProvider;
use webignition\BasilParser\Provider\Step\PopulatedStepProvider;
use webignition\BasilParser\Provider\Step\StepProviderInterface;
use webignition\BasilParser\Resolver\Test\TestResolver;
use webignition\BasilParser\Tests\Services\TestResolverFactory;

class TestResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TestResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = TestResolverFactory::create();
    }

    /**
     * @dataProvider resolveDataProvider
     */
    public function testResolve(
        TestInterface $test,
        PageProviderInterface $pageProvider,
        StepProviderInterface $stepProvider,
        DataSetProviderInterface $dataSetProvider,
        TestInterface $expectedTest
    ) {
        $resolvedTest = $this->resolver->resolve($test, $pageProvider, $stepProvider, $dataSetProvider);

        $this->assertEquals($expectedTest, $resolvedTest);
    }

    public function resolveDataProvider(): array
    {
        return [
            'empty' => [
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
                'pageProvider' => new PopulatedPageProvider([
                    'page_import_name' => new Page(new Uri('http://example.com/'), []),
                ]),
                'stepProvider' => new EmptyStepProvider(),
                'dataSetProvider' => new EmptyDataSetProvider(),
                'expectedTest' => new Test(
                    'test name',
                    new Configuration('', 'http://example.com/'),
                    []
                ),
            ],
            'steps are resolved' => [
                'test' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => (new PendingImportResolutionStep(
                            new Step([], []),
                            'step_import_name',
                            'data_provider_import_name'
                        ))->withElementIdentifiers([
                            new Identifier(
                                IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
                                new Value(
                                    ValueTypes::STRING,
                                    'page_import_name.elements.heading_element_name'
                                ),
                                1,
                                'heading'
                            ),
                        ]),
                    ]
                ),
                'pageProvider' => new PopulatedPageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com/'),
                        [
                            'action_element_name' => new Identifier(
                                IdentifierTypes::CSS_SELECTOR,
                                new Value(
                                    ValueTypes::STRING,
                                    '.action-selector'
                                )
                            ),
                            'assertion_element_name' => new Identifier(
                                IdentifierTypes::CSS_SELECTOR,
                                new Value(
                                    ValueTypes::STRING,
                                    '.assertion-selector'
                                )
                            ),
                            'heading_element_name' => new Identifier(
                                IdentifierTypes::CSS_SELECTOR,
                                new Value(
                                    ValueTypes::STRING,
                                    '.heading-selector'
                                )
                            ),
                        ]
                    )
                ]),
                'stepProvider' => new PopulatedStepProvider([
                    'step_import_name' => new Step(
                        [
                            new InteractionAction(
                                ActionTypes::CLICK,
                                new Identifier(
                                    IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
                                    new Value(
                                        ValueTypes::STRING,
                                        'page_import_name.elements.action_element_name'
                                    )
                                ),
                                'page_import_name.elements.action_element_name'
                            )
                        ],
                        [
                            new Assertion(
                                'page_import_name.elements.assertion_element_name exists',
                                new Identifier(
                                    IdentifierTypes::PAGE_MODEL_ELEMENT_REFERENCE,
                                    new Value(
                                        ValueTypes::STRING,
                                        'page_import_name.elements.assertion_element_name'
                                    )
                                ),
                                AssertionComparisons::EXISTS
                            )
                        ]
                    ),
                ]),
                'dataSetProvider' => new PopulatedDataSetProvider([
                    'data_provider_import_name' => [
                        new DataSet([
                            'foo' => 'bar',
                        ]),
                    ],
                ]),
                'expectedTest' => new Test(
                    'test name',
                    new Configuration('', ''),
                    [
                        'step name' => (new Step(
                            [
                                new InteractionAction(
                                    ActionTypes::CLICK,
                                    new Identifier(
                                        IdentifierTypes::CSS_SELECTOR,
                                        new Value(
                                            ValueTypes::STRING,
                                            '.action-selector'
                                        )
                                    ),
                                    'page_import_name.elements.action_element_name'
                                )
                            ],
                            [
                                new Assertion(
                                    'page_import_name.elements.assertion_element_name exists',
                                    new Identifier(
                                        IdentifierTypes::CSS_SELECTOR,
                                        new Value(
                                            ValueTypes::STRING,
                                            '.assertion-selector'
                                        )
                                    ),
                                    AssertionComparisons::EXISTS
                                )
                            ]
                        ))->withDataSets([
                            new DataSet([
                                'foo' => 'bar',
                            ]),
                        ])->withElementIdentifiers([
                            new Identifier(
                                IdentifierTypes::CSS_SELECTOR,
                                new Value(
                                    ValueTypes::STRING,
                                    '.heading-selector'
                                ),
                                null,
                                'heading'
                            ),
                        ]),
                    ]
                ),
            ],
        ];
    }
}