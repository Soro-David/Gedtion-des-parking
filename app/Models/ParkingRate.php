<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingRate extends Model
{
    protected $fillable = [
        'parking_id',
        'label',
        'from_minutes',
        'to_minutes',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'from_minutes' => 'integer',
        'to_minutes'   => 'integer',
        'amount'       => 'float',
    ];

    public function parking()
    {
        return $this->belongsTo(Parking::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calcule le montant pour une durée donnée (en minutes).
     * Priorité : tarifs spécifiques au parking → tarifs globaux.
     */
    public static function calculateAmount(int $minutes, ?int $parkingId = null): ?float
    {
        // 1. Tarifs spécifiques au parking
        if ($parkingId) {
            $rate = static::where('parking_id', $parkingId)
                ->where('from_minutes', '<=', $minutes)
                ->where(function ($q) use ($minutes) {
                    $q->whereNull('to_minutes')->orWhere('to_minutes', '>=', $minutes);
                })
                ->orderByDesc('from_minutes')
                ->first();

            if ($rate) {
                return (float) $rate->amount;
            }
        }

        // 2. Tarifs globaux (parking_id = null)
        $rate = static::whereNull('parking_id')
            ->where('from_minutes', '<=', $minutes)
            ->where(function ($q) use ($minutes) {
                $q->whereNull('to_minutes')->orWhere('to_minutes', '>=', $minutes);
            })
            ->orderByDesc('from_minutes')
            ->first();

        return $rate ? (float) $rate->amount : null;
    }
}
