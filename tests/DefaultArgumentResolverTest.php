<?php

declare(strict_types=1);

namespace MakinaCorpus\ArgumentResolver\Tests;

use MakinaCorpus\ArgumentResolver\DefaultArgumentResolver;
use MakinaCorpus\ArgumentResolver\Context\ArrayResolverContext;
use MakinaCorpus\ArgumentResolver\Context\ResolverContext;
use MakinaCorpus\ArgumentResolver\Error\MissingArgumentError;
use MakinaCorpus\ArgumentResolver\Error\TooManyArgumentError;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;
use MakinaCorpus\ArgumentResolver\Resolver\ArgumentValueResolver;
use PHPUnit\Framework\TestCase;

class DefaultArgumentResolverTest extends TestCase
{
    public function testValueResolverEmptyResolveError(): void
    {
        $resolver = new DefaultArgumentResolver(null, [new ValueResolveEmpty()]);

        self::expectException(MissingArgumentError::class);
        self::expectExceptionMessageMatches('/must yield at least/');
        $resolver->getArguments(fn (int $bar) => null);
    }

    public function testValueResolverMoreThanOneWhenNotVariadicError(): void
    {
        $resolver = new DefaultArgumentResolver(null, [new ValueResolveMoreThanOnce()]);

        self::expectException(TooManyArgumentError::class);
        self::expectExceptionMessageMatches('/yielded more than one value/');
        $resolver->getArguments(fn (int $bar) => null);
    }

    public function testNoResolverError(): void
    {
        $resolver = new DefaultArgumentResolver(null, []);

        self::expectException(MissingArgumentError::class);
        self::expectExceptionMessageMatches('/requires that you provide a value for the \'\$bar\'/');
        $resolver->getArguments(fn (int $bar) => null);
    }

    public function testNoValueResolverError(): void
    {
        $resolver = new DefaultArgumentResolver(null, [new ValueResolveNever()]);

        self::expectException(MissingArgumentError::class);
        self::expectExceptionMessageMatches('/requires that you provide a value for the \'\$bar\'/');
        $resolver->getArguments(fn (int $bar) => null);
    }

    public function testThatWorks(): void
    {
        $resolver = new DefaultArgumentResolver(null, [new ValueResolveJustFine()]);

        $arguments = $resolver->getArguments(fn (int $bar, int $foo) => null);
        self::assertSame([1, 1], $arguments);
    }

    public function testDefaultContextBeforeNullable(): void
    {
        $resolver = new DefaultArgumentResolver();
        $context = new ArrayResolverContext(['bar' => 12]);

        $arguments = $resolver->getArguments(fn (?int $bar) => null, $context);
        self::assertSame([12], $arguments);
    }

    public function testDefaultNullable(): void
    {
        $resolver = new DefaultArgumentResolver();
        $context = new ArrayResolverContext(['other' => 12]);

        $arguments = $resolver->getArguments(fn (?int $bar) => null, $context);
        self::assertSame([null], $arguments);
    }

    public function testDefaultNullableWithVariadic(): void
    {
        $resolver = new DefaultArgumentResolver();
        $context = new ArrayResolverContext(['other' => 12]);

        $arguments = $resolver->getArguments(fn (int ...$bar) => null, $context);
        self::assertSame([], $arguments);
    }
}

class ValueResolveNever implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        return [];
    }
}

class ValueResolveEmpty implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        return [];
    }
}

class ValueResolveJustFine implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return \in_array('int', $argument->getTypes());
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        yield 1;
    }
}

class ValueResolveMoreThanOnce implements ArgumentValueResolver
{
    /**
     * {@inheritdoc}
     */
    public function supports(ArgumentMetadata $argument, ResolverContext $context): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ArgumentMetadata $argument, ResolverContext $context): iterable
    {
        yield 1;
        yield 2;
    }
}
