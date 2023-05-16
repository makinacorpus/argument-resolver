<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver;

use MakinaCorpus\ArgumentResolver\Context\ArrayResolverContext;
use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Error\MissingArgumentError;
use MakinaCorpus\ArgumentResolver\Error\TooManyArgumentError;
use MakinaCorpus\ArgumentResolver\Metadata\CallableMetadataFactory;
use MakinaCorpus\ArgumentResolver\Metadata\ReflectionCallableMetadataFactory;
use MakinaCorpus\ArgumentResolver\Resolver\ArgumentValueResolver;
use MakinaCorpus\ArgumentResolver\Resolver\ContextArgumentValueResolver;
use MakinaCorpus\ArgumentResolver\Resolver\DefaultArgumentValueResolver;

/**
 * Code inspired by symfony/http-kernel code, with some minor fixes to make
 * it more flexible. We are not API compatible with Symfony, because it
 * hardwires the Request object, whereas we choose to change it to be more
 * flexible and give the user an interface to play with.
 */
class DefaultArgumentResolver implements ArgumentResolver
{
    private CallableMetadataFactory $callbackMetadataFactory;
    private ?iterable $argumentValueResolvers = null;

    public function __construct(?CallableMetadataFactory $callbackMetadataFactory = null, ?iterable $argumentValueResolvers = null)
    {
        $this->callbackMetadataFactory = $callbackMetadataFactory ?? new ReflectionCallableMetadataFactory();
        $this->argumentValueResolvers = $argumentValueResolvers ?? $this->createDefaultArgumentResolvers();
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(callable $callback, ?ResolverContext $context = null): array
    {
        $arguments = [];
        $context = $context ?? new ArrayResolverContext();

        foreach ($this->callbackMetadataFactory->getArgumentsMetadata($callback) as $argument) {
            foreach ($this->argumentValueResolvers as $resolver) {
                \assert($resolver instanceof ArgumentValueResolver);

                if (!$resolver->supports($argument, $context)) {
                    continue;
                }

                $resolved = $resolver->resolve($argument, $context);

                $count = 0;
                foreach ($resolved as $append) {
                    $count++;
                    $arguments[] = $append;
                }

                if (!$count) {
                    // No values for a variadic argument is valid.
                    if (!$argument->isVariadic()) {
                        throw new MissingArgumentError(sprintf("'%s::resolve()' must yield at least one value.", \get_debug_type($resolver)));
                    }
                }
                if (1 < $count && !$argument->isVariadic()) {
                    throw new TooManyArgumentError(\sprintf("'%s::resolve()' yielded more than one value for a non variadic argument.", \get_debug_type($resolver)));
                }

                // Continue to the next controller argument.
                continue 2;
            }

            $representative = $callback;
            if (\is_array($representative)) {
                $representative = sprintf('%s::%s()', (\is_object($representative[0]) ? \get_class($representative[0]) : $representative[0]), $representative[1]);
            } elseif (\is_object($representative)) {
                $representative = \get_class($representative);
            }

            throw new MissingArgumentError(\sprintf("Callable '%s' requires that you provide a value for the '$%s' argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.", $representative, $argument->getName()));
        }

        return $arguments;
    }

    /**
     * Default argument resolvers.
     */
    private function createDefaultArgumentResolvers(): array
    {
        return [
            // And this should always be the first in default configuration.
            new ContextArgumentValueResolver(),
            // In real life, this must be the very last one.
            new DefaultArgumentValueResolver(),
        ];
    }
}
