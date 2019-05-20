<?php

namespace webignition\BasilParser\Model\Assertion;

use webignition\BasilParser\Model\Identifier\IdentifierInterface;
use webignition\BasilParser\Model\Value\ValueInterface;

class Assertion implements AssertionInterface
{
    private $assertionString;
    private $identifier;
    private $comparison;
    private $value;

    public function __construct(
        string $assertionString,
        IdentifierInterface $identifier,
        string $comparison,
        ?ValueInterface $value = null
    ) {
        $this->assertionString = $assertionString;
        $this->identifier = $identifier;
        $this->comparison = $comparison;
        $this->value = $value;
    }

    public function getAssertionString(): string
    {
        return $this->assertionString;
    }

    public function getIdentifier(): IdentifierInterface
    {
        return $this->identifier;
    }

    public function getComparison(): string
    {
        return $this->comparison;
    }

    public function getValue(): ?ValueInterface
    {
        return $this->value;
    }
}