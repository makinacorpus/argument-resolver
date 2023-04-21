<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Context;

interface ResolverContext
{
    public function has(string $name): bool;

    public function get(string $name, ?array $allowedTypes = null): mixed;
}
