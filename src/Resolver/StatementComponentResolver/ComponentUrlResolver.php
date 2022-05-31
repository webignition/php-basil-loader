<?php

declare(strict_types=1);

namespace webignition\BasilLoader\Resolver\StatementComponentResolver;

use webignition\BasilLoader\Resolver\ImportedUrlResolver;
use webignition\BasilLoader\Resolver\ResolvedComponent;
use webignition\BasilModelProvider\Exception\UnknownItemException;
use webignition\BasilModelProvider\ProviderInterface;
use webignition\BasilModels\PageProperty\PageProperty;

class ComponentUrlResolver implements StatementComponentResolverInterface
{
    public function __construct(
        private ImportedUrlResolver $importedUrlResolver
    ) {
    }

    public static function createResolver(): self
    {
        return new ComponentUrlResolver(
            ImportedUrlResolver::createResolver()
        );
    }

    /**
     * @throws UnknownItemException
     */
    public function resolve(
        ?string $data,
        ProviderInterface $pageProvider,
        ProviderInterface $identifierProvider
    ): ?ResolvedComponent {
        if (is_string($data) && false === PageProperty::is($data)) {
            $resolvedData = $this->importedUrlResolver->resolve($data, $pageProvider);

            if ($data !== $resolvedData) {
                $resolvedData = '"' . $resolvedData . '"';
            }

            return new ResolvedComponent(
                $data,
                $resolvedData
            );
        }

        return null;
    }
}
