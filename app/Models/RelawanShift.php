<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RelawanShift extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'relawan_id',
        'shift_date',
    ];

    protected $casts = [
        'shift_date' => 'date',
    ];

    public function relawan()
    {
        return $this->belongsTo(User::class, 'relawan_id');
    }
}