<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Tebru\Gson\TypeAdapter;

use Tebru\Gson\Context\ReaderContext;
use Tebru\Gson\Context\WriterContext;
use Tebru\Gson\Internal\DefaultJsonDeserializationContext;
use Tebru\Gson\Internal\DefaultJsonSerializationContext;
use Tebru\Gson\JsonDeserializer;
use Tebru\Gson\JsonSerializer;
use Tebru\Gson\TypeAdapter;
use Tebru\Gson\TypeAdapterFactory;
use Tebru\PhpType\TypeToken;

/**
 * Class CustomWrappedTypeAdapter
 *
 * Wraps a [@see JsonSerializer] or [@see JsonDeserializer] and delegates if either is null
 *
 * @author Nate Brunette <n@tebru.net>
 */
class CustomWrappedTypeAdapter extends TypeAdapter
{
    /**
     * @var TypeToken
     */
    protected $type;

    /**
     * @var JsonSerializer
     */
    protected $serializer;

    /**
     * @var JsonDeserializer
     */
    protected $deserializer;

    /**
     * @var TypeAdapterFactory
     */
    protected $skip;

    /**
     * Cached instance of the delegate type adapter
     *
     * @var TypeAdapter
     */
    protected $delegateTypeAdapter;

    /**
     * Constructor
     *
     * @param TypeToken $type
     * @param JsonSerializer|null $serializer
     * @param JsonDeserializer|null $deserializer
     * @param TypeAdapterFactory|null $skip
     */
    public function __construct(
        TypeToken $type,
        JsonSerializer $serializer = null,
        JsonDeserializer $deserializer = null,
        TypeAdapterFactory $skip = null
    ) {
        $this->type = $type;
        $this->serializer = $serializer;
        $this->deserializer = $deserializer;
        $this->skip = $skip;
    }

    /**
     * Read the next value, convert it to its type and return it
     *
     * @param mixed $value
     * @param ReaderContext $context
     * @return mixed
     */
    public function read($value, ReaderContext $context)
    {
        $provider = $context->getTypeAdapterProvider();
        if ($this->deserializer === null) {
            $this->delegateTypeAdapter = $this->delegateTypeAdapter ?? $provider->getAdapter($this->type, $this->skip);

            return $this->delegateTypeAdapter->read($value, $context);
        }

        if ($value === null) {
            return null;
        }

        return $this->deserializer->deserialize(
            $value,
            $this->type,
            new DefaultJsonDeserializationContext($provider, $context)
        );
    }

    /**
     * Write the value to the writer for the type
     *
     * @param mixed $value
     * @param WriterContext $context
     * @return mixed
     */
    public function write($value, WriterContext $context)
    {
        $provider = $context->getTypeAdapterProvider();
        if ($this->serializer === null) {
            $this->delegateTypeAdapter = $this->delegateTypeAdapter ?? $provider->getAdapter($this->type, $this->skip);

            return $this->delegateTypeAdapter->write($value, $context);
        }

        if ($value === null) {
            return null;
        }

        return $this->serializer->serialize(
            $value,
            $this->type,
            new DefaultJsonSerializationContext($provider, $context)
        );
    }

    /**
     * Return true if object can be written to disk
     *
     * @return bool
     */
    public function canCache(): bool
    {
        $cacheSerializer = true;
        $cacheDeserializer = true;

        if ($this->serializer !== null) {
            $cacheSerializer = $this->serializer->canCache();
        }

        if ($this->deserializer !== null) {
            $cacheDeserializer = $this->deserializer->canCache();
        }

        return $cacheSerializer && $cacheDeserializer;
    }
}
