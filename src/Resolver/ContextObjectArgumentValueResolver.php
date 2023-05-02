<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Resolver;

use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;

/**
 * For unit testing only, will use context object types to match with
 * arguments instead of context keys.
 */
class ContextObjectArgumentValueResolver implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        foreach ($context->all() as $value) {
            if (\is_object($value) && $this->typeIsCompatible($value, $argument)) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        foreach ($context->all() as $value) {
            if (\is_object($value) && $this->typeIsCompatible($value, $argument)) {
                yield $value;
                return;
            }
        }
    }

    /**
     * Given object is a subclass of or implements interface of argument.
     */
    private function typeIsCompatible(object $candidate, ArgumentMetadata $argument): bool
    {
        $refClass = new \ReflectionClass($candidate);

        foreach ($argument->getTypes() as $candidate) {
            if ($refClass->getName() === $candidate) {
                return true;
            }

            if (\class_exists($candidate)) {
                if ($refClass->isSubclassOf($candidate)) {
                    return true;
                }
                continue;
            }

            if (\interface_exists($candidate)) {
                if ($refClass->implementsInterface($candidate)) {
                    return true;
                }
                continue;
            }
        }

        return false;
    }
}
