<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Resolver;

use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;
use Psr\Container\ContainerInterface;

final class ServiceArgumentValueResolver implements ArgumentValueResolver
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        foreach ($argument->getTypes() as $type) {
            if (\class_exists($type) || \interface_exists($type) && $this->container->has($type)) {
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
        foreach ($argument->getTypes() as $type) {
            if ($this->container->has($type)) {
                yield $this->container->get($type);

                // There might multiple services with different types, we are
                // only returning one, so just return the first one.
                return;
            }
        }
    }
}
