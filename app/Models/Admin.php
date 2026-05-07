<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Admin extends Model
{
    /** @use HasFactory<\Database\Factories\AdminFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'created_by', 'blocked'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user who created this admin entry (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
