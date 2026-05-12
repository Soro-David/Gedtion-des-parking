<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'name',
        'address',
        'longitude',
        'latitude',
        'capacity',
        'price',
        'image',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users assigned to this parking (supervisors, attendants, caissiers).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_parkings')->withTimestamps();
    }

    /**
     * Get the user who created the parking.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Active (occupied) parking sessions.
     */
    public function activeSessions()
    {
        return $this->hasMany(ParkingSession::class)->where('status', 'occupied');
    }

    /**
     * All parking sessions.
     */
    public function sessions()
    {
        return $this->hasMany(ParkingSession::class);
    }

    /**
     * Parking rates (tarifs par intervalle).
     */
    public function rates()
    {
        return $this->hasMany(ParkingRate::class)->orderBy('from_minutes');
    }

    /**
     * Number of occupied spots.
     */
    public function getOccupiedSpotsAttribute(): int
    {
        return $this->activeSessions()->count();
    }

    /**
     * Number of available (free) spots.
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->capacity - $this->occupied_spots);
    }
}
