<?php

declare(strict_types=1);

namespace App\Models\Casts;

use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use JsonException;

class ArrayFieldsCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (! $value) {
            return [];
        }

        try {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    /**
     * @throws JsonException
     */
    public function set($model, string $key, $value, array $attributes)
    {
        $value = array_values(array_intersect(User::SECURE_FIELDS, $value));

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
    }
}
