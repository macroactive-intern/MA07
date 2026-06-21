<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageReadController extends Controller
{
    public function update(Request $request, MessageThread $thread, Message $message): JsonResponse
    {
        $this->authorize('markRead', $thread);

        abort_unless($message->thread_id === $thread->id, 404);

        $message->update(['read_at' => now()]);

        return response()->json(['message' => 'Message marked as read.']);
    }
}
