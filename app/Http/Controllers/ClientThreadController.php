<?php

namespace App\Http\Controllers;

use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientThreadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageAsClient', MessageThread::class);

        $threads = MessageThread::query()
            ->where('client_id', $request->user()->id)
            ->whereNull('archived_at')
            ->with('coach:id,name')
            ->get();

        return response()->json($threads);
    }
}
