<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'consumer_id',
        'service_id',
        'source_file_path',
        'source_line_number',
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
        'processed_at',
        'request_headers',
        'response_headers',
        'querystring',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'processed_at' => 'datetime',
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

    public function setStartedAtAttribute(int|string $value): void
    {
        if (is_numeric($value)) {
            $timestamp = (int) $value;

            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }

            $value = CarbonImmutable::createFromTimestamp($timestamp, config('app.timezone'))->toDateTimeString();
        }

        $this->attributes['started_at'] = $value;
    }
}
