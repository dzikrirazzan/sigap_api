<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Override untuk memastikan timestamp menggunakan Jakarta timezone
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        // Create a new DateTime object from the interface and set timezone
        $dateTime = new \DateTime($date->format('Y-m-d H:i:s'), $date->getTimezone());
        return $dateTime->setTimezone(new \DateTimeZone('Asia/Jakarta'))->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Override asDateTime untuk memastikan semua datetime menggunakan timezone Jakarta
     */
    protected function asDateTime($value)
    {
        // Panggil parent method dulu
        $datetime = parent::asDateTime($value);

        // Jika datetime berhasil dibuat, set timezone ke Jakarta
        if ($datetime) {
            return $datetime->setTimezone('Asia/Jakarta');
        }

        return $datetime;
    }

    const ROLE_USER = 'user';
    const ROLE_RELAWAN = 'relawan';
    const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nik',
        'nim',
        'jurusan',
        'no_telp',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRelawan()
    {
        return $this->role === self::ROLE_RELAWAN;
    }

    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }

    public function refreshTokens()
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function createRefreshToken()
    {
        $this->refreshTokens()->delete();

        $expiresAt = now()->addMinutes(config('sanctum.refresh_expiration', 60 * 24 * 7));

        $token = Str::random(80);

        $refreshToken = $this->refreshTokens()->create([
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $refreshToken;
    }

    public function panicReports()
    {
        return $this->hasMany(\App\Models\PanicReport::class);
    }

    public function relawanShifts()
    {
        return $this->hasMany(\App\Models\RelawanShift::class, 'relawan_id');
    }

    public function shiftPatterns()
    {
        return $this->hasMany(\App\Models\RelawanShiftPattern::class, 'relawan_id');
    }

    /**
     * Check if user has verified email
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified()
    {
        $this->email_verified_at = now();
        $this->save();
        
        return $this;
    }
}
