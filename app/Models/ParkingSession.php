<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ParkingSession extends Model
{
    protected $fillable = [
        'parking_id',
        'license_plate',
        'phone',
        'marque',
        'modele',
        'vehicle_image',
        'status',
        'started_at',
        'ended_at',
        'amount',
        'payment_method',
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

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', 'occupied')
            ->whereNull('ended_at');
    }

    public static function normalizeLicensePlate(?string $licensePlate): string
    {
        $plate = strtoupper(trim((string) $licensePlate));
        // Remove 2 leading letters (e.g. "CI") if they are followed by numbers, spaces, or dashes
        if (preg_match('/^[A-Z]{2}(?=[0-9\s\-])/', $plate)) {
            $plate = preg_replace('/^[A-Z]{2}[\s\-]*/', '', $plate);
        }
        return $plate;
    }

    public static function hasActiveForLicensePlate(string $licensePlate): bool
    {
        return static::query()
            ->active()
            ->where('license_plate', static::normalizeLicensePlate($licensePlate))
            ->exists();
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
