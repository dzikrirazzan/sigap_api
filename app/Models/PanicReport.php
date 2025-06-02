<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PanicReport extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'location_description',
        'emergency_type',
        'status',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'handled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Override untuk memastikan timestamp menggunakan Jakarta timezone
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->setTimezone(new \DateTimeZone('Asia/Jakarta'))->format('Y-m-d\TH:i:s.uP');
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_HANDLING = 'handling';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CANCELLED = 'cancelled';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function getLocationUrlAttribute()
    {
        return "https://maps.google.com/maps?q={$this->latitude},{$this->longitude}";
    }

    // Override untuk memastikan timezone Jakarta
    public function getCreatedAtAttribute($value)
    {
        return $this->asDateTime($value)->setTimezone('Asia/Jakarta');
    }

    public function getUpdatedAtAttribute($value)
    {
        return $this->asDateTime($value)->setTimezone('Asia/Jakarta');
    }
}