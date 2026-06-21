<?php

use App\Models\MessageThread;
use App\Models\User;

test('coach can restore an archived thread', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'    => $coach->id,
        'client_id'   => $client->id,
        'archived_at' => now(),
    ]);

    $this->actingAs($coach)
        ->patchJson("/api/coach/threads/{$thread->id}/restore")
        ->assertStatus(200);

    expect($thread->fresh()->archived_at)->toBeNull();
});

test('client cannot restore a thread', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'    => $coach->id,
        'client_id'   => $client->id,
        'archived_at' => now(),
    ]);

    $this->actingAs($client)
        ->patchJson("/api/coach/threads/{$thread->id}/restore")
        ->assertStatus(403);
});

test('coach cannot restore another coach\'s thread', function () {
    $coach      = User::factory()->coach()->create();
    $otherCoach = User::factory()->coach()->create();
    $client     = User::factory()->client()->create();
    $thread     = MessageThread::factory()->create([
        'coach_id'    => $otherCoach->id,
        'client_id'   => $client->id,
        'archived_at' => now(),
    ]);

    $this->actingAs($coach)
        ->patchJson("/api/coach/threads/{$thread->id}/restore")
        ->assertStatus(403);
});
