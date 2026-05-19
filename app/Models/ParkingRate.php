<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingRate extends Model
{
    protected $fillable = [
        'parking_id',
        'label',
        'from_time',
        'to_time',
        'amount',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
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
     * Calcule le montant applicable à une heure donnée (format "H:i").
     * Priorité : tarifs spécifiques au parking → tarif de base du parking.
     */
    public static function calculateAmount(string $time, ?int $parkingId = null): ?float
    {
        if ($parkingId) {
            $rate = static::where('parking_id', $parkingId)
                ->whereNotNull('from_time')
                ->whereNotNull('to_time')
                ->whereRaw('? BETWEEN from_time AND to_time', [$time])
                ->orderBy('from_time')
                ->first();

            if ($rate) {
                return (float) $rate->amount;
            }

            // Tarif de base du parking
            $parking = \App\Models\Parking::find($parkingId);
            if ($parking && $parking->price !== null) {
                return (float) $parking->price;
            }
        }

        return null;
    }
}
