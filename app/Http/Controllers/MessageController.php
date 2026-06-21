<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('storeMessage', $thread);

        $message = $thread->messages()->create([
            'sender_id' => $request->user()->id,
            'body'      => $request->validated('body'),
        ]);

        return response()->json($message, 201);
    }
}
