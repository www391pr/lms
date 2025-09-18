<?php

namespace App\Events;

use App\Models\Section;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SectionDurationUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $section;

    /**
     * Create a new event instance.
     */
    public function __construct(Section $section)
    {
        $this->section = $section;
    }
}
