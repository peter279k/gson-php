<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\Gson\Test\Unit\TypeAdapter\Factory;


use PHPUnit\Framework\TestCase;
use stdClass;
use Tebru\Gson\TypeAdapter\Factory\ArrayTypeAdapterFactory;
use Tebru\Gson\TypeAdapter\IntegerTypeAdapter;
use Tebru\Gson\Test\MockProvider;
use Tebru\Gson\TypeAdapter\ScalarArrayTypeAdapter;
use Tebru\Gson\TypeAdapter\WildcardTypeAdapter;
use Tebru\PhpType\TypeToken;

/**
 * Class ArrayTypeAdapterFactoryTest
 *
 * @author Nate Brunette <n@tebru.net>
 * @covers \Tebru\Gson\TypeAdapter\Factory\ArrayTypeAdapterFactory
 */
class ArrayTypeAdapterFactoryTest extends TestCase
{
    public function testInvalidSupports(): void
    {
        $factory = new ArrayTypeAdapterFactory(false);
        $phpType = new TypeToken('string');
        $typeAdapterProvider = MockProvider::typeAdapterProvider();
        $adapter = $factory->create($phpType, $typeAdapterProvider);

        self::assertNull($adapter);
    }

    public function testCreate(): void
    {
        $factory = new ArrayTypeAdapterFactory(false);
        $phpType = new TypeToken('array');
        $typeAdapterProvider = MockProvider::typeAdapterProvider();
        $adapter = $factory->create($phpType, $typeAdapterProvider);

        self::assertAttributeSame($typeAdapterProvider, 'typeAdapterProvider', $adapter);
        self::assertAttributeSame(TypeToken::create(TypeToken::WILDCARD), 'keyType', $adapter);
        self::assertAttributeEquals(new WildcardTypeAdapter($typeAdapterProvider), 'valueTypeAdapter', $adapter);
        self::assertAttributeSame(0, 'numberOfGenerics', $adapter);
    }

    public function testCreateScalar(): void
    {
        $factory = new ArrayTypeAdapterFactory(false);
        $phpType = new TypeToken('array<int>');
        $typeAdapterProvider = MockProvider::typeAdapterProvider();
        $adapter = $factory->create($phpType, $typeAdapterProvider);

        self::assertInstanceOf(ScalarArrayTypeAdapter::class, $adapter);
    }

    public function testCreateStdClass(): void
    {
        $factory = new ArrayTypeAdapterFactory(false);
        $phpType = new TypeToken('stdClass');
        $typeAdapterProvider = MockProvider::typeAdapterProvider();
        $adapter = $factory->create($phpType, $typeAdapterProvider);

        self::assertAttributeSame($typeAdapterProvider, 'typeAdapterProvider', $adapter);
        self::assertAttributeSame(TypeToken::create(TypeToken::WILDCARD), 'keyType', $adapter);
        self::assertAttributeEquals(new WildcardTypeAdapter($typeAdapterProvider), 'valueTypeAdapter', $adapter);
        self::assertAttributeSame(0, 'numberOfGenerics', $adapter);
    }

    public function testCreateOneGenericType(): void
    {
        $factory = new ArrayTypeAdapterFactory(true);
        $phpType = new TypeToken('array<int>');
        $typeAdapterProvider = MockProvider::typeAdapterProvider();
        $adapter = $factory->create($phpType, $typeAdapterProvider);

        self::assertAttributeSame($typeAdapterProvider, 'typeAdapterProvider', $adapter);
        self::assertAttributeSame(TypeToken::create(TypeToken::WILDCARD), 'keyType', $adapter);
        self::assertAttributeEquals(new IntegerTypeAdapter(), 'valueTypeAdapter', $adapter);
        self::assertAttributeSame(1, 'numberOfGenerics', $adapter);
    }

    public function testCreateTwoGenericTypes(): void
    {
        $factory = new ArrayTypeAdapterFactory(true);
        $phpType = new TypeToken('array<string, int>');
        $typeAdapterProvider = MockProvider::typeAdapterProvider();
        $adapter = $factory->create($phpType, $typeAdapterProvider);

        self::assertAttributeSame(TypeToken::create('string'), 'keyType', $adapter);
        self::assertAttributeEquals(new IntegerTypeAdapter(), 'valueTypeAdapter', $adapter);
        self::assertAttributeSame(2, 'numberOfGenerics', $adapter);
    }
}