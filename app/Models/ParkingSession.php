<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingSession extends Model
{
    protected $fillable = [
        'parking_id',
        'license_plate',
        'marque',
        'modele',
        'vehicle_image',
        'status',
        'started_at',
        'ended_at',
        'amount',
        'closed_by',
        'created_by',
        'reversement_id',
        'versement_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'amount'     => 'float',
    ];

    public function parking()
    {
        return $this->belongsTo(Parking::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function reversement()
    {
        return $this->belongsTo(Reversement::class, 'reversement_id');
    }

    public function versement()
    {
        return $this->belongsTo(Versement::class, 'versement_id');
    }

    /**
     * Durée de stationnement en minutes (arrondie à l'entier supérieur).
     */
    public function getDurationMinutesAttribute(): int
    {
        $end = $this->ended_at ?? now();
        return (int) ceil($this->started_at->diffInMinutes($end));
    }
}
