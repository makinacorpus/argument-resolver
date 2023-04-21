<?php

namespace MakinaCorpus\ArgumentResolver\Tests\Metadata;

use PHPUnit\Framework\TestCase;
use MakinaCorpus\ArgumentResolver\Metadata\ArgumentMetadata;

class ArgumentMetadataTest extends TestCase
{
    public function testDefaultValue(): void
    {
        // Common case.
        $argument = new ArgumentMetadata('foo', ['int'], false, false, null, false);
        self::assertFalse($argument->isNullable());

        // Second common case.
        $argument = new ArgumentMetadata('foo', ['int'], false, false, null, true);
        self::assertTrue($argument->isNullable());

        // Because there is not types.
        $argument = new ArgumentMetadata('foo', [], false, false, null, false);
        self::assertTrue($argument->isNullable());

        // Because there is mixed type.
        $argument = new ArgumentMetadata('foo', ['int', 'mixed'], false, false, null, false);
        self::assertTrue($argument->isNullable());

        // Because there is a default value.
        $argument = new ArgumentMetadata('foo', ['int', 'mixed'], false, true, null, false);
        self::assertTrue($argument->isNullable());
    }
}
