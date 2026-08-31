<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordRequest extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'alumni_id',
        'request_type',
        'year_requested',
        'requester_note',
        'status',
        'admin_notes',
        'processed_by',
        'processed_at',
        'admin_replied_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_requested' => 'integer',
            'processed_at' => 'datetime',
            'admin_replied_at' => 'datetime',
        ];
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @return array<int, string>
     */
    public static function requestTypes(): array
    {
        return [
            'Alumni ID',
            'Year Book',
            'Facility Use-(Message/Note)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function workflowStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'ready_for_pickup' => 'Ready for Pickup',
            'completed' => 'Claimed',
            'rejected' => 'Rejected',
        ];
    }
}
