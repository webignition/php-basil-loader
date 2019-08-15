<?php

namespace webignition\BasilParser\Resolver;

use webignition\BasilModel\Identifier\AttributeIdentifier;
use webignition\BasilModel\Identifier\ElementIdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierCollectionInterface;
use webignition\BasilModel\Value\AttributeValue;
use webignition\BasilModel\Value\ElementValue;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelFactory\InvalidPageElementIdentifierException;
use webignition\BasilModelFactory\MalformedPageElementReferenceException;
use webignition\BasilParser\Exception\NonRetrievablePageException;
use webignition\BasilParser\Exception\UnknownElementException;
use webignition\BasilParser\Exception\UnknownPageElementException;
use webignition\BasilParser\Exception\UnknownPageException;
use webignition\BasilParser\Provider\Page\PageProviderInterface;

class ValueResolver
{
    const ELEMENT_NAME_ATTRIBUTE_NAME_DELIMITER = '.';

    private $pageElementReferenceResolver;

    public function __construct(PageElementReferenceResolver $pageElementReferenceResolver)
    {
        $this->pageElementReferenceResolver = $pageElementReferenceResolver;
    }

    public static function createResolver(): ValueResolver
    {
        return new ValueResolver(
            PageElementReferenceResolver::createResolver()
        );
    }

    /**
     * @param ValueInterface $value
     * @param PageProviderInterface $pageProvider
     * @param IdentifierCollectionInterface $identifierCollection
     *
     * @return ValueInterface
     *
     * @throws InvalidPageElementIdentifierException
     * @throws MalformedPageElementReferenceException
     * @throws NonRetrievablePageException
     * @throws UnknownElementException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolve(
        ValueInterface $value,
        PageProviderInterface $pageProvider,
        IdentifierCollectionInterface $identifierCollection
    ): ValueInterface {
        $value = $this->resolvePageElementReference($value, $pageProvider);
        $value = $this->resolveElementParameter($value, $identifierCollection);
        $value = $this->resolveAttributeParameter($value, $identifierCollection);

        return $value;
    }

    /**
     * @param ValueInterface $value
     * @param PageProviderInterface $pageProvider
     *
     * @return ValueInterface
     *
     * @throws InvalidPageElementIdentifierException
     * @throws MalformedPageElementReferenceException
     * @throws NonRetrievablePageException
     * @throws UnknownPageElementException
     * @throws UnknownPageException
     */
    public function resolvePageElementReference(
        ValueInterface $value,
        PageProviderInterface $pageProvider
    ): ValueInterface {
        if ($value instanceof ObjectValue && ValueTypes::PAGE_ELEMENT_REFERENCE === $value->getType()) {
            return new ElementValue(
                $this->pageElementReferenceResolver->resolve($value, $pageProvider)
            );
        }

        return $value;
    }

    /**
     * @param ValueInterface $value
     * @param IdentifierCollectionInterface $identifierCollection
     *
     * @return ValueInterface
     *
     * @throws UnknownElementException
     */
    public function resolveElementParameter(
        ValueInterface $value,
        IdentifierCollectionInterface $identifierCollection
    ): ValueInterface {
        if ($value instanceof ObjectValue && ValueTypes::ELEMENT_PARAMETER === $value->getType()) {
            return new ElementValue(
                $this->findElementIdentifier($identifierCollection, $value->getObjectProperty())
            );
        }

        return $value;
    }

    /**
     * @param ValueInterface $value
     * @param IdentifierCollectionInterface $identifierCollection
     *
     * @return ValueInterface
     *
     * @throws UnknownElementException
     */
    public function resolveAttributeParameter(
        ValueInterface $value,
        IdentifierCollectionInterface $identifierCollection
    ): ValueInterface {
        if ($value instanceof ObjectValue && ValueTypes::ATTRIBUTE_PARAMETER === $value->getType()) {
            $objectProperty = $value->getObjectProperty();

            if (substr_count($objectProperty, self::ELEMENT_NAME_ATTRIBUTE_NAME_DELIMITER) > 0) {
                list($elementName, $attributeName) = explode('.', $value->getObjectProperty());

                $elementIdentifier = $this->findElementIdentifier($identifierCollection, $elementName);
                $attributeIdentifier = new AttributeIdentifier($elementIdentifier, $attributeName);

                return new AttributeValue($attributeIdentifier);
            }
        }

        return $value;
    }

    /**
     * @param IdentifierCollectionInterface $identifierCollection
     *
     * @param string $elementName
     *
     * @return ElementIdentifierInterface
     * @throws UnknownElementException
     */
    private function findElementIdentifier(
        IdentifierCollectionInterface $identifierCollection,
        string $elementName
    ): ElementIdentifierInterface {
        $identifier = $identifierCollection->getIdentifier($elementName);

        if (!$identifier instanceof ElementIdentifierInterface) {
            throw new UnknownElementException($elementName);
        }

        return $identifier;
    }
}