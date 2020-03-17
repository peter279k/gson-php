<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Tebru\Gson\TypeAdapter;

use Tebru\Gson\Context\ReaderContext;
use Tebru\Gson\Context\WriterContext;
use Tebru\Gson\Exception\JsonSyntaxException;
use Tebru\Gson\TypeAdapter;
use Tebru\PhpType\TypeToken;

/**
 * Class ArrayTypeAdapter
 *
 * @author Nate Brunette <n@tebru.net>
 */
class ArrayTypeAdapter extends TypeAdapter
{
    /**
     * A TypeAdapter cache keyed by raw type
     *
     * @var TypeAdapter[]
     */
    protected $adapters = [];

    /**
     * Read the next value, convert it to its type and return it
     *
     * @param array|null $value
     * @param ReaderContext $context
     * @return array|null
     */
    public function read($value, ReaderContext $context): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new JsonSyntaxException(sprintf('Could not parse json, expected array or object but found "%s"', gettype($value)));
        }

        $result = [];

        $arrayIsObject = is_string(key($value));
        $enableScalarAdapters = $context->enableScalarAdapters();
        $typeAdapterProvider = $context->getTypeAdapterProvider();

        foreach ($value as $key => $item) {
            if (!$enableScalarAdapters && ($item === null || is_scalar($item))) {
                $itemValue = $item;
            } else {
                $type = TypeToken::createFromVariable($item);
                $adapter = $this->adapters[$type->rawType] ?? $this->adapters[$type->rawType] = $typeAdapterProvider->getAdapter($type);
                $itemValue = $adapter->read($item, $context);
            }

            $result[$arrayIsObject ? (string)$key : (int)$key] = $itemValue;
        }

        return $result;
    }

    /**
     * Write the value to the writer for the type
     *
     * @param array|null $value
     * @param WriterContext $context
     * @return array|null
     */
    public function write($value, WriterContext $context): ?array
    {
        if ($value === null) {
            return null;
        }

        $arrayIsObject = is_string(key($value));
        $enableScalarAdapters = $context->enableScalarAdapters();
        $serializeNull = $context->serializeNull();
        $typeAdapterProvider = $context->getTypeAdapterProvider();
        $result = [];

        foreach ($value as $key => $item) {
            if ($item === null && !$serializeNull) {
                continue;
            }

            if (!$enableScalarAdapters && is_scalar($item)) {
                $result[$arrayIsObject ? (string)$key : (int)$key] = $item;
                continue;
            }

            $itemValue = null;
            $type = TypeToken::createFromVariable($item);
            $adapter = $this->adapters[$type->rawType] ?? $this->adapters[$type->rawType] = $typeAdapterProvider->getAdapter($type);
            $itemValue = $adapter->write($item, $context);

            if ($itemValue === null && !$serializeNull) {
                continue;
            }

            $result[$arrayIsObject ? (string)$key : (int)$key] = $itemValue;
        }

        return $result;
    }

    /**
     * Return true if object can be written to disk
     *
     * @return bool
     */
    public function canCache(): bool
    {
        return true;
    }

    public function __sleep()
    {
        return [];
    }
}
