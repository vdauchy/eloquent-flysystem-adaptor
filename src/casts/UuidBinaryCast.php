<?php

declare(strict_types=1);

namespace VDauchy\EloquentFlysystemAdaptor\casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidBinaryCast implements CastsAttributes
{
    /**
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return UuidInterface|null
     */
    public function get($model, string $key, $value, array $attributes): ?UuidInterface
    {
        return $value
            ? Uuid::fromBytes($value)
            : null;
    }

    /**
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, string $key, $value, array $attributes): string
    {
        return (($value instanceof UuidInterface) ? $value : Uuid::fromString($value))->getBytes();
    }
}
