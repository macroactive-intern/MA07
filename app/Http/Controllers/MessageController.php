<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('storeMessage', $thread);

        $message = $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body'      => $request->validated('body'),
        ]);

        Log::info('message.sent', ['message_id' => $message->id, 'thread_id' => $thread->id, 'sender_id' => $request->user()->id]);

        return response()->json(new MessageResource($message), 201);
    }
}
