<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Bridge\Symfony;

use MakinaCorpus\ArgumentResolver\Bridge\Symfony\DependencyInjection\ArgumentResolverExtension;
use MakinaCorpus\ArgumentResolver\Bridge\Symfony\DependencyInjection\Compiler\RegisterArgumentResolverPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class ArgumentResolverBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RegisterArgumentResolverPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ArgumentResolverExtension();
    }
}
