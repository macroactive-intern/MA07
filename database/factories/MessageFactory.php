<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'thread_id' => MessageThread::factory(),
            'sender_id' => User::factory()->client(),
            'body'      => fake()->paragraph(),
            'read_at'   => null,
        ];
    }
}
