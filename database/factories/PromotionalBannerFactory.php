<?php

namespace Database\Factories;

use App\Models\PromotionalBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionalBanner>
 */
class PromotionalBannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'image_url' => null,
            'image_alt' => null,
            'destination_url' => 'https://example.com/store',
            'placement' => 'dashboard',
            'is_enabled' => false,
        ];
    }
}
