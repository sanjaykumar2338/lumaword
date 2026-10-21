<?php

namespace Tests\Feature;

use Database\Seeders\ReadingPlanSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Tests\TestCase;

class ReadingPlanSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_repeated_runs_keep_one_copy_of_each_plan_and_day(): void
    {
        $this->seed(ReadingPlanSeeder::class);
        $this->seed(ReadingPlanSeeder::class);

        $this->assertDatabaseCount('reading_plans', 3);
        $this->assertDatabaseCount('reading_plan_days', 51);
        $this->assertDatabaseHas('reading_plan_days', [
            'day_number' => 30,
            'scripture_key' => 'john-20-24-31',
            'scripture_reference' => 'John 20:24-31',
        ]);
    }

    public function test_missing_scripture_fails_with_the_catalog_key_before_writing_plans(): void
    {
        Config::set('scripture.passages.john-3-16-21', null);

        try {
            $this->seed(ReadingPlanSeeder::class);
            $this->fail('The seeder did not reject a missing Scripture catalog entry.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Scripture passage [john-3-16-21] is not defined in the catalog.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('reading_plans', 0);
        $this->assertDatabaseCount('reading_plan_days', 0);
    }
}
