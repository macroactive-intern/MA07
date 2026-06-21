<?php

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Database\QueryException;

test('coach can create a thread with a client', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create();

    $this->actingAs($coach)
        ->postJson('/api/coach/threads', [
            'client_id' => $client->id,
            'subject'   => 'Nutrition check-in',
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('message_threads', [
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);
});

test('coach cannot create a duplicate thread with the same client', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create();

    MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($coach)
        ->postJson('/api/coach/threads', [
            'client_id' => $client->id,
            'subject'   => 'Second thread',
        ])
        ->assertStatus(422);
});

test('database prevents duplicate coach-client pair', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create();

    MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    expect(fn () => MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]))->toThrow(QueryException::class);
});

test('coach cannot create a thread with themselves as the client', function () {
    $coach = User::factory()->coach()->create();

    $this->actingAs($coach)
        ->postJson('/api/coach/threads', [
            'client_id' => $coach->id,
            'subject'   => 'Self thread',
        ])
        ->assertStatus(422);
});

test('archive sets archived_at but row still exists', function () {
    $coach = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    $this->actingAs($coach)
        ->deleteJson("/api/coach/threads/{$thread->id}")
        ->assertStatus(200);

    $this->assertDatabaseHas('message_threads', ['id' => $thread->id]);
    expect($thread->fresh()->archived_at)->not->toBeNull();
});
