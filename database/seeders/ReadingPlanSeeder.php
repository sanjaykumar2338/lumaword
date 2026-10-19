<?php

namespace Database\Seeders;

use App\Models\ReadingPlan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReadingPlanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $readings = $this->readings();

        DB::transaction(function () use ($readings): void {
            foreach ($this->plans() as $definition) {
                $plan = ReadingPlan::query()->updateOrCreate(
                    ['slug' => $definition['slug']],
                    [
                        'title' => $definition['title'],
                        'description' => $definition['description'],
                        'duration_days' => $definition['duration_days'],
                        'is_published' => true,
                    ],
                );

                $plan->days()->where('day_number', '>', $definition['duration_days'])->delete();

                foreach (array_slice($readings, 0, $definition['duration_days']) as $index => $reading) {
                    $scripture = config("scripture.passages.{$reading['key']}");
                    $plan->days()->updateOrCreate(
                        ['day_number' => $index + 1],
                        [
                            'title' => $reading['title'],
                            'scripture_key' => $reading['key'],
                            'scripture_reference' => $scripture['reference'],
                        ],
                    );
                }
            }
        });
    }

    /** @return array<int, array{slug: string, title: string, description: string, duration_days: int}> */
    private function plans(): array
    {
        return [
            [
                'slug' => 'foundations-of-faith',
                'title' => 'Foundations of Faith',
                'description' => 'Seven days with essential passages about God’s love, care, peace, obedience, and mission.',
                'duration_days' => 7,
            ],
            [
                'slug' => 'walking-with-jesus',
                'title' => 'Walking with Jesus',
                'description' => 'Fourteen days of Gospel-shaped habits for following Jesus in community and everyday life.',
                'duration_days' => 14,
            ],
            [
                'slug' => 'the-bible-in-thirty-days',
                'title' => 'The Bible in Thirty Days',
                'description' => 'A thirty-day journey through key passages in the biblical story and the life of discipleship.',
                'duration_days' => 30,
            ],
        ];
    }

    /** @return array<int, array{key: string, title: string}> */
    private function readings(): array
    {
        return [
            ['key' => 'john-3-16-21', 'title' => 'Loved and Sent'],
            ['key' => 'psalm-23', 'title' => 'The Shepherd’s Care'],
            ['key' => 'matthew-6-25-34', 'title' => 'Seek First'],
            ['key' => 'romans-8-31-39', 'title' => 'Inseparable Love'],
            ['key' => 'philippians-4-4-9', 'title' => 'Prayer and Peace'],
            ['key' => 'james-1-19-27', 'title' => 'Doers of the Word'],
            ['key' => 'matthew-28-16-20', 'title' => 'Sent to Make Disciples'],
            ['key' => 'mark-1-14-20', 'title' => 'Come, Follow Me'],
            ['key' => 'luke-10-25-37', 'title' => 'Love Your Neighbor'],
            ['key' => 'john-15-1-8', 'title' => 'Remain in the Vine'],
            ['key' => 'acts-2-42-47', 'title' => 'A Devoted Community'],
            ['key' => 'galatians-5-16-26', 'title' => 'Walk by the Spirit'],
            ['key' => 'ephesians-2-1-10', 'title' => 'Saved by Grace'],
            ['key' => 'colossians-3-12-17', 'title' => 'Put on Love'],
            ['key' => 'genesis-1-1-5', 'title' => 'In the Beginning'],
            ['key' => 'genesis-12-1-4', 'title' => 'Called to Bless'],
            ['key' => 'exodus-3-1-12', 'title' => 'God Hears and Calls'],
            ['key' => 'exodus-20-1-17', 'title' => 'A Covenant Way of Life'],
            ['key' => 'deuteronomy-6-4-9', 'title' => 'Love God Wholeheartedly'],
            ['key' => 'joshua-1-1-9', 'title' => 'Strong and Courageous'],
            ['key' => '1-samuel-16-1-13', 'title' => 'God Looks at the Heart'],
            ['key' => '2-samuel-7-8-16', 'title' => 'A Lasting Promise'],
            ['key' => '1-kings-18-20-39', 'title' => 'The Lord Is God'],
            ['key' => 'psalm-51-1-12', 'title' => 'Create in Me a Clean Heart'],
            ['key' => 'isaiah-53-1-6', 'title' => 'He Bore Our Suffering'],
            ['key' => 'jeremiah-29-10-14', 'title' => 'Seek Me and Find Me'],
            ['key' => 'daniel-6-16-23', 'title' => 'Faithful in the Lions’ Den'],
            ['key' => 'micah-6-6-8', 'title' => 'Justice, Mercy, Humility'],
            ['key' => 'matthew-5-1-12', 'title' => 'The Blessed Life'],
            ['key' => 'john-20-24-31', 'title' => 'Believe and Have Life'],
        ];
    }
}
