<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['user_id', 'created_by', 'license_plate'];

    /** Profile user */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Attendant who registered this driver */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
