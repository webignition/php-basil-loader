<?php

namespace webignition\BasilParser\Resolver;

use webignition\BasilModel\Identifier\ElementIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierCollectionInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModelFactory\InvalidPageElementIdentifierException;
use webignition\BasilModelFactory\MalformedPageElementReferenceException;
use webignition\BasilParser\Exception\NonRetrievablePageException;
use webignition\BasilParser\Exception\UnknownElementException;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Exception\UnknownPageException;
use webignition\BasilParser\Provider\Page\PageProviderInterface;

class IdentifierResolver
{
    private $pageElementReferenceResolver;

    public function __construct(PageElementReferenceResolver $pageElementReferenceResolver)
    {
        $this->pageElementReferenceResolver = $pageElementReferenceResolver;
    }

    public static function createResolver(): IdentifierResolver
    {
        return new IdentifierResolver(
            PageElementReferenceResolver::createResolver()
        );
    }

    /**
     * @param IdentifierInterface $identifier
     * @param PageProviderInterface $pageProvider
     * @param IdentifierCollectionInterface $identifierCollection
     *
     * @return IdentifierInterface
     *
     * @throws InvalidPageElementIdentifierException
     * @throws MalformedPageElementReferenceException
     * @throws NonRetrievablePageException
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolve(
        IdentifierInterface $identifier,
        PageProviderInterface $pageProvider,
        IdentifierCollectionInterface $identifierCollection
    ): IdentifierInterface {
        $identifierType = $identifier->getType();

        if (!in_array($identifierType, [IdentifierTypes::PAGE_ELEMENT_REFERENCE, IdentifierTypes::ELEMENT_PARAMETER])) {
            return $identifier;
        }

        $value = $identifier->getValue();

        if (!$value instanceof ObjectValue) {
            return $identifier;
        }

        if (IdentifierTypes::PAGE_ELEMENT_REFERENCE === $identifierType) {
            return $this->pageElementReferenceResolver->resolve($value, $pageProvider);
        }

        $elementName = $value->getObjectProperty();
        $resolvedIdentifier = $identifierCollection->getIdentifier($elementName);

        if ($resolvedIdentifier instanceof ElementIdentifierInterface) {
            return $resolvedIdentifier;
        }

        throw new UnknownElementException($elementName);
    }
}
