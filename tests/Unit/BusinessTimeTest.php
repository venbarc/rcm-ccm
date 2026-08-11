<?php

namespace Tests\Unit;

use App\Support\BusinessTime;
use Carbon\Carbon;
use Tests\TestCase;

class BusinessTimeTest extends TestCase
{
    public function test_pacific_business_days_convert_to_utc_with_daylight_saving_time(): void
    {
        config([
            'app.timezone' => 'UTC',
            'app.business_timezone' => 'America/Los_Angeles',
        ]);

        $this->assertSame('2026-08-11 07:00:00', BusinessTime::dayStart('2026-08-11')?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-12-11 08:00:00', BusinessTime::dayStart('2026-12-11')?->format('Y-m-d H:i:s'));
    }

    public function test_utc_timestamps_are_displayed_in_pacific_time(): void
    {
        config(['app.business_timezone' => 'America/Los_Angeles']);

        $display = BusinessTime::display(Carbon::parse('2026-08-12 06:30:00', 'UTC'));

        $this->assertSame('2026-08-11 23:30:00', $display->format('Y-m-d H:i:s'));
        $this->assertSame('-07:00', $display->format('P'));
    }
}
