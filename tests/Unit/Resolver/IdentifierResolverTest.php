<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilParser\Tests\Unit\Resolver;

use Nyholm\Psr7\Uri;
use webignition\BasilModel\Identifier\ElementIdentifier;
use webignition\BasilModel\Identifier\Identifier;
use webignition\BasilModel\Identifier\IdentifierCollection;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Page\Page;
use webignition\BasilModel\Value\LiteralValue;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Provider\Page\EmptyPageProvider;
use webignition\BasilParser\Provider\Page\PageProviderInterface;
use webignition\BasilParser\Provider\Page\PopulatedPageProvider;
use webignition\BasilParser\Resolver\IdentifierResolver;

class IdentifierResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IdentifierResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = IdentifierResolver::createResolver();
    }

    /**
     * @dataProvider resolveNonResolvableDataProvider
     */
    public function testResolveNonResolvable(IdentifierInterface $identifier)
    {
        $resolvedIdentifier = $this->resolver->resolve($identifier, new EmptyPageProvider());

        $this->assertSame($identifier, $resolvedIdentifier);
    }

    public function resolveNonResolvableDataProvider(): array
    {
        return [
            'wrong identifier type' => [
                'identifier' => new ElementIdentifier(
                    LiteralValue::createCssSelectorValue('.selector')
                ),
            ],
            'wrong value type' => [
                'identifier' => new Identifier(
                    IdentifierTypes::PAGE_ELEMENT_REFERENCE,
                    LiteralValue::createStringValue('value')
                ),
            ],
        ];
    }

    /**
     * @dataProvider resolveIsResolvedDataProvider
     */
    public function testResolveIsResolved(
        IdentifierInterface $identifier,
        PageProviderInterface $pageProvider,
        IdentifierInterface $expectedIdentifier
    ) {
        $resolvedIdentifier = $this->resolver->resolve($identifier, $pageProvider);

        $this->assertEquals($expectedIdentifier, $resolvedIdentifier);
    }

    public function resolveIsResolvedDataProvider(): array
    {
        $cssElementIdentifier = new ElementIdentifier(
            LiteralValue::createCssSelectorValue('.selector')
        );

        $cssElementIdentifierWithName = $cssElementIdentifier->withName('element_name');

        return [
            'resolvable' => [
                'identifier' => new Identifier(
                    IdentifierTypes::PAGE_ELEMENT_REFERENCE,
                    new ObjectValue(
                        ValueTypes::PAGE_ELEMENT_REFERENCE,
                        'page_import_name.elements.element_name',
                        'page_import_name',
                        'element_name'
                    )
                ),
                'pageProvider' => new PopulatedPageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com/'),
                        new IdentifierCollection([
                            $cssElementIdentifierWithName,
                        ])
                    )
                ]),
                'expectedIdentifier' => $cssElementIdentifierWithName,
            ],
        ];
    }

    /**
     * @dataProvider resolveThrowsUnknownPageElementExceptionDataProvider
     */
    public function testResolveThrowsUnknownPageElementException(
        IdentifierInterface $identifier,
        PageProviderInterface $pageProvider,
        string $expectedExceptionMessage
    ) {
        $this->expectException(UnknownPageElementException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->resolver->resolve($identifier, $pageProvider);
    }

    public function resolveThrowsUnknownPageElementExceptionDataProvider(): array
    {
        return [
            'element not present in page' => [
                'identifier' => new Identifier(
                    IdentifierTypes::PAGE_ELEMENT_REFERENCE,
                    new ObjectValue(
                        ValueTypes::PAGE_ELEMENT_REFERENCE,
                        'page_import_name.elements.element_name',
                        'page_import_name',
                        'element_name'
                    )
                ),
                'pageProvider' => new PopulatedPageProvider([
                    'page_import_name' => new Page(
                        new Uri('http://example.com/'),
                        new IdentifierCollection([])
                    )
                ]),
                'expectedExceptionMessage' => 'Unknown page element "element_name" in page "page_import_name"',
            ],
        ];
    }
}
