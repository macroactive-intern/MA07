<?php

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('coach thread list is ordered by latest message time', function () {
    $coach   = User::factory()->coach()->create();
    $clientA = User::factory()->client()->create();
    $clientB = User::factory()->client()->create();

    $older = MessageThread::factory()->create(['coach_id' => $coach->id, 'client_id' => $clientA->id]);
    $newer = MessageThread::factory()->create(['coach_id' => $coach->id, 'client_id' => $clientB->id]);

    Message::factory()->create([
        'thread_id'  => $older->id,
        'sender_id'  => $clientA->id,
        'created_at' => now()->subHours(2),
    ]);

    Message::factory()->create([
        'thread_id'  => $newer->id,
        'sender_id'  => $clientB->id,
        'created_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($coach)->getJson('/api/coach/threads');

    $response->assertStatus(200);
    $ids = collect($response->json())->pluck('id');
    expect($ids->first())->toBe($newer->id)
        ->and($ids->last())->toBe($older->id);
});

test('coach thread list unread count ignores coach own messages', function () {
    $coach  = User::factory()->coach()->create();
    $client = User::factory()->client()->create();
    $thread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    Message::factory()->count(2)->create([
        'thread_id' => $thread->id,
        'sender_id' => $coach->id,
        'read_at'   => null,
    ]);

    Message::factory()->count(3)->create([
        'thread_id' => $thread->id,
        'sender_id' => $client->id,
        'read_at'   => null,
    ]);

    $response = $this->actingAs($coach)->getJson('/api/coach/threads');

    $response->assertStatus(200);
    expect($response->json('0.unread_count'))->toBe(3);
});

test('coach thread list avoids N+1 queries', function () {
    $coach   = User::factory()->coach()->create();
    $clients = User::factory()->client()->count(5)->create();

    foreach ($clients as $client) {
        $thread = MessageThread::factory()->create([
            'coach_id'  => $coach->id,
            'client_id' => $client->id,
        ]);
        Message::factory()->create([
            'thread_id' => $thread->id,
            'sender_id' => $client->id,
        ]);
    }

    DB::enableQueryLog();
    $this->actingAs($coach)->getJson('/api/coach/threads')->assertStatus(200);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBeLessThanOrEqual(5);
});
