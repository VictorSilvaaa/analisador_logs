<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogProcessingFailure extends Model
{
    protected $fillable = [
        'file_path',
        'line_number',
        'content',
        'error_message',
        'context',
        'resolved_at',
        'resolved_message',
    ];

    protected $casts = [
        'context' => 'array',
        'resolved_at' => 'datetime',
    ];
}
