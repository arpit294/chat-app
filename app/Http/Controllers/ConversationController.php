<?php

namespace App\Http\Controllers;

use App\Events\ConversationRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ConversationController extends Controller
{
    /**
     * Display a listing of the user's conversations with computed unread counts.
     */
    public function index()
    {
        $userId = Auth::id();

        // Get user's conversations ordered by latest activity with calculated unread count
        $conversations = Auth::user()
            ->conversations()
            ->with(['users', 'latestMessage.user'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conv) use ($userId) {
                $pivot = $conv->users->firstWhere('id', $userId)?->pivot;
                $lastReadAt = $pivot?->last_read_at;

                $conv->unread_count = $conv->messages()
                    ->where('user_id', '!=', $userId)
                    ->when($lastReadAt, fn($q) => $q->where('created_at', '>', $lastReadAt))
                    ->count();

                return $conv;
            });

        // Available contacts to start a new chat with
        $users = User::where('id', '!=', $userId)->orderBy('name')->get();

        return view('conversations.index', [
            'conversations' => $conversations,
            'activeConversation' => null,
            'messages' => collect(),
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created conversation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:direct,group'],
            'name' => ['nullable', 'required_if:type,group', 'string', 'max:100'],
            'user_id' => ['nullable', 'required_if:type,direct', 'exists:users,id'],
            'members' => ['nullable', 'required_if:type,group', 'array', 'min:1'],
            'members.*' => ['exists:users,id'],
            'initial_message' => ['nullable', 'string', 'max:5000'],
        ]);

        $currentUserId = Auth::id();

        if ($validated['type'] === 'direct') {
            $targetUserId = (int) $validated['user_id'];

            if ($targetUserId === $currentUserId) {
                return back()->withErrors(['user_id' => 'You cannot start a chat with yourself.']);
            }

            // Check if direct conversation already exists between these 2 users
            $existing = Conversation::where('type', 'direct')
                ->whereHas('users', fn($q) => $q->where('users.id', $currentUserId))
                ->whereHas('users', fn($q) => $q->where('users.id', $targetUserId))
                ->first();

            if ($existing) {
                return redirect()->route('conversations.show', $existing);
            }

            $conversation = Conversation::create([
                'type' => 'direct',
                'name' => null,
            ]);

            $conversation->users()->attach([
                $currentUserId => ['joined_at' => now(), 'last_read_at' => now()],
                $targetUserId => ['joined_at' => now(), 'last_read_at' => null],
            ]);
        } else {
            // Group conversation
            $conversation = Conversation::create([
                'type' => 'group',
                'name' => $validated['name'],
            ]);

            $attachData = [
                $currentUserId => ['joined_at' => now(), 'last_read_at' => now()],
            ];

            foreach ($validated['members'] as $memberId) {
                if ((int) $memberId !== $currentUserId) {
                    $attachData[(int) $memberId] = ['joined_at' => now(), 'last_read_at' => null];
                }
            }

            $conversation->users()->attach($attachData);
        }

        // Optional initial message
        if (!empty($validated['initial_message'])) {
            $conversation->messages()->create([
                'user_id' => $currentUserId,
                'body' => $validated['initial_message'],
                'type' => 'text',
            ]);
            $conversation->touch();
        }

        return redirect()->route('conversations.show', $conversation)
            ->with('status', 'Conversation started successfully!');
    }

    /**
     * Display the specified conversation, update last_read_at, and broadcast read status.
     */
    public function show(Conversation $conversation)
    {
        // 1. Authorize user belongs to this conversation
        Gate::authorize('view', $conversation);

        $userId = Auth::id();
        $now = now();

        // 2. Update current user's last_read_at timestamp on the pivot table
        $conversation->users()->updateExistingPivot($userId, [
            'last_read_at' => $now,
        ]);

        // 3. Broadcast real-time read receipt (with graceful fallback if Reverb is offline)
        try {
            broadcast(new ConversationRead($conversation->id, $userId, $now->toISOString()))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('ConversationRead broadcast skipped: ' . $e->getMessage());
        }

        // 4. List all conversations for the sidebar with unread counts
        $conversations = Auth::user()
            ->conversations()
            ->with(['users', 'latestMessage.user'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conv) use ($userId, $conversation) {
                $pivot = $conv->users->firstWhere('id', $userId)?->pivot;
                $lastReadAt = $conv->id === $conversation->id ? now() : $pivot?->last_read_at;

                $conv->unread_count = $conv->id === $conversation->id 
                    ? 0 
                    : $conv->messages()
                        ->where('user_id', '!=', $userId)
                        ->when($lastReadAt, fn($q) => $q->where('created_at', '>', $lastReadAt))
                        ->count();

                return $conv;
            });

        // 5. Paginate conversation messages with user and attachments
        $messages = $conversation->messages()
            ->with(['user', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->paginate(30);

        // Available contacts for creating new chats
        $users = User::where('id', '!=', $userId)->orderBy('name')->get();

        return view('conversations.index', [
            'conversations' => $conversations,
            'activeConversation' => $conversation->load('users'),
            'messages' => $messages,
            'users' => $users,
        ]);
    }

    /**
     * Endpoint to explicitly mark a conversation as read.
     */
    public function markAsRead(Conversation $conversation)
    {
        Gate::authorize('view', $conversation);

        $userId = Auth::id();
        $now = now();

        $conversation->users()->updateExistingPivot($userId, [
            'last_read_at' => $now,
        ]);

        try {
            broadcast(new ConversationRead($conversation->id, $userId, $now->toISOString()))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('ConversationRead broadcast skipped: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'last_read_at' => $now->toISOString(),
        ]);
    }
}
