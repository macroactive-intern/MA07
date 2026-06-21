<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;

test('message body is required', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($coach)
        ->postJson("/api/threads/{$thread->id}/messages", ['body' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

test('message body max is 5000 characters', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($coach)
        ->postJson("/api/threads/{$thread->id}/messages", ['body' => str_repeat('a', 5001)])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['body']);
});

test('mark read sets read_at on the message', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);
    $message = Message::factory()->create([
        'thread_id' => $thread->id,
        'sender_id' => $client->id,
        'read_at'   => null,
    ]);

    $this->actingAs($coach)
        ->patchJson("/api/threads/{$thread->id}/messages/{$message->id}/read")
        ->assertStatus(200);

    expect($message->fresh()->read_at)->not->toBeNull();
});
