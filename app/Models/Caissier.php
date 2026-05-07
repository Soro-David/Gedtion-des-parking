<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caissier extends Model
{
    protected $table = 'caissiers';
    protected $fillable = ['user_id', 'created_by'];

    /** Profile user */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Creator (admin or supervisor) */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
