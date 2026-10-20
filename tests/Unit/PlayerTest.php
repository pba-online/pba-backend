<?php

namespace Tests\Unit;

use App\Models\Player;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class PlayerTest extends TestCase
{
    public function test_age_is_derived_from_birth_date(): void
    {
        $player = new Player(['birth_date' => '2000-01-01']);

        $this->assertSame(Carbon::parse('2000-01-01')->age, $player->age);
    }

    public function test_height_inches_is_derived_from_stored_height(): void
    {
        $player = new Player(['height' => '6\'5"']);

        $this->assertSame(77, $player->height_inches);
        $this->assertSame('6\'5"', $player->height);
    }

    public function test_invalid_stored_height_has_no_inches_value(): void
    {
        $player = new Player(['height' => 'unknown']);

        $this->assertNull($player->height_inches);
    }
}
