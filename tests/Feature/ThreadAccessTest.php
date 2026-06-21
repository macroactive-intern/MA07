<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;

test('user cannot view a thread they do not participate in', function () {
    $coach    = User::factory()->coach()->create();
    $client   = User::factory()->client()->create();
    $outsider = User::factory()->client()->create();
    $thread   = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($outsider)
        ->getJson("/api/threads/{$thread->id}")
        ->assertStatus(403);
});

test('coach cannot read another coach\'s thread', function () {
    $coach      = User::factory()->coach()->create();
    $otherCoach = User::factory()->coach()->create();
    $client     = User::factory()->client()->create();
    $thread     = MessageThread::factory()->create([
        'coach_id'  => $otherCoach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($coach)
        ->getJson("/api/threads/{$thread->id}")
        ->assertStatus(403);
});

test('client cannot read an unrelated thread', function () {
    $coach          = User::factory()->coach()->create();
    $client         = User::factory()->client()->create();
    $unrelatedClient = User::factory()->client()->create();
    $thread         = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($unrelatedClient)
        ->getJson("/api/threads/{$thread->id}")
        ->assertStatus(403);
});

test('messages in thread are returned oldest first', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $first = Message::factory()->create([
        'thread_id'  => $thread->id,
        'sender_id'  => $client->id,
        'created_at' => now()->subHours(2),
    ]);
    $second = Message::factory()->create([
        'thread_id'  => $thread->id,
        'sender_id'  => $coach->id,
        'created_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($coach)->getJson("/api/threads/{$thread->id}");

    $response->assertStatus(200);
    $ids = collect($response->json('messages'))->pluck('id');
    expect($ids->first())->toBe($first->id)
        ->and($ids->last())->toBe($second->id);
});
