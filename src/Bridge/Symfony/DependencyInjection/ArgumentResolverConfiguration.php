<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class ArgumentResolverConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('argument_resolver');

        // $rootNode = $treeBuilder->getRootNode();

        return $treeBuilder;
    }
}
