<?php

namespace MakinaCorpus\ArgumentResolver\Metadata;

class ArgumentMetadata
{
    private string $name;
    private array $types;
    private bool $isVariadic = false;
    private bool $hasDefaultValue = false;
    private mixed $defaultValue = null;
    private bool $isNullable = false;

    public function __construct(
        string $name,
        array $types = [],
        bool $isVariadic = false,
        bool $hasDefaultValue = false,
        mixed $defaultValue = null,
        bool $isNullable = false
    ) {
        $this->name = $name;
        $this->types = $types;
        $this->isVariadic = $isVariadic;
        $this->hasDefaultValue = $hasDefaultValue;
        $this->defaultValue = $defaultValue;
        $this->isNullable = $isNullable || !$types || \in_array('mixed', $types) || ($hasDefaultValue && null === $defaultValue);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }

    public function hasDefaultValue(): bool
    {
        return $this->hasDefaultValue;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function getDefaultValue(): mixed
    {
        if (!$this->hasDefaultValue) {
            throw new \LogicException(sprintf('Argument $%s does not have a default value. Use "%s::hasDefaultValue()" to avoid this exception.', $this->name, __CLASS__));
        }
        return $this->defaultValue;
    }
}
