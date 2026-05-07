<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingAttendant extends Model
{
    protected $fillable = ['user_id', 'created_by'];

    /** Profile user */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Supervisor who created this attendant */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Drivers registered by this attendant */
    public function drivers()
    {
        return $this->hasMany(Driver::class, 'created_by', 'user_id');
    }
}
