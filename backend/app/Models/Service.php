<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'external_id',
        'name',
        'host',
        'path',
        'port',
        'protocol',
        'connect_timeout',
        'read_timeout',
        'write_timeout',
        'retries',
        'service_created_at',
        'service_updated_at',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(RequestLog::class);
    }
}
