<?php

namespace App\Shared\DTOs;

use ArrayAccess;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class BaseDTO implements Arrayable, Jsonable, JsonSerializable, ArrayAccess
{
    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Convert DTO to JSON.
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset} ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }
}

