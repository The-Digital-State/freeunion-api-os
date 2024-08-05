<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use RuntimeException;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    /**
     * @template TModelClass of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModelClass>|Relation<TModelClass>|BelongsToMany<TModelClass>  $query
     * @param  array<string, string|null>  $filter
     * @param  bool  $pivot
     * @return array<string, string>
     */
    public static function filterQuery(
        Builder|Relation|BelongsToMany $query,
        array $filter,
        bool $pivot = false,
    ): array {
        $result = [];

        $ops = [
            'eq' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, $value[0])
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, $value[0]);
                $value = [$value[0]];
            },
            'neq' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, '<>', $value[0])
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, '<>', $value[0]);
                $value = [$value[0]];
            },
            'lt' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, '<', $value[0])
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, '<', $value[0]);
                $value = [$value[0]];
            },
            'lte' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, '<=', $value[0])
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, '<=', $value[0]);
                $value = [$value[0]];
            },
            'gt' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, '>', $value[0])
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, '>', $value[0]);
                $value = [$value[0]];
            },
            'gte' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, '>=', $value[0])
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, '>=', $value[0]);
                $value = [$value[0]];
            },
            'in' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                $pivot && $query instanceof BelongsToMany ? $query->wherePivotIn($field, $value)
                    /** @phpstan-ignore-next-line */
                    : $query->whereIn($field, $value);
            },
            'nin' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                $pivot && $query instanceof BelongsToMany ? $query->wherePivotNotIn($field, $value)
                    /** @phpstan-ignore-next-line */
                    : $query->whereNotIn($field, $value);
            },
            'bw' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 2) {
                    throw new RuntimeException("Filter $field requires 2 parameters");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivotBetween($field, [$value[0], $value[1]])
                    /** @phpstan-ignore-next-line */
                    : $query->whereBetween($field, [$value[0], $value[1]]);
                $value = [$value[0], $value[1]];
            },
            'nbw' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 2) {
                    throw new RuntimeException("Filter $field requires 2 parameters");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivotNotBetween($field, [$value[0], $value[1]])
                    /** @phpstan-ignore-next-line */
                    : $query->whereNotBetween($field, [$value[0], $value[1]]);
                $value = [$value[0], $value[1]];
            },
            'lk' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, 'like', "%$value[0]%")
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, 'like', "%$value[0]%");
                $value = [$value[0]];
            },
            'nlk' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                if (count($value) < 1) {
                    throw new RuntimeException("Filter $field requires the parameter");
                }

                $pivot && $query instanceof BelongsToMany ? $query->wherePivot($field, 'not like', "%$value[0]%")
                    /** @phpstan-ignore-next-line */
                    : $query->where($field, 'not like', "%$value[0]%");
                $value = [$value[0]];
            },
            'nl' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                $pivot && $query instanceof BelongsToMany ? $query->wherePivotNull($field)
                    /** @phpstan-ignore-next-line */
                    : $query->whereNull($field);
                $value = [];
            },
            'nnl' => static function (
                Builder|Relation|BelongsToMany $query,
                string $field,
                array &$value,
                bool $pivot,
            ): void {
                $pivot && $query instanceof BelongsToMany ? $query->wherePivotNotNull($field)
                    /** @phpstan-ignore-next-line */
                    : $query->whereNotNull($field);
                $value = [];
            },
        ];

        foreach ($filter as $field => $string) {
            if ($string === null) {
                continue;
            }

            $value = explode(',', $string);
            $operation = count($value) === 1 ? 'eq' : array_shift($value);

            if (isset($ops[$operation])) {
                $ops[$operation]($query, $field, $value, $pivot);

                $result[$field] = implode(',', array_merge([$operation], $value));
            }
        }

        return $result;
    }
}
