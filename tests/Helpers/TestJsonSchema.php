<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;

/**
 * A simple test helper that implements JsonSchema for testing tool schemas.
 * Returns mock type objects that support method chaining.
 */
final class TestJsonSchema implements JsonSchema
{
    public function object(Closure|array $properties = []): ObjectType
    {
        return new ObjectType;
    }

    public function array(): ArrayType
    {
        return new ArrayType;
    }

    public function string(): StringType
    {
        return new StringType;
    }

    public function integer(): IntegerType
    {
        return new IntegerType;
    }

    public function number(): NumberType
    {
        return new NumberType;
    }

    public function boolean(): BooleanType
    {
        return new BooleanType;
    }
}
