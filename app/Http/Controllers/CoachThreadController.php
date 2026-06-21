<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreThreadRequest;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoachThreadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageAsCoach', MessageThread::class);

        $coach = $request->user();

        $threads = MessageThread::query()
            ->where('coach_id', $coach->id)
            ->whereNull('archived_at')
            ->with('client:id,name')
            ->with(['lastMessage' => fn ($q) => $q->select('messages.id', 'messages.thread_id', 'messages.body', 'messages.created_at')])
            ->withCount([
                'messages as unread_count' => function ($query) use ($coach) {
                    $query->whereNull('read_at')
                          ->where('sender_id', '!=', $coach->id);
                },
            ])
            ->orderByRaw('(SELECT MAX(created_at) FROM messages WHERE messages.thread_id = message_threads.id) DESC')
            ->get();

        return response()->json($threads->map(fn ($thread) => [
            'id'           => $thread->id,
            'subject'      => $thread->subject,
            'client'       => ['name' => $thread->client->name],
            'last_message' => $thread->lastMessage ? [
                'body'    => Str::limit($thread->lastMessage->body, 100, ''),
                'sent_at' => $thread->lastMessage->created_at,
            ] : null,
            'unread_count' => $thread->unread_count,
        ]));
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        $this->authorize('create', MessageThread::class);

        $thread = MessageThread::create([
            'coach_id'  => $request->user()->id,
            'client_id' => $request->validated('client_id'),
            'subject'   => $request->validated('subject'),
        ]);

        return response()->json($thread, 201);
    }

    public function destroy(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('archive', $thread);

        $thread->update(['archived_at' => now()]);

        return response()->json(['message' => 'Thread archived.']);
    }
}
