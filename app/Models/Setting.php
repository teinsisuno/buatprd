<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key','value','group','label','type','is_encrypted'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public function getDecryptedValueAttribute()
    {
        if ($this->is_encrypted && $this->value) {
            try { return decrypt($this->value); } catch (\Throwable $e) { return $this->value; }
        }
        return $this->value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();
        if (! $row) return $default;
        if ($row->is_encrypted && $row->value) {
            try { return decrypt($row->value); } catch (\Throwable $e) { return $row->value; }
        }
        // try json decode
        $val = $row->value;
        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
        return $val;
    }

    public static function set(string $key, mixed $value, string $group = 'general', string $label = '', string $type = 'text', bool $encrypted = false): static
    {
        $store = is_array($value) || is_object($value) ? json_encode($value) : (string) $value;
        if ($encrypted) $store = encrypt($store);
        return static::updateOrCreate(['key' => $key], [
            'value' => $store,
            'group' => $group,
            'label' => $label ?: $key,
            'type' => $type,
            'is_encrypted' => $encrypted,
        ]);
    }
}
