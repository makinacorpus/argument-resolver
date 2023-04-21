<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver;

use MakinaCorpus\ArgumentResolver\Context\ResolverContext;

interface ArgumentResolver
{
    /**
     * Find arguments for callback.
     */
    public function getArguments(callable $callback, ?ResolverContext $context = null): array;
}
