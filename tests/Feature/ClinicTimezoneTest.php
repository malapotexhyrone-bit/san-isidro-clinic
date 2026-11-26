<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;

class ClinicTimezoneTest extends TestCase
{
    public function test_the_application_uses_philippine_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', date_default_timezone_get());
        $this->assertSame('+08:00', now()->format('P'));
    }

    public function test_clinic_day_rolls_over_at_midnight_in_the_philippines(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-03 17:30:00', 'UTC'));

        try {
            $this->assertSame('2026-10-04 01:30', now()->format('Y-m-d H:i'));
            $this->assertSame('2026-10-04', today()->toDateString());
            $this->assertTrue(Carbon::parse('2026-10-03')->isBefore(today()));
            $this->assertFalse(Carbon::parse('2026-10-04')->isBefore(today()));
        } finally {
            Carbon::setTestNow();
        }
    }
}