<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Metadata;

/**
 * This exists as an interface to allow cache layers to exist.
 */
interface CallableMetadataFactory
{
    /**
     * Find arguments metadata for callback.
     *
     * @return ArgumentMetadata[]
     */
    public function getArgumentsMetadata(callable $callback): array;
}
