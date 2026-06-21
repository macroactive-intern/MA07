<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageReadController extends Controller
{
    public function update(Request $request, MessageThread $thread, Message $message): JsonResponse
    {
        $this->authorize('markRead', $thread);

        abort_unless($message->thread_id === $thread->id, 404);

        DB::transaction(function () use ($message) {
            Message::lockForUpdate()->find($message->id)->update(['read_at' => now()]);
        });

        Log::info('message.read', ['message_id' => $message->id, 'thread_id' => $thread->id, 'user_id' => $request->user()->id]);

        return response()->json(['message' => 'Message marked as read.']);
    }
}
