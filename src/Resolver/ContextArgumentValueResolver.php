<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Resolver;

use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;

class ContextArgumentValueResolver implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return $context->has($argument->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        yield $context->get($argument->getName(), $argument->getTypes());
    }
}
