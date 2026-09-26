<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordRequestStatusHistory extends Model
{
    protected $table = 'request_status_histories';

    protected $fillable = [
        'request_id',
        'status',
        'admin_notes',
        'changed_by',
    ];

    public function recordRequest(): BelongsTo
    {
        return $this->belongsTo(RecordRequest::class, 'request_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
