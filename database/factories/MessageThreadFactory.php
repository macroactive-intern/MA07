<?php

namespace Database\Factories;

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageThread>
 */
class MessageThreadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'coach_id'    => User::factory()->coach(),
            'client_id'   => User::factory()->client(),
            'subject'     => fake()->sentence(4),
            'archived_at' => null,
        ];
    }
}
