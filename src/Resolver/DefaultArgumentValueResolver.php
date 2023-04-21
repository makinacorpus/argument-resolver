<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Resolver;

use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;

class DefaultArgumentValueResolver implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return $argument->isNullable() || $argument->isVariadic() || $argument->hasDefaultValue();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        if ($argument->hasDefaultValue()) {
            yield $argument->getDefaultValue();
        } else if (!$argument->isVariadic()) {
            yield null;
        }
    }
}
