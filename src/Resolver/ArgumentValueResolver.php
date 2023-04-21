<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Resolver;

use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;

/**
 * This is where this API is extended.
 */
interface ArgumentValueResolver
{
    /**
     * Is this argument supported.
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool;

    /**
     * Resolve one or more values for the given arguments.
     *
     * In most cases, this should resolve only a single value, multiple values
     * are allowed only for variadic arguments.
     *
     * This method can be a generator, or may return an array.
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable;
}
