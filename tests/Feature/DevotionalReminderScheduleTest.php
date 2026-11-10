<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class DevotionalReminderScheduleTest extends TestCase
{
    public function test_devotional_reminders_are_registered_as_an_overlapping_safe_minute_schedule(): void
    {
        $this->travelTo('2026-10-21 08:07:12 UTC');

        $event = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains($event->getSummaryForDisplay(), 'devotional-reminders:dispatch'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->isDue($this->app));
    }
}
