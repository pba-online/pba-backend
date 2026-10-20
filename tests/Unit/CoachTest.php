<?php

namespace Tests\Unit;

use App\Models\Coach;
use PHPUnit\Framework\TestCase;

class CoachTest extends TestCase
{
    public function test_name_maps_to_first_and_last_name_columns(): void
    {
        $coach = new Coach(['name' => 'Test Coach Name']);

        $this->assertSame('Test', $coach->first_name);
        $this->assertSame('Coach Name', $coach->last_name);
        $this->assertSame('Test Coach Name', $coach->name);
        $this->assertArrayNotHasKey('name', $coach->getAttributes());
    }

    public function test_name_is_composed_from_stored_name_columns(): void
    {
        $coach = new Coach(['first_name' => 'Jane', 'last_name' => 'Doe']);

        $this->assertSame('Jane Doe', $coach->name);
    }
}
