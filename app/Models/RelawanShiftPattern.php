<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RelawanShiftPattern extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'relawan_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Konstanta untuk hari dalam seminggu
    const DAYS = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];

    public function relawan()
    {
        return $this->belongsTo(User::class, 'relawan_id');
    }

    // Scope untuk mendapatkan pattern aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk mendapatkan pattern berdasarkan hari
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }
}
