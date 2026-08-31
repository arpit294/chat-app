<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Search users to start new chat or add to group.
     */
    public function search(Request $request)
    {
        $currentUserId = Auth::id();
        $query = $request->input('q', '');

        $users = User::where('id', '!=', $currentUserId)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                });
            })
            ->limit(20)
            ->get(['id', 'name', 'email', 'avatar', 'status_message', 'is_online', 'last_seen_at']);

        $data = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar_url' => $u->avatar_url,
                'status_message' => $u->status_message,
                'is_online' => (bool) $u->is_online,
                'last_seen' => $u->last_seen_at ? $u->last_seen_at->diffForHumans() : 'offline',
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $data,
        ]);
    }

    /**
     * Heartbeat to keep user status updated.
     */
    public function ping(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->update([
                'is_online' => true,
                'last_seen_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
