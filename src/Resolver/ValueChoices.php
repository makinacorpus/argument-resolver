<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Resolver;

use MakinaCorpus\ArgumentResolver\Error\MissingArgumentError;

/**
 * Allow value choices under a resolver context single name, with different
 * types, and choice the right one accordingly. This allows some advanced
 * usage of the API where you have more than one value for a single parameter
 * with different types.
 */
class ValueChoices
{
    private array $choices;

    public function __construct(array $choices)
    {
        $this->choices = \array_values($choices);
    }

    public static function wrap(mixed $data): self
    {
        if ($data instanceof self) {
            return $data;
        }
        if (\is_array($data)) {
            return new self($data);
        }
        return new self([$data]);
    }

    /**
     * Find any value in the choices that match one of the given types.
     *
     * @param string|string[] $type
     */
    public function find($types)
    {
        if (!$this->choices) {
            return null;
        }

        if (!$types) {
            return $this->choices[0];
        }

        $types = (array)$types;

        foreach ($types as $type) {
            if ('resource' === $type || 'callable' === $type) {
                return null; // Do not support resources or callables.
            }
            if ('mixed' === $type) {
                return $this->choices[0];
            }

            foreach ($this->choices as $choice) {
                if (\class_exists($type) || \interface_exists($type)) {
                    if (\is_object($choice) && $choice instanceof $type) {
                        return $choice;
                    }
                    continue;
                }

                if (\is_bool($choice)) {
                    if ('bool' === $type) {
                        return $choice;
                    }
                    continue;
                }

                if (\is_int($choice)) {
                    if ('int' === $type) {
                        return $choice;
                    }
                    continue;
                }

                if (\is_float($choice)) {
                    if ('float' === $type) {
                        return $choice;
                    }
                    continue;
                }

                if (\is_string($choice)) {
                    if ('string' === $type) {
                        return $choice;
                    }
                    continue;
                }

                if (\is_array($choice)) {
                    if ('array' === $type) {
                        return $choice;
                    }
                    continue;
                }

                if (\is_iterable($choice)) {
                    if ('iterable' === $type || 'array' === $type) {
                        return $choice;
                    }
                    continue;
                }
            }
        }

        throw new MissingArgumentError(\sprintf("Could not find value for given types '%s'", \implode(', ', $types)));
    }
}
