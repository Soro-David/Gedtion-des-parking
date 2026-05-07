<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Versement extends Model
{
    protected $fillable = [
        'admin_id',
        'agent_id',
        'caissier_id',
        'collected_amount',
        'previous_debt',
        'total_due',
        'paid_amount',
        'remaining_debt',
        'note',
    ];

    protected $casts = [
        'collected_amount' => 'float',
        'previous_debt'    => 'float',
        'total_due'        => 'float',
        'paid_amount'      => 'float',
        'remaining_debt'   => 'float',
    ];

    /** Admin ou superviseur qui initie le versement */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /** Agent (attendant) concerné */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** Caissier concerné */
    public function caissier()
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    /** Retourne l'agent ou le caissier selon le versement */
    public function getPersonneAttribute()
    {
        return $this->agent ?? $this->caissier;
    }

    /** Sessions incluses dans ce versement */
    public function sessions()
    {
        return $this->hasMany(ParkingSession::class, 'versement_id');
    }
}
