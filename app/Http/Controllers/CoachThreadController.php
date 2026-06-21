<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreThreadRequest;
use App\Http\Resources\ThreadResource;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
                'body'    => Str::limit($thread->lastMessage->body, config('messaging.preview_length'), ''),
                'sent_at' => $thread->lastMessage->created_at,
            ] : null,
            'unread_count' => $thread->unread_count,
        ]));
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        $this->authorize('create', MessageThread::class);

        $thread = DB::transaction(fn () => MessageThread::create([
            'coach_id'  => $request->user()->id,
            'client_id' => $request->validated('client_id'),
            'subject'   => $request->validated('subject'),
        ]));

        Log::info('thread.created', ['thread_id' => $thread->id, 'coach_id' => $request->user()->id]);

        return response()->json(new ThreadResource($thread), 201);
    }

    public function destroy(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('archive', $thread);

        DB::transaction(function () use ($thread) {
            MessageThread::lockForUpdate()->find($thread->id)->update(['archived_at' => now()]);
        });

        Log::info('thread.archived', ['thread_id' => $thread->id, 'coach_id' => $request->user()->id]);

        return response()->json(['message' => 'Thread archived.']);
    }

    public function restore(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('restore', $thread);

        DB::transaction(function () use ($thread) {
            MessageThread::lockForUpdate()->find($thread->id)->update(['archived_at' => null]);
        });

        Log::info('thread.restored', ['thread_id' => $thread->id, 'coach_id' => $request->user()->id]);

        return response()->json(['message' => 'Thread restored.']);
    }
}
