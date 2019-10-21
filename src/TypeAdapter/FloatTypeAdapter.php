<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

declare(strict_types=1);

namespace Tebru\Gson\TypeAdapter;

use Tebru\Gson\Context\ReaderContext;
use Tebru\Gson\TypeAdapter;
use Tebru\Gson\Context\WriterContext;

/**
 * Class FloatTypeAdapter
 *
 * @author Nate Brunette <n@tebru.net>
 */
class FloatTypeAdapter extends TypeAdapter
{
    /**
     * Read the next value, convert it to its type and return it
     *
     * @param float|null $value
     * @param ReaderContext $context
     * @return float|null
     */
    public function read($value, ReaderContext $context): ?float
    {
        return $value === null ? null : (float)$value;
    }

    /**
     * Write the value to the writer for the type
     *
     * @param float|null $value
     * @param WriterContext $context
     * @return float|null
     */
    public function write($value, WriterContext $context): ?float
    {
        return $value === null ? null : (float)$value;
    }
}