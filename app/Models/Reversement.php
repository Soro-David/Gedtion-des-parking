<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reversement extends Model
{
    protected $fillable = [
        'user_id',
        'receiver_id',
        'amount',
        'note',
        'status',
        'confirmed_at',
    ];

    protected $casts = [
        'amount'       => 'float',
        'confirmed_at' => 'datetime',
    ];

    /** Agent ou caissier qui effectue le reversement */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Admin ou superviseur qui reçoit */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /** Sessions de stationnement incluses dans ce reversement */
    public function sessions()
    {
        return $this->hasMany(ParkingSession::class, 'reversement_id');
    }
}
