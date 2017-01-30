<?php
/*
 * Copyright (c) Nate Brunette.
 * Distributed under the MIT License (http://opensource.org/licenses/MIT)
 */

namespace Tebru\Gson\Internal;

use Tebru\Collection\ArrayList;
use Tebru\Collection\HashMap;
use Tebru\Gson\Exception\MalformedTypeException;

/**
 * Class PhpType
 *
 * Wrapper around core php types and custom types.  It can be as simply as
 *
 *     new PhpType('string');
 *
 * To create a string type.
 *
 * This class also allows us to fake generic types.  The syntax to
 * represent generics uses angle brackets <>.
 *
 * For example:
 *
 *     ArrayList<int>
 *
 * Would represent an ArrayList of ints.
 *
 *     HashMap<string, int>
 *
 * Would represent a HashMap using string keys and int values.
 *
 * They can be combined, like so
 *
 *     HashMap<string, ArrayList<int>>
 *
 * To represent a HashMap with string keys and an ArrayList of ints as values.
 *
 * @author Nate Brunette <n@tebru.net>
 */
final class PhpType
{
    /**
     * The initial type
     *
     * @var string
     */
    private $fullType;

    /**
     * An enum representing core php types
     *
     * @var TypeToken
     */
    private $type;

    /**
     * If the type is an object, this will be the object's class name
     *
     * @var string
     */
    private $class;

    /**
     * Generic types, if they exist
     *
     * @var ArrayList
     */
    private $genericTypes;

    /**
     * Various options a type might need to reference
     *
     * For example, a DateTime object might want to store formatting options
     *
     * @var HashMap
     */
    private $options;

    /**
     * Constructor
     *
     * @param string $type
     * @throws \RuntimeException If the value is not valid
     * @throws \Tebru\Gson\Exception\MalformedTypeException If the type cannot be parsed
     */
    public function __construct(string $type)
    {
        $this->fullType = (string) str_replace(' ', '', $type);
        $this->genericTypes = new ArrayList();
        $this->options = new HashMap();

        $this->parseType($this->fullType);
    }

    /**
     * Recursively parse type.  If generics are found, this will create
     * new PhpTypes.
     *
     * @param string $type
     * @return void
     * @throws \RuntimeException If the value is not valid
     * @throws \Tebru\Gson\Exception\MalformedTypeException If the type cannot be parsed
     */
    private function parseType(string $type): void
    {
        if (false === strpos($type, '<')) {
            $this->setType($type);

            return;
        }

        // get start and end positions of generic
        $start = strpos($type, '<');
        $end = strrpos($type, '>');

        if (false === $end) {
            throw new MalformedTypeException('Could not find ending ">" for generic type');
        }

        // get generic types
        $genericTypes = substr($type, $start + 1, $end - $start - 1);

        // set the main type
        $this->setType(substr($type, 0, $start));

        // iterate over subtype to determine if format is <type> or <key, type>
        $depth = 0;
        $type = '';
        foreach (str_split($genericTypes) as $char) {
            // stepping into another generic type
            if ('<' === $char) {
                $depth++;
            }

            // stepping out of generic type
            if ('>' === $char) {
                $depth--;
            }

            // we only care about commas for the initial list of generics
            if (',' === $char && 0 === $depth) {
                // add new type to list
                $this->genericTypes->add(new PhpType($type));

                // reset type
                $type = '';

                continue;
            }

            // write character key
            $type .= $char;
        }

        $this->genericTypes->add(new PhpType($type));
    }

    /**
     * Create a type enum and set the class if necessary
     *
     * @param string $type
     * @return void
     * @throws \RuntimeException If the value is not valid
     */
    private function setType(string $type): void
    {
        $this->type = TypeToken::createFromString($type);

        if ($this->isObject()) {
            $this->class = 'object' === $type ? 'stdClass' : $type;
        } else {
            $this->fullType = (string) $this->type;
        }
    }

    /**
     * Returns a [@see TypeToken]
     *
     * @return TypeToken
     */
    public function getType(): TypeToken
    {
        return $this->type;
    }

    /**
     * Returns an ArrayList of generic types
     *
     * @return ArrayList
     */
    public function getGenerics(): ArrayList
    {
        return $this->genericTypes;
    }

    /**
     * Returns the class as a string or null if there isn't a class
     *
     * @return string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Returns true if this is a string
     *
     * @return bool
     */
    public function isString(): bool
    {
        return $this->type->equals(TypeToken::STRING());
    }

    /**
     * Returns true if this is an integer
     *
     * @return bool
     */
    public function isInteger(): bool
    {
        return $this->type->equals(TypeToken::INTEGER());
    }

    /**
     * Returns true if this is a float
     *
     * @return bool
     */
    public function isFloat(): bool
    {
        return $this->type->equals(TypeToken::FLOAT());
    }

    /**
     * Returns true if this is a boolean
     *
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->type->equals(TypeToken::BOOLEAN());
    }

    /**
     * Returns true if this is an array
     *
     * @return bool
     */
    public function isArray(): bool
    {
        return $this->type->equals(TypeToken::ARRAY());
    }

    /**
     * Returns true if this is an object
     *
     * @return bool
     */
    public function isObject(): bool
    {
        return $this->type->equals(TypeToken::OBJECT());
    }

    /**
     * Returns true if this is null
     *
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->type->equals(TypeToken::NULL());
    }

    /**
     * Returns true if this is a resource
     *
     * @return bool
     */
    public function isResource(): bool
    {
        return $this->type->equals(TypeToken::RESOURCE());
    }

    /**
     * Returns true if the type could be anything
     *
     * @return bool
     */
    public function isWildcard(): bool
    {
        return $this->type->equals(TypeToken::WILDCARD());
    }

    /**
     * Returns a HashMap of extra options
     *
     * @return HashMap
     */
    public function getOptions(): HashMap
    {
        return $this->options;
    }

    /**
     * Sets extra options on this type
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options->putAllArray($options);
    }

    /**
     * Return the initial type including generics
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->fullType;
    }
}
