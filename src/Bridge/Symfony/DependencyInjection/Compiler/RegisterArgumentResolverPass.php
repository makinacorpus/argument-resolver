<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Bridge\Symfony\DependencyInjection\Compiler;

use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;
use MakinaCorpus\ArgumentResolver\Metadata\CallableMetadataFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class RegisterArgumentResolverPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $registered = [];

        foreach ($container->findTaggedServiceIds('argument_resolver') as $serviceId => $attributes) {
            $id = $attributes[0]['id'] ?? null;
            if (!$id) {
                throw new InvalidArgumentException(\sprintf("Service '%s' uses the 'argument_resolver' tag without setting the 'id' attribute.", $serviceId));
            }
            if (isset($registered[$id])) {
                throw new InvalidArgumentException(\sprintf("Service '%s' uses the 'argument_resolver' and redefined the '%s' value for the 'id' attribute.", $serviceId, $id));
            }

            $definition = $container->getDefinition($serviceId);
            if (!$definition->getClass()) {
                $definition->setClass(DefaultArgumentResolver::class);
            }
            if (!$definition->getArguments()) {
                $definition->setArgument(0, new Reference(CallableMetadataFactory::class));
            }

            // Register a predictible alias for the argument resolver.
            $newAlias = 'argument_resolver.' . $id;
            if ($newAlias !== $serviceId) {
                $container->setAlias($newAlias, $serviceId);
            }

            $registered[$id] = $definition;
        }

        // Add "argument_resolver.ID" tags for all identifiers, to all "default"
        // identified value resolvers, to propagate them to all definitions.
        // This allow us to preserve the priority.
        foreach (\array_keys($registered) as $id) {
            foreach ($container->findTaggedServiceIds('argument_resolver.default') as $serviceId => $attributes) {
                $definition = $container->getDefinition($serviceId);
                if (!$definition->hasTag('argument_resolver.' . $id)) {
                    $definition->addTag('argument_resolver.' . $id, $attributes[0]);
                }
            }
        }

        foreach ($registered as $id => $definition) {
            // Find already set value resolver for this service, they could exist.
            $valueResolvers = $definition->getArguments()[1] ?? null;
            if ($valueResolvers) {
                if (!\is_array($valueResolvers)) {
                    $valueResolvers = [$valueResolvers];
                }
            } else {
                $valueResolvers = [];
            }

            // They are sorted, just set everything into place.
            foreach ($this->findAndSortTaggedServices('argument_resolver.' . $id, $container) as $reference) {
                $valueResolvers[] = $reference;
            }
            $definition->setArgument(1, $valueResolvers);
        }
    }
}
