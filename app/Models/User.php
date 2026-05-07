<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'email',
        'password',
        'role',
        'unique_code',
        'phone',
        'address',
        'emergency_contact',
        'emergency_relationship',
        'emergency_name',
        'invitation_token',
        'invitation_expires_at',
    ];

    const ROLE_SUPERADMIN = 'superadmin';
    const ROLE_ADMIN = 'admin';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_ATTENDANT = 'attendant';
    const ROLE_DRIVER = 'driver';
    const ROLE_CAISSIER = 'caissier';

    /**
     * Relationships with profiles
     */
    public function admin() { return $this->hasOne(Admin::class); }
    public function supervisor() { return $this->hasOne(Supervisor::class); }
    public function attendant() { return $this->hasOne(ParkingAttendant::class); }
    public function caissier() { return $this->hasOne(Caissier::class); }
    public function driver() { return $this->hasOne(Driver::class); }

    /** Parkings assigned to this user (attendant or caissier) */
    public function parkings()
    {
        return $this->belongsToMany(Parking::class, 'user_parkings')->withTimestamps();
    }

    /** Sessions fermées (sorties enregistrées) par cet utilisateur */
    public function closedSessions()
    {
        return $this->hasMany(ParkingSession::class, 'closed_by');
    }

    /**
     * Helpers to check roles
     */
    public function isSuperAdmin() { return $this->role === self::ROLE_SUPERADMIN; }
    public function isAdmin() { return $this->role === self::ROLE_ADMIN; }
    public function isSupervisor() { return $this->role === self::ROLE_SUPERVISOR; }
    public function isAttendant() { return $this->role === self::ROLE_ATTENDANT; }
    public function isDriver() { return $this->role === self::ROLE_DRIVER; }
    public function isCaissier() { return $this->role === self::ROLE_CAISSIER; }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'invitation_expires_at' => 'datetime',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted()
    {
        static::created(function ($user) {
            if (!$user->unique_code) {
                $prefix = 'GP';
                if ($user->role === self::ROLE_ADMIN) $prefix = 'ADM';
                if ($user->role === self::ROLE_SUPERVISOR) $prefix = 'SUP';
                if ($user->role === self::ROLE_ATTENDANT) $prefix = 'AGT';
                if ($user->role === self::ROLE_CAISSIER) $prefix = 'CAI';
                
                $user->unique_code = $prefix . '-' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
                $user->save();
            }
        });
    }


}
