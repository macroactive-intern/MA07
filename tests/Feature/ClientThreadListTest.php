<?php

use App\Models\MessageThread;
use App\Models\User;

test('client thread list only returns threads where the user is the client', function () {
    $coach       = User::factory()->coach()->create();
    $client      = User::factory()->client()->create();
    $otherClient = User::factory()->client()->create();

    $ownThread = MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $client->id,
    ]);

    MessageThread::factory()->create([
        'coach_id'  => $coach->id,
        'client_id' => $otherClient->id,
    ]);

    $response = $this->actingAs($client)->getJson('/api/client/threads');

    $response->assertStatus(200);
    $ids = collect($response->json())->pluck('id');
    expect($ids)->toHaveCount(1)
        ->and($ids->first())->toBe($ownThread->id);
});
