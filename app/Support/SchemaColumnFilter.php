<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class SchemaColumnFilter
{
    /** @var array<string, array<string, true>> */
    private static array $columnCache = [];

    /**
     * Keep only attributes that exist as columns on the model's table.
     *
     * @param  class-string<Model>|Model  $model
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function forModel(Model|string $model, array $attributes): array
    {
        $instance = is_string($model) ? new $model : $model;
        $table = $instance->getTable();

        if (! isset(self::$columnCache[$table])) {
            $columns = Schema::getColumnListing($table);
            self::$columnCache[$table] = $columns !== []
                ? array_fill_keys($columns, true)
                : [];
        }

        if (self::$columnCache[$table] === []) {
            return $attributes;
        }

        return array_intersect_key($attributes, self::$columnCache[$table]);
    }
}
