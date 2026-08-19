<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'value_type', 'is_public'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
