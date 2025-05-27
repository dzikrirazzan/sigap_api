<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelawanShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'relawan_id',
        'shift_date',
    ];

    public function relawan()
    {
        return $this->belongsTo(User::class, 'relawan_id');
    }
}