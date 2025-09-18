<?php

namespace App\Listeners;

use App\Events\SectionDurationUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCourseDuration
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SectionDurationUpdated $event)
    {
        $course = $event->section->course;
        if($course) {
            $course->total_duration = $course->sections()->sum('total_duration');
            $course->save();
        }
    }
}
