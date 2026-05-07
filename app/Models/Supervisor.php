<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $fillable = ['user_id', 'created_by'];

    /** Profile user */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Admin who created this supervisor */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Attendants (agents) managed by this supervisor */
    public function attendants()
    {
        return $this->hasMany(ParkingAttendant::class, 'created_by', 'user_id');
    }
}
