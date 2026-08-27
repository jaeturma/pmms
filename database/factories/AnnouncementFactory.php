<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state: an unpublished general advisory.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_id' => null,
            'title' => 'Advisory: '.fake()->unique()->sentence(3),
            'body' => fake()->paragraph(),
            'status' => 'draft',
            'priority' => 'normal',
            'audience' => 'public',
            'status' => 'draft',
            'priority' => 'normal',
            'audience' => 'public',
            'is_published' => false,
            'published_at' => null,
            'created_by' => User::factory()->organizer(),
        ];
    }

    /**
     * Indicate that the announcement is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'status' => 'published',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
