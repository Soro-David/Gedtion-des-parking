<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signalement extends Model
{
    protected $fillable = [
        'user_id',
        'parking_id',
        'license_plate',
        'motif',
        'type',
        'phone',
        'sms_status',
    ];

    /**
     * Get the user who created this signalement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parking where this signalement was recorded.
     */
    public function parking(): BelongsTo
    {
        return $this->belongsTo(Parking::class);
    }
}
