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

    /**
     * Override handled_at accessor untuk PanicReport
     */
    public function getHandledAtAttribute($value)
    {
        if ($value) {
            return $this->asDateTime($value)->setTimezone('Asia/Jakarta');
        }
        return $value;
    }
}
