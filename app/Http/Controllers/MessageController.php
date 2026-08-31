<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Store and broadcast a newly created message with file attachment support.
     */
    public function store(Request $request, Conversation $conversation)
    {
        // 1. Authorize user belongs to this conversation
        Gate::authorize('sendMessage', $conversation);

        // 2. Validate request (body or attachment required, max 10MB)
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpeg,png,jpg,gif,webp,svg,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
        ]);

        $hasBody = !empty(trim($validated['body'] ?? ''));
        $hasSingleFile = $request->hasFile('attachment');
        $hasMultiFiles = $request->hasFile('attachments');

        if (!$hasBody && !$hasSingleFile && !$hasMultiFiles) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Please enter a message or select a file.'], 422);
            }
            return back()->withErrors(['body' => 'Please enter a message or select a file.']);
        }

        $userId = Auth::id();

        // Determine message type
        $messageType = 'text';
        $uploadedFiles = [];

        if ($hasSingleFile) {
            $uploadedFiles[] = $request->file('attachment');
        }
        if ($hasMultiFiles) {
            $uploadedFiles = array_merge($uploadedFiles, $request->file('attachments'));
        }

        if (!empty($uploadedFiles)) {
            $firstMime = $uploadedFiles[0]->getMimeType();
            $messageType = str_starts_with($firstMime, 'image/') ? 'image' : 'file';
        }

        // 3. Save message
        $message = $conversation->messages()->create([
            'user_id' => $userId,
            'body' => $validated['body'] ?? null,
            'type' => $messageType,
        ]);

        // 4. Process and store file attachments
        foreach ($uploadedFiles as $file) {
            $origName = $file->getClientOriginalName();
            $mime = $file->getMimeType();
            $size = $file->getSize();
            $extension = $file->getClientOriginalExtension();
            $uniqueName = Str::random(24) . '.' . $extension;

            // Store original file on public disk
            $targetDir = 'chat_attachments/' . $conversation->id;
            $filePath = $file->storeAs($targetDir, $uniqueName, 'public');

            // Generate thumbnail if image
            $thumbnailPath = null;
            if (str_starts_with($mime, 'image/') && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $thumbnailPath = $this->generateThumbnail($file, $targetDir, pathinfo($uniqueName, PATHINFO_FILENAME));
            }

            MessageAttachment::create([
                'message_id' => $message->id,
                'file_path' => $filePath,
                'thumbnail_path' => $thumbnailPath,
                'original_name' => $origName,
                'mime_type' => $mime,
                'file_size' => $size,
            ]);
        }

        // 5. Update sender's last_read_at on the conversation_user pivot table
        $conversation->users()->updateExistingPivot($userId, [
            'last_read_at' => now(),
        ]);

        // 6. Update conversation updated_at for latest activity ordering
        $conversation->touch();

        // 7. Load relations for broadcast and response
        $message->load(['user', 'attachments']);

        // 8. Broadcast event over Reverb WebSocket (with graceful fallback if Reverb is offline)
        try {
            broadcast(new MessageSent($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('MessageSent broadcast skipped: ' . $e->getMessage());
        }

        // 9. Send notification (mail + database) to participants who are currently offline
        $offlineParticipants = $conversation->users()
            ->where('users.id', '!=', $userId)
            ->where('is_online', false)
            ->get();

        if ($offlineParticipants->isNotEmpty()) {
            try {
                Notification::send($offlineParticipants, new NewMessageNotification($message));
            } catch (\Throwable $e) {
                Log::warning('Offline notification failed: ' . $e->getMessage());
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'user_id' => $message->user_id,
                    'is_sender' => true,
                    'body' => $message->body,
                    'type' => $message->type,
                    'formatted_time' => $message->formatted_time,
                    'formatted_date' => $message->formatted_date,
                    'created_at' => $message->created_at->toISOString(),
                    'sender' => [
                        'id' => Auth::user()->id,
                        'name' => Auth::user()->name,
                        'avatar_url' => Auth::user()->avatar_url,
                    ],
                    'attachments' => $message->attachments->map(fn($att) => [
                        'id' => $att->id,
                        'original_name' => $att->original_name,
                        'mime_type' => $att->mime_type,
                        'file_size' => $att->file_size,
                        'formatted_size' => $att->formatted_size,
                        'url' => $att->url,
                        'thumbnail_url' => $att->thumbnail_url,
                        'is_image' => $att->is_image,
                        'is_pdf' => $att->is_pdf,
                    ])->values(),
                ],
            ]);
        }

        return redirect()->route('conversations.show', $conversation)
            ->with('status', 'Message sent.');
    }

    /**
     * Generate an image thumbnail using native PHP GD library.
     */
    private function generateThumbnail($uploadedFile, string $targetDir, string $fileBaseName): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $mime = $uploadedFile->getMimeType();
        $sourcePath = $uploadedFile->getRealPath();

        $srcImage = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => @imagecreatefromwebp($sourcePath),
            'image/gif' => @imagecreatefromgif($sourcePath),
            default => null,
        };

        if (!$srcImage) {
            return null;
        }

        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);
        $maxThumbDim = 320;

        if ($origWidth <= $maxThumbDim && $origHeight <= $maxThumbDim) {
            $thumbWidth = $origWidth;
            $thumbHeight = $origHeight;
        } elseif ($origWidth > $origHeight) {
            $thumbWidth = $maxThumbDim;
            $thumbHeight = (int) round(($origHeight / $origWidth) * $maxThumbDim);
        } else {
            $thumbHeight = $maxThumbDim;
            $thumbWidth = (int) round(($origWidth / $origHeight) * $maxThumbDim);
        }

        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($thumbImage, false);
            imagesavealpha($thumbImage, true);
        }

        imagecopyresampled($thumbImage, $srcImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $origWidth, $origHeight);

        $thumbRelativePath = "{$targetDir}/thumb_{$fileBaseName}.jpg";
        $fullDiskPath = Storage::disk('public')->path($thumbRelativePath);

        $fullDir = dirname($fullDiskPath);
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        imagejpeg($thumbImage, $fullDiskPath, 85);
        imagedestroy($srcImage);
        imagedestroy($thumbImage);

        return $thumbRelativePath;
    }

    /**
     * Update an existing message.
     */
    public function update(Request $request, Conversation $conversation, Message $message)
    {
        Gate::authorize('sendMessage', $conversation);
        
        if ($message->user_id !== Auth::id() || $message->is_deleted) {
            abort(403, 'Unauthorized or message already deleted.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update([
            'body' => $validated['body'],
            'is_edited' => true,
        ]);

        try {
            broadcast(new \App\Events\MessageEdited($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('MessageEdited broadcast skipped: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'is_edited' => $message->is_edited,
                    'formatted_time' => $message->formatted_time,
                ]
            ]);
        }

        return back()->with('status', 'Message updated.');
    }

    /**
     * Delete a message.
     */
    public function destroy(Request $request, Conversation $conversation, Message $message)
    {
        Gate::authorize('sendMessage', $conversation);
        
        if ($message->user_id !== Auth::id() || $message->is_deleted) {
            abort(403, 'Unauthorized or message already deleted.');
        }

        $message->update([
            'is_deleted' => true,
            'deleted_at' => now(),
            'body' => 'This message was deleted',
        ]);

        try {
            broadcast(new \App\Events\MessageDeleted($message))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('MessageDeleted broadcast skipped: ' . $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'is_deleted' => true,
                    'body' => $message->body,
                ]
            ]);
        }

        return back()->with('status', 'Message deleted.');
    }
}
