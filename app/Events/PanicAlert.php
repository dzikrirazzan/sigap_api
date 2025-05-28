<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\PanicReport;

class PanicAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $panic;
    public $relawanId;

    public function __construct(PanicReport $panic, $relawanId)
    {
        $this->panic = $panic;
        $this->relawanId = $relawanId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('relawan.' . $this->relawanId);
    }

    public function broadcastAs()
    {
        return 'panic.alert';
    }

    public function broadcastWith()
    {
        return [
            'panic' => [
                'id' => $this->panic->id,
                'user' => [
                    'id' => $this->panic->user->id,
                    'name' => $this->panic->user->name,
                    'no_telp' => $this->panic->user->no_telp,
                    'nik' => $this->panic->user->nik,
                ],
                'latitude' => $this->panic->latitude,
                'longitude' => $this->panic->longitude,
                'location_description' => $this->panic->location_description,
                'location_url' => $this->panic->location_url,
                'status' => $this->panic->status,
                'created_at' => $this->panic->created_at,
            ],
            'message' => 'New panic alert received!',
            'timestamp' => now()->toISOString(),
        ];
    }
}
