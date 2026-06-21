<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    public function show(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $thread->load([
            'messages' => fn ($q) => $q->oldest(),
        ]);

        return response()->json([
            'id'       => $thread->id,
            'subject'  => $thread->subject,
            'messages' => $thread->messages->map(fn ($message) => [
                'id'         => $message->id,
                'sender_id'  => $message->sender_id,
                'body'       => $message->body,
                'read_at'    => $message->read_at,
                'created_at' => $message->created_at,
            ]),
        ]);
    }
}
