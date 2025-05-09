<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a config value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getConfig($key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->value : $default;
    }

    /**
     * Set a config value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @return \App\Models\SystemConfig
     */
    public static function setConfig($key, $value, $description = null)
    {
        $config = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );

        return $config;
    }
} 