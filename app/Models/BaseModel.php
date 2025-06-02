<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    /**
     * Override untuk memastikan semua timestamp menggunakan Jakarta timezone
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        // Konversi ke timezone Jakarta dan format tanpa Z
        return $date->setTimezone(new \DateTimeZone('Asia/Jakarta'))->format('Y-m-d\TH:i:s.uP');
    }

    /**
     * Override created_at accessor
     */
    public function getCreatedAtAttribute($value)
    {
        return $this->asDateTime($value)->setTimezone('Asia/Jakarta');
    }

    /**
     * Override updated_at accessor
     */
    public function getUpdatedAtAttribute($value)
    {
        return $this->asDateTime($value)->setTimezone('Asia/Jakarta');
    }
}
