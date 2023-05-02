<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Context;

use MakinaCorpus\ArgumentResolver\Error\MissingArgumentError;
use MakinaCorpus\ArgumentResolver\Resolver\ValueChoices;

class ArrayResolverContext implements ResolverContext
{
    private array $data;

    public function __construct(?array $data = null)
    {
        $this->data = $data ?? [];
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name, ?array $allowedTypes = null): mixed
    {
        if (!\array_key_exists($name, $this->data)) {
            throw new MissingArgumentError(\sprintf("Value named '%s' does not exist in context.", $name));
        }
        $value = $this->data[$name];

        if ($allowedTypes) {
            return ValueChoices::wrap($value)->find($allowedTypes);
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): iterable
    {
        return $this->data;
    }
}
