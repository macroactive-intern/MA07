<?php

namespace App\Policies;

use App\Models\MessageThread;
use App\Models\User;

class MessageThreadPolicy
{
    // GET /api/coach/threads, POST /api/coach/threads
    public function manageAsCoach(User $user): bool
    {
        return $user->role === 'coach';
    }

    // GET /api/client/threads
    public function manageAsClient(User $user): bool
    {
        return $user->role === 'client';
    }

    // POST /api/coach/threads
    public function create(User $user): bool
    {
        return $user->role === 'coach';
    }

    // DELETE /api/coach/threads/{thread}
    public function archive(User $user, MessageThread $thread): bool
    {
        return $user->role === 'coach' && $thread->coach_id === $user->id;
    }

    // GET /api/threads/{thread}
    // POST /api/threads/{thread}/messages
    // PATCH /api/threads/{thread}/messages/{message}/read
    public function view(User $user, MessageThread $thread): bool
    {
        return $thread->isParticipant($user);
    }

    public function storeMessage(User $user, MessageThread $thread): bool
    {
        return $thread->isParticipant($user);
    }

    public function markRead(User $user, MessageThread $thread): bool
    {
        return $thread->isParticipant($user);
    }
}
