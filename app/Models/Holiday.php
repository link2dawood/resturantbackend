<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Active holiday names for the daily-report picker. Falls back to the config
     * defaults if the table is empty (e.g. before the seed migration runs).
     *
     * @return array<int, string>
     */
    public static function optionList(): array
    {
        $names = static::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return $names ?: config('holidays', []);
    }
}
