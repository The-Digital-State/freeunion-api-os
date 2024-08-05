<?php

declare(strict_types=1);

namespace App\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use JsonException;

class EncryptCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): array
    {
        return $value ? $this->decrypt($value) : [];
    }

    /**
     * @throws JsonException
     */
    public function set($model, string $key, $value, array $attributes): string
    {
        return $this->encrypt($value);
    }

    /**
     * @throws JsonException
     */
    private function encrypt(array $value): string
    {
        if (App::environment('production')) {
            return Crypt::encrypt($value);
        }

        return json_encode($value, JSON_THROW_ON_ERROR | JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
    }

    private function decrypt(string $string): array
    {
        if (App::environment('production')) {
            return Crypt::decrypt($string);
        }

        try {
            return json_decode($string, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }
}
