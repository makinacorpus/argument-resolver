<?php

namespace MakinaCorpus\ArgumentResolver\Tests\Metadata;

use MakinaCorpus\ArgumentResolver\Metadata\ReflectionCallableMetadataFactory;
use PHPUnit\Framework\TestCase;

class ReflectionCallableMetadataFactoryTest extends TestCase
{
    public function testNoArgument(): void
    {
        $factory = new ReflectionCallableMetadataFactory();
        $callback = fn () => null;

        self::assertCount(0, $factory->getArgumentsMetadata($callback));
    }

    public function testUnionTypeArgument(): void
    {
        $factory = new ReflectionCallableMetadataFactory();
        $callback = fn (string|int $union) => null;

        $arguments = $factory->getArgumentsMetadata($callback);
        self::assertCount(1, $arguments);
        self::assertSame('union', $arguments[0]->getName());
        self::assertSame(['string', 'int'], $arguments[0]->getTypes());
        self::assertFalse($arguments[0]->isNullable());
    }

    public function testNamedTypeArgument(): void
    {
        $factory = new ReflectionCallableMetadataFactory();
        $callback = fn (\DateTime $someDate) => null;

        $arguments = $factory->getArgumentsMetadata($callback);
        self::assertCount(1, $arguments);
        self::assertSame('someDate', $arguments[0]->getName());
        self::assertSame([\DateTime::class], $arguments[0]->getTypes());
        self::assertFalse($arguments[0]->isNullable());
    }

    public function testNoTypeArgument(): void
    {
        $factory = new ReflectionCallableMetadataFactory();
        $callback = fn ($someMixed) => null;

        $arguments = $factory->getArgumentsMetadata($callback);
        self::assertCount(1, $arguments);
        self::assertSame('someMixed', $arguments[0]->getName());
        self::assertSame([], $arguments[0]->getTypes());
        self::assertTrue($arguments[0]->isNullable());
    }

    public function testWithArrayInstanceMethod(): void
    {
        self::markTestIncomplete();
    }

    public function testWithArrayStaticMethod(): void
    {
        self::markTestIncomplete();
    }

    public function testWithStringFunction(): void
    {
        self::markTestIncomplete();
    }

    public function testWithStringStaticMethod(): void
    {
        self::markTestIncomplete();
    }

    public function testWithFunction(): void
    {
        self::markTestIncomplete();
    }
}

class WithCallableMethod
{
    public static function staticCallableMethod(string $bar): void
    {
    }

    public function instanceCallableMethod(\DateTimeImmutable $bar, int $bla): void
    {
    }
}

function callable_function(int $foo)
{
}
