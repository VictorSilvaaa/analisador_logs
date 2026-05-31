<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'consumer_id',
        'service_id',
        'method',
        'uri',
        'url',
        'request_size',
        'upstream_uri',
        'response_status',
        'response_size',
        'proxy_latency',
        'gateway_latency',
        'request_latency',
        'client_ip',
        'started_at',
        'request_headers',
        'response_headers',
        'querystring',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'querystring' => 'array',
    ];

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(Consumer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
