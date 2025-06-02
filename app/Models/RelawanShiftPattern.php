<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RelawanShiftPattern extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'relawan_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Days of week constants
    const DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu'
    ];

    // Relationship to User (Relawan)
    public function relawan()
    {
        return $this->belongsTo(User::class, 'relawan_id');
    }

    // Get day name in Indonesian
    public function getDayNameAttribute()
    {
        return self::DAYS[$this->day_of_week] ?? $this->day_of_week;
    }

    // Scope for active patterns
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for specific day
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}
