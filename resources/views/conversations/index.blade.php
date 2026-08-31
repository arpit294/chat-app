@extends('layouts.app')

@section('title', 'Conversations - ChatApp')

@section('content')
<div class="container-fluid px-2 px-md-3">
    <div class="chat-container">
        <div class="card chat-card border-0">
            <div class="row g-0 h-100">

                <!-- ============================================== -->
                <!-- 1. LEFT COLUMN: User's Conversations List      -->
                <!-- ============================================== -->
                <div class="col-12 col-md-4 col-lg-3 chat-sidebar">
                    
                    <!-- Action Bar: New 1-on-1 or Group Chat -->
                    <div class="chat-sidebar-header">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm flex-fill d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#newDirectModal">
                                <i class="bi bi-chat-plus"></i>
                                <span>New Chat</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#newGroupModal">
                                <i class="bi bi-people"></i>
                                <span>Group</span>
                            </button>
                        </div>
                    </div>

                    <!-- List of Conversations with Unread Counts -->
                    <div class="chat-list" id="conversations-list">
                        @forelse ($conversations as $conv)
                            @php
                                $isActive = $activeConversation && $activeConversation->id === $conv->id;
                                $latest = $conv->latestMessage;
                                $unreadCount = $conv->unread_count ?? 0;
                            @endphp

                            <a href="{{ route('conversations.show', $conv) }}" class="chat-list-item {{ $isActive ? 'active' : '' }}" data-conversation-id="{{ $conv->id }}">
                                <div class="avatar-wrap me-3">
                                    <img src="{{ $conv->avatar_url }}" alt="{{ $conv->display_name }}" class="avatar-img">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-truncate" style="font-size: 0.9rem;">{{ $conv->display_name }}</strong>
                                        @if ($latest)
                                            <small class="text-muted conv-time" style="font-size: 0.72rem;">{{ $latest->formatted_time }}</small>
                                        @endif
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-truncate small conv-snippet {{ $unreadCount > 0 ? 'fw-bold text-dark' : 'text-muted' }}" style="font-size: 0.82rem;">
                                            @if ($latest)
                                                {{ $latest->user_id === auth()->id() ? 'You: ' : '' }}
                                                @if ($latest->body)
                                                    {{ $latest->body }}
                                                @elseif ($latest->type === 'image')
                                                    <i class="bi bi-image"></i> Photo
                                                @else
                                                    <i class="bi bi-file-earmark"></i> Attachment
                                                @endif
                                            @else
                                                <em class="text-muted">No messages yet</em>
                                            @endif
                                        </span>
                                        @if ($unreadCount > 0)
                                            <span class="badge bg-primary rounded-pill ms-1 unread-count-badge">{{ $unreadCount }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-5 text-muted px-3">
                                <i class="bi bi-chat-square-text fs-3 text-secondary mb-2 d-block"></i>
                                <p class="small mb-2">No conversations yet.</p>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newDirectModal">Start a Chat</button>
                            </div>
                        @endforelse
                    </div>

                </div>

                <!-- ============================================== -->
                <!-- 2. RIGHT COLUMN: Active Chat Messages & Form   -->
                <!-- ============================================== -->
                <div class="col-12 col-md-8 col-lg-9 chat-main-area position-relative">

                    @if ($activeConversation)
                        <!-- Active Conversation Header with Call Actions -->
                        <div class="chat-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-wrap">
                                    <img src="{{ $activeConversation->avatar_url }}" alt="{{ $activeConversation->display_name }}" class="avatar-img">
                                    <span class="status-dot" id="active-user-status-dot"></span>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-bold">{{ $activeConversation->display_name }}</h6>
                                        <span class="badge bg-secondary rounded-pill" id="presence-badge" style="font-size: 0.65rem;">Connecting...</span>
                                    </div>
                                    <div class="small text-muted" id="active-user-status-text">
                                        @if ($activeConversation->isGroup())
                                            {{ $activeConversation->users->count() }} members
                                        @else
                                            Offline
                                        @endif
                                    </div>
                                    <!-- Typing Indicator -->
                                    <div id="typing-indicator" class="small text-primary fst-italic d-none">
                                        <span id="typing-text"></span>
                                        <span class="spinner-grow spinner-grow-sm text-primary" style="width: 8px; height: 8px;" role="status"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Voice & Video Call Action Buttons -->
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" id="btn-start-audio-call" style="width: 36px; height: 36px;" title="Start Voice Call">
                                    <i class="bi bi-telephone-fill text-primary"></i>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm rounded-circle d-flex align-items-center justify-content-center" id="btn-start-video-call" style="width: 36px; height: 36px;" title="Start Video Call">
                                    <i class="bi bi-camera-video-fill"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Status Notification -->
                        @if (session('status'))
                            <div class="alert alert-success py-2 px-3 m-2 small mb-0 rounded" id="flash-status-alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger py-2 px-3 m-2 small mb-0 rounded">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Messages Stream Area -->
                        <div class="chat-messages" id="messages-stream">
                            
                            <!-- Pagination links if multiple pages -->
                            @if ($messages->hasPages())
                                <div class="d-flex justify-content-center my-2">
                                    {{ $messages->links() }}
                                </div>
                            @endif

                            @forelse ($messages as $msg)
                                @php $isSender = $msg->user_id === auth()->id(); @endphp
                                <div class="message-row {{ $isSender ? 'outgoing' : 'incoming' }}">
                                    <div class="message-bubble">
                                        @if (!$isSender && $activeConversation->isGroup())
                                            <small class="fw-bold d-block text-primary mb-1">{{ $msg->user?->name ?? 'User' }}</small>
                                        @endif

                                        <!-- Attachments -->
                                        @if ($msg->attachments->isNotEmpty())
                                            <div class="message-attachments mb-2">
                                                @foreach ($msg->attachments as $att)
                                                    @if ($att->is_image)
                                                        <div class="attachment-image mb-1">
                                                            <a href="{{ $att->url }}" target="_blank" class="d-block text-decoration-none">
                                                                <img src="{{ $att->thumbnail_url }}" alt="{{ $att->original_name }}" class="img-fluid rounded border" style="max-height: 220px; object-fit: cover;">
                                                            </a>
                                                        </div>
                                                    @else
                                                        <div class="attachment-file p-2 rounded bg-white bg-opacity-75 border mb-1 d-flex align-items-center gap-2">
                                                            <i class="bi {{ $att->is_pdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-text text-primary' }} fs-4"></i>
                                                            <div class="overflow-hidden flex-grow-1">
                                                                <a href="{{ $att->url }}" target="_blank" download class="fw-semibold text-truncate d-block small text-dark">
                                                                    {{ $att->original_name }}
                                                                </a>
                                                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $att->formatted_size }}</small>
                                                            </div>
                                                            <a href="{{ $att->url }}" target="_blank" download class="btn btn-sm btn-light border py-0 px-2" title="Download">
                                                                <i class="bi bi-download"></i>
                                                            </a>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($msg->body)
                                            <div class="message-content">{{ $msg->body }}</div>
                                        @endif

                                        <div class="message-time">
                                            <span>{{ $msg->formatted_time }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted small" id="no-messages-placeholder">
                                    <i class="bi bi-chat-text fs-3 mb-2 d-block text-secondary"></i>
                                    No messages yet. Send a message below to start the conversation!
                                </div>
                            @endforelse
                        </div>

                        <!-- ============================================== -->
                        <!-- EMOJI PICKER POPUP                             -->
                        <!-- ============================================== -->
                        <div id="emoji-picker-popup" class="card shadow-lg border-0 position-absolute d-none" style="bottom: 75px; left: 20px; width: 330px; z-index: 1060; border-radius: 12px; background: #fff;">
                            <div class="card-header bg-white border-bottom-0 py-2 px-3 d-flex justify-content-between align-items-center">
                                <span class="small fw-bold text-dark"><i class="bi bi-emoji-smile me-1 text-warning"></i> Emoji Picker</span>
                                <button type="button" class="btn-close btn-sm" id="btn-close-emoji" aria-label="Close"></button>
                            </div>
                            <div class="px-3 pb-2">
                                <input type="text" id="emoji-search-input" class="form-control form-control-sm" placeholder="Search emojis... (e.g. smile, heart, fire)">
                            </div>
                            <div class="card-body p-2 pt-0 overflow-auto" id="emoji-list-container" style="max-height: 210px;">
                                <!-- Emoji Categories will be populated by JS -->
                            </div>
                        </div>

                        <!-- Message Submit Form (AJAX + WebSockets + Attachments + Emoji Support) -->
                        <div class="chat-footer">
                            
                            <!-- Selected Attachment Preview Chip -->
                            <div id="attachment-preview-bar" class="d-none p-2 mb-2 bg-light border rounded d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2 overflow-hidden">
                                    <i class="bi bi-paperclip fs-5 text-primary"></i>
                                    <span id="attachment-filename" class="text-truncate small fw-semibold"></span>
                                    <small id="attachment-filesize" class="text-muted"></small>
                                </div>
                                <button type="button" class="btn btn-sm text-danger p-0" id="btn-remove-attachment" title="Remove attachment">
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>

                            <form method="POST" action="{{ route('messages.store', $activeConversation) }}" enctype="multipart/form-data" id="chat-message-form" class="m-0">
                                @csrf
                                <div class="input-group">
                                    
                                    <!-- Emoji Picker Button -->
                                    <button type="button" class="btn btn-outline-secondary d-flex align-items-center" id="btn-emoji-toggle" title="Insert Emoji">
                                        <i class="bi bi-emoji-smile fs-5"></i>
                                    </button>

                                    <!-- Attachment Picker Button -->
                                    <label for="attachment-input" class="btn btn-outline-secondary d-flex align-items-center" title="Attach file or image (max 10MB)" style="cursor: pointer;">
                                        <i class="bi bi-paperclip fs-5"></i>
                                    </label>
                                    <input type="file" name="attachment" id="attachment-input" class="d-none" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">

                                    <!-- Message Input Field -->
                                    <input type="text" name="body" id="message-body-input" class="form-control" placeholder="Type a message or emoji... :)" autocomplete="off" autofocus>
                                    
                                    <!-- Send Button -->
                                    <button type="submit" class="btn btn-primary px-3" id="btn-send-message">
                                        <i class="bi bi-send-fill me-1" id="send-icon"></i>
                                        <span class="spinner-border spinner-border-sm d-none" id="send-spinner" role="status"></span>
                                        <span>Send</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    @else
                        <!-- Blank / Empty State -->
                        <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-4 text-muted">
                            <i class="bi bi-chat-dots display-3 text-secondary mb-3 opacity-50"></i>
                            <h5 class="fw-bold text-dark">Select a Conversation</h5>
                            <p class="small text-muted mb-3" style="max-width: 360px;">
                                Choose an existing conversation from the left sidebar or start a new chat with your contacts.
                            </p>
                            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#newDirectModal">
                                <i class="bi bi-person-plus me-1"></i> Start New Chat
                            </button>
                        </div>
                    @endif

                </div>

            </div>
        </div>
    </div>
</div>

<!-- ======================================================= -->
<!-- 3. WEBRTC AUDIO & VIDEO CALL OVERLAY & INCOMING MODAL    -->
<!-- ======================================================= -->

<!-- Incoming Call Notification Modal -->
<div class="modal fade" id="incomingCallModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg text-center p-3" style="border-radius: 16px;">
            <div class="modal-body">
                <div class="avatar-wrap mb-3 d-inline-block">
                    <img src="" id="incoming-caller-avatar" alt="Caller" class="call-avatar-pulsing">
                </div>
                <h5 class="fw-bold mb-1" id="incoming-caller-name">User</h5>
                <p class="text-muted small mb-4" id="incoming-call-type">Incoming Video Call...</p>
                
                <div class="d-flex justify-content-center gap-3">
                    <!-- Reject Call Button -->
                    <button type="button" class="call-btn-circle call-btn-hangup" id="btn-reject-incoming-call" title="Decline">
                        <i class="bi bi-telephone-x-fill"></i>
                    </button>
                    <!-- Accept Call Button -->
                    <button type="button" class="call-btn-circle call-btn-accept" id="btn-accept-incoming-call" title="Accept">
                        <i class="bi bi-telephone-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Call Fullscreen Video/Audio Overlay -->
<div id="active-call-overlay" class="call-overlay d-none">
    <!-- Call Top Bar -->
    <div class="call-header-bar">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger rounded-pill p-2"><i class="bi bi-record-fill me-1"></i> LIVE</span>
            <span class="fw-semibold" id="active-call-partner-name">Calling...</span>
        </div>
        <div class="badge bg-dark bg-opacity-75 rounded-pill px-3 py-2 fs-6 fw-normal" id="active-call-timer">
            00:00
        </div>
    </div>

    <!-- Central Viewport -->
    <div class="call-viewport">
        <!-- Remote Video Stream -->
        <video id="remoteVideo" autoplay playsinline class="remote-video d-none"></video>

        <!-- Voice Call Avatar Card (when video is off) -->
        <div id="audio-call-placeholder" class="call-audio-avatar-card">
            <img src="" id="active-call-avatar" alt="Partner" class="call-avatar-pulsing">
            <h4 class="fw-bold mt-2" id="active-call-title">Voice Call</h4>
            <p class="text-white-50 small" id="active-call-status">Connecting audio stream...</p>
        </div>

        <!-- Local Self-View Video (Picture in Picture) -->
        <video id="localVideo" autoplay playsinline muted class="local-video-pip d-none"></video>
    </div>

    <!-- Call Control Actions Bar -->
    <div class="call-control-bar">
        <!-- Toggle Microphone -->
        <button type="button" class="call-btn-circle call-btn-secondary" id="btn-toggle-mic" title="Mute/Unmute Mic">
            <i class="bi bi-mic-fill" id="icon-mic"></i>
        </button>

        <!-- Toggle Video Camera -->
        <button type="button" class="call-btn-circle call-btn-secondary" id="btn-toggle-cam" title="Turn Camera On/Off">
            <i class="bi bi-camera-video-fill" id="icon-cam"></i>
        </button>

        <!-- Share Screen -->
        <button type="button" class="call-btn-circle call-btn-secondary" id="btn-share-screen" title="Share Screen">
            <i class="bi bi-display"></i>
        </button>

        <!-- Hangup Button -->
        <button type="button" class="call-btn-circle call-btn-hangup" id="btn-end-call" title="End Call">
            <i class="bi bi-telephone-x-fill"></i>
        </button>
    </div>
</div>

<!-- ============================================== -->
<!-- 4. MODALS: New 1-on-1 & New Group Forms        -->
<!-- ============================================== -->

<!-- Modal: New 1-on-1 Chat -->
<div class="modal fade" id="newDirectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Start 1-on-1 Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('conversations.store') }}">
                @csrf
                <input type="hidden" name="type" value="direct">
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="direct-user-select" class="form-label small fw-semibold">Select User</label>
                        <select name="user_id" id="direct-user-select" class="form-select" required>
                            <option value="">-- Choose a contact --</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label for="direct-initial-msg" class="form-label small fw-semibold">First Message (Optional)</label>
                        <input type="text" name="initial_message" id="direct-initial-msg" class="form-control" placeholder="Hello... 👋">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Start Chat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: New Group Chat -->
<div class="modal fade" id="newGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-people text-primary me-2"></i> Create Group Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('conversations.store') }}">
                @csrf
                <input type="hidden" name="type" value="group">
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="group-name" class="form-label small fw-semibold">Group Name</label>
                        <input type="text" name="name" id="group-name" class="form-control" required placeholder="e.g. Development Team 🚀">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold d-block">Select Members</label>
                        <div class="border rounded p-2 bg-light" style="max-height: 180px; overflow-y: auto;">
                            @foreach ($users as $u)
                                <div class="form-check py-1">
                                    <input class="form-check-input" type="checkbox" name="members[]" value="{{ $u->id }}" id="grp-usr-{{ $u->id }}">
                                    <label class="form-check-label small" for="grp-usr-{{ $u->id }}">
                                        {{ $u->name }} <span class="text-muted">({{ $u->email }})</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="group-initial-msg" class="form-label small fw-semibold">First Message (Optional)</label>
                        <input type="text" name="initial_message" id="group-initial-msg" class="form-control" placeholder="Welcome everyone! 🎉">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stream = document.getElementById('messages-stream');
    const CURRENT_USER_ID = {{ auth()->id() }};
    const CURRENT_USER_NAME = "{{ addslashes(auth()->user()->name) }}";
    const CURRENT_USER_AVATAR = "{{ auth()->user()->avatar_url }}";

    function scrollToBottom() {
        if (stream) {
            stream.scrollTop = stream.scrollHeight;
        }
    }

    scrollToBottom();

    // Helper to append message bubble to UI
    function appendMessageBubble(data, isSender) {
        const placeholder = document.getElementById('no-messages-placeholder');
        if (placeholder) {
            placeholder.remove();
        }

        const senderName = data.sender ? data.sender.name : 'User';
        const isGroup = {{ $activeConversation && $activeConversation->isGroup() ? 'true' : 'false' }};
        const senderHeader = (!isSender && isGroup) 
            ? `<small class="fw-bold d-block text-primary mb-1">${escapeHtml(senderName)}</small>` 
            : '';

        let attachmentsHtml = '';
        if (data.attachments && data.attachments.length > 0) {
            attachmentsHtml = '<div class="message-attachments mb-2">';
            data.attachments.forEach(att => {
                if (att.is_image) {
                    attachmentsHtml += `
                        <div class="attachment-image mb-1">
                            <a href="${att.url}" target="_blank" class="d-block text-decoration-none">
                                <img src="${att.thumbnail_url || att.url}" alt="${escapeHtml(att.original_name)}" class="img-fluid rounded border" style="max-height: 220px; object-fit: cover;">
                            </a>
                        </div>
                    `;
                } else {
                    const iconClass = att.is_pdf ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-text text-primary';
                    attachmentsHtml += `
                        <div class="attachment-file p-2 rounded bg-white bg-opacity-75 border mb-1 d-flex align-items-center gap-2">
                            <i class="bi ${iconClass} fs-4"></i>
                            <div class="overflow-hidden flex-grow-1">
                                <a href="${att.url}" target="_blank" download class="fw-semibold text-truncate d-block small text-dark">
                                    ${escapeHtml(att.original_name)}
                                </a>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">${att.formatted_size || ''}</small>
                            </div>
                            <a href="${att.url}" target="_blank" download class="btn btn-sm btn-light border py-0 px-2" title="Download">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    `;
                }
            });
            attachmentsHtml += '</div>';
        }

        const bodyHtml = data.body ? `<div class="message-content">${escapeHtml(data.body)}</div>` : '';

        const row = document.createElement('div');
        row.className = `message-row ${isSender ? 'outgoing' : 'incoming'}`;
        row.innerHTML = `
            <div class="message-bubble">
                ${senderHeader}
                ${attachmentsHtml}
                ${bodyHtml}
                <div class="message-time">
                    <span>${data.formatted_time || 'Just now'}</span>
                </div>
            </div>
        `;

        if (stream) {
            stream.appendChild(row);
            scrollToBottom();
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    @if ($activeConversation)
        const conversationId = {{ $activeConversation->id }};
        const isGroup = {{ $activeConversation->isGroup() ? 'true' : 'false' }};
        @php
            $otherUser = $activeConversation->getOtherUser(auth()->id());
        @endphp
        const otherUserId = {{ $otherUser ? $otherUser->id : 'null' }};
        const otherUserName = "{{ $otherUser ? addslashes($otherUser->name) : 'User' }}";
        const otherUserAvatar = "{{ $otherUser ? $otherUser->avatar_url : '' }}";

        const presenceBadge = document.getElementById('presence-badge');
        const statusDot = document.getElementById('active-user-status-dot');
        const statusText = document.getElementById('active-user-status-text');
        const typingBox = document.getElementById('typing-indicator');
        const typingText = document.getElementById('typing-text');
        let typingTimeout = null;
        let onlineMembers = new Map();

        function updatePresenceUI() {
            const count = onlineMembers.size;
            if (presenceBadge) {
                presenceBadge.textContent = `${count} online`;
                presenceBadge.className = count > 0 ? 'badge bg-success rounded-pill' : 'badge bg-secondary rounded-pill';
            }

            if (!isGroup && otherUserId) {
                const isOtherOnline = onlineMembers.has(otherUserId);
                if (statusDot) {
                    statusDot.className = isOtherOnline ? 'status-dot online' : 'status-dot';
                }
                if (statusText) {
                    statusText.textContent = isOtherOnline ? 'Online' : 'Offline';
                }
            }
        }

        function updateSidebarConversation(msg) {
            if (!msg || !msg.conversation_id) return;
            const convItem = document.querySelector(`.chat-list-item[data-conversation-id="${msg.conversation_id}"]`);
            if (convItem) {
                const snippetEl = convItem.querySelector('.conv-snippet');
                const timeEl = convItem.querySelector('.conv-time');
                if (snippetEl) {
                    const prefix = msg.user_id === CURRENT_USER_ID ? 'You: ' : '';
                    snippetEl.textContent = prefix + (msg.body || (msg.type === 'image' ? 'Photo' : 'Attachment'));
                }
                if (timeEl) {
                    timeEl.textContent = msg.formatted_time || 'Just now';
                }
            }
        }

        function handleIncomingMessage(e) {
            if (e.user_id !== CURRENT_USER_ID) {
                appendMessageBubble(e, false);
                if (typeof window.playNotificationSound === 'function') {
                    window.playNotificationSound();
                }
            }
            updateSidebarConversation(e);
        }

        // =============================================================
        // WEBRTC CALLING STATE & PEER CONNECTION
        // =============================================================
        let peerConnection = null;
        let localStream = null;
        let remoteStream = null;
        let isCallInitiator = false;
        let isVideoCall = false;
        let callTimerInterval = null;
        let callSeconds = 0;
        let pendingIncomingSignal = null;

        const rtcConfiguration = {
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        };

        const activeCallOverlay = document.getElementById('active-call-overlay');
        const incomingCallModalEl = document.getElementById('incomingCallModal');
        const incomingCallModal = incomingCallModalEl ? new bootstrap.Modal(incomingCallModalEl) : null;
        const localVideo = document.getElementById('localVideo');
        const remoteVideo = document.getElementById('remoteVideo');
        const audioPlaceholder = document.getElementById('audio-call-placeholder');
        const activeCallTimer = document.getElementById('active-call-timer');
        const activeCallPartnerName = document.getElementById('active-call-partner-name');
        const activeCallAvatar = document.getElementById('active-call-avatar');
        const activeCallTitle = document.getElementById('active-call-title');
        const activeCallStatus = document.getElementById('active-call-status');

        const btnStartAudioCall = document.getElementById('btn-start-audio-call');
        const btnStartVideoCall = document.getElementById('btn-start-video-call');
        const btnAcceptCall = document.getElementById('btn-accept-incoming-call');
        const btnRejectCall = document.getElementById('btn-reject-incoming-call');
        const btnEndCall = document.getElementById('btn-end-call');
        const btnToggleMic = document.getElementById('btn-toggle-mic');
        const btnToggleCam = document.getElementById('btn-toggle-cam');
        const btnShareScreen = document.getElementById('btn-share-screen');

        let presenceChannel = null;

        // -------------------------------------------------------------
        // 1. Presence Channel Subscription via Echo.join()
        // -------------------------------------------------------------
        if (window.Echo) {
            presenceChannel = window.Echo.join(`chat.${conversationId}`);

            presenceChannel
                .here((users) => {
                    users.forEach(u => onlineMembers.set(u.id, u));
                    updatePresenceUI();
                })
                .joining((user) => {
                    onlineMembers.set(user.id, user);
                    updatePresenceUI();
                })
                .leaving((user) => {
                    onlineMembers.delete(user.id);
                    updatePresenceUI();
                    if (peerConnection && user.id === otherUserId) {
                        endCall(false, 'Participant left the call.');
                    }
                })
                .listen('.MessageSent', handleIncomingMessage)
                .listen('MessageSent', handleIncomingMessage)
                .listen('App\\Events\\MessageSent', handleIncomingMessage)
                .listenForWhisper('typing', (e) => {
                    if (e.id !== CURRENT_USER_ID) {
                        showTypingIndicator(e.name);
                    }
                })
                // -----------------------------------------------------
                // WebRTC Calling Whispers
                // -----------------------------------------------------
                .listenForWhisper('call-invitation', (data) => {
                    if (data.targetId === CURRENT_USER_ID) {
                        handleIncomingCall(data);
                    }
                })
                .listenForWhisper('call-accepted', (data) => {
                    if (data.targetId === CURRENT_USER_ID && isCallInitiator) {
                        handleCallAccepted();
                    }
                })
                .listenForWhisper('call-rejected', (data) => {
                    if (data.targetId === CURRENT_USER_ID) {
                        endCall(false, data.reason || 'Call was declined.');
                    }
                })
                .listenForWhisper('call-ended', (data) => {
                    if (data.targetId === CURRENT_USER_ID) {
                        endCall(false, 'Call ended.');
                    }
                })
                .listenForWhisper('webrtc-offer', async (data) => {
                    if (data.targetId === CURRENT_USER_ID) {
                        await handleWebRtcOffer(data.offer);
                    }
                })
                .listenForWhisper('webrtc-answer', async (data) => {
                    if (data.targetId === CURRENT_USER_ID) {
                        await handleWebRtcAnswer(data.answer);
                    }
                })
                .listenForWhisper('webrtc-candidate', async (data) => {
                    if (data.targetId === CURRENT_USER_ID && peerConnection) {
                        try {
                            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
                        } catch (err) {
                            console.error('Error adding ICE candidate:', err);
                        }
                    }
                });

            function showTypingIndicator(name) {
                if (typingBox && typingText) {
                    typingText.textContent = `${name} is typing...`;
                    typingBox.classList.remove('d-none');
                    if (statusText) statusText.classList.add('d-none');

                    clearTimeout(typingTimeout);
                    typingTimeout = setTimeout(() => {
                        typingBox.classList.add('d-none');
                        if (statusText) statusText.classList.remove('d-none');
                    }, 2500);
                }
            }

            // Typing whisper
            const input = document.getElementById('message-body-input');
            let lastWhisperTime = 0;
            if (input) {
                input.addEventListener('input', function() {
                    const now = Date.now();
                    if (now - lastWhisperTime > 1200) {
                        presenceChannel.whisper('typing', {
                            id: CURRENT_USER_ID,
                            name: CURRENT_USER_NAME
                        });
                        lastWhisperTime = now;
                    }
                });
            }
        }

        // =============================================================
        // WEBRTC CALLING CORE FUNCTIONS
        // =============================================================
        async function startCall(video) {
            if (!otherUserId) {
                alert('Calls are currently supported in 1-on-1 chats.');
                return;
            }

            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Camera & Microphone access requires HTTPS or localhost.\n\nTo test on LAN (e.g. 192.168.x.x):\n1. Open chrome://flags/#unsafely-treat-insecure-origin-as-secure in Chrome/Edge\n2. Add "http://' + window.location.host + '" and enable it\n3. Relaunch your browser.');
                return;
            }

            isVideoCall = video;
            isCallInitiator = true;

            try {
                // Get User Media
                localStream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                    video: isVideoCall ? { width: 1280, height: 720 } : false
                });

                showActiveCallUI(true);

                // Send Call Invitation
                presenceChannel.whisper('call-invitation', {
                    callerId: CURRENT_USER_ID,
                    callerName: CURRENT_USER_NAME,
                    callerAvatar: CURRENT_USER_AVATAR,
                    targetId: otherUserId,
                    isVideo: isVideoCall
                });

                if (activeCallStatus) activeCallStatus.textContent = `Ringing ${otherUserName}...`;

            } catch (err) {
                console.error('Media access error:', err);
                alert('Could not access microphone/camera. Please grant permissions.');
            }
        }

        function handleIncomingCall(data) {
            pendingIncomingSignal = data;
            const callerNameEl = document.getElementById('incoming-caller-name');
            const callerAvatarEl = document.getElementById('incoming-caller-avatar');
            const callTypeEl = document.getElementById('incoming-call-type');

            if (callerNameEl) callerNameEl.textContent = data.callerName;
            if (callerAvatarEl) callerAvatarEl.src = data.callerAvatar;
            if (callTypeEl) callTypeEl.textContent = data.isVideo ? 'Incoming Video Call...' : 'Incoming Voice Call...';

            if (typeof window.playNotificationSound === 'function') {
                window.playNotificationSound();
            }

            if (incomingCallModal) incomingCallModal.show();
        }

        if (btnAcceptCall) {
            btnAcceptCall.addEventListener('click', async function() {
                if (!pendingIncomingSignal) return;
                if (incomingCallModal) incomingCallModal.hide();

                const data = pendingIncomingSignal;
                isVideoCall = data.isVideo;
                isCallInitiator = false;

                try {
                    localStream = await navigator.mediaDevices.getUserMedia({
                        audio: true,
                        video: isVideoCall ? { width: 1280, height: 720 } : false
                    });

                    showActiveCallUI(false);
                    setupPeerConnection();

                    presenceChannel.whisper('call-accepted', {
                        calleeId: CURRENT_USER_ID,
                        targetId: data.callerId
                    });

                } catch (err) {
                    console.error('Failed to get media for incoming call:', err);
                    alert('Camera/Mic permission denied.');
                    presenceChannel.whisper('call-rejected', {
                        calleeId: CURRENT_USER_ID,
                        targetId: data.callerId,
                        reason: 'Device permission denied'
                    });
                }
            });
        }

        if (btnRejectCall) {
            btnRejectCall.addEventListener('click', function() {
                if (incomingCallModal) incomingCallModal.hide();
                if (pendingIncomingSignal) {
                    presenceChannel.whisper('call-rejected', {
                        calleeId: CURRENT_USER_ID,
                        targetId: pendingIncomingSignal.callerId,
                        reason: 'Call declined'
                    });
                    pendingIncomingSignal = null;
                }
            });
        }

        async function handleCallAccepted() {
            if (activeCallStatus) activeCallStatus.textContent = 'Connecting peer stream...';
            setupPeerConnection();

            // Create and send WebRTC Offer
            const offer = await peerConnection.createOffer();
            await peerConnection.setLocalDescription(offer);

            presenceChannel.whisper('webrtc-offer', {
                offer: offer,
                callerId: CURRENT_USER_ID,
                targetId: otherUserId
            });
        }

        async function handleWebRtcOffer(offer) {
            if (!peerConnection) setupPeerConnection();

            await peerConnection.setRemoteDescription(new RTCSessionDescription(offer));
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);

            presenceChannel.whisper('webrtc-answer', {
                answer: answer,
                calleeId: CURRENT_USER_ID,
                targetId: otherUserId
            });
        }

        async function handleWebRtcAnswer(answer) {
            if (peerConnection) {
                await peerConnection.setRemoteDescription(new RTCSessionDescription(answer));
            }
        }

        function setupPeerConnection() {
            peerConnection = new RTCPeerConnection(rtcConfiguration);

            // Add local tracks to connection
            if (localStream) {
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });
            }

            // Handle ICE Candidates
            peerConnection.onicecandidate = (e) => {
                if (e.candidate) {
                    presenceChannel.whisper('webrtc-candidate', {
                        candidate: e.candidate,
                        userId: CURRENT_USER_ID,
                        targetId: otherUserId
                    });
                }
            };

            // Handle Incoming Remote Stream
            peerConnection.ontrack = (e) => {
                remoteStream = e.streams[0];
                if (remoteVideo) {
                    remoteVideo.srcObject = remoteStream;
                }
                onCallConnected();
            };

            peerConnection.onconnectionstatechange = () => {
                if (peerConnection.connectionState === 'disconnected' || peerConnection.connectionState === 'failed') {
                    endCall(false, 'Connection lost.');
                }
            };
        }

        function onCallConnected() {
            if (activeCallStatus) activeCallStatus.textContent = 'Connected';
            if (isVideoCall) {
                if (audioPlaceholder) audioPlaceholder.classList.add('d-none');
                if (remoteVideo) remoteVideo.classList.remove('d-none');
                if (localVideo) localVideo.classList.remove('d-none');
            } else {
                if (audioPlaceholder) audioPlaceholder.classList.remove('d-none');
                if (remoteVideo) remoteVideo.classList.add('d-none');
                if (localVideo) localVideo.classList.add('d-none');
            }

            startTimer();
        }

        function showActiveCallUI(isCalling) {
            if (activeCallOverlay) activeCallOverlay.classList.remove('d-none');
            if (activeCallPartnerName) activeCallPartnerName.textContent = otherUserName;
            if (activeCallAvatar) activeCallAvatar.src = otherUserAvatar;
            if (activeCallTitle) activeCallTitle.textContent = isVideoCall ? 'Video Call' : 'Voice Call';

            if (localVideo && localStream) {
                localVideo.srcObject = localStream;
                if (isVideoCall) localVideo.classList.remove('d-none');
            }
        }

        function startTimer() {
            clearInterval(callTimerInterval);
            callSeconds = 0;
            callTimerInterval = setInterval(() => {
                callSeconds++;
                const mins = String(Math.floor(callSeconds / 60)).padStart(2, '0');
                const secs = String(callSeconds % 60).padStart(2, '0');
                if (activeCallTimer) activeCallTimer.textContent = `${mins}:${secs}`;
            }, 1000);
        }

        function endCall(sendSignal = true, message = '') {
            clearInterval(callTimerInterval);

            if (sendSignal && otherUserId && presenceChannel) {
                presenceChannel.whisper('call-ended', {
                    userId: CURRENT_USER_ID,
                    targetId: otherUserId
                });
            }

            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
                localStream = null;
            }

            if (peerConnection) {
                peerConnection.close();
                peerConnection = null;
            }

            if (localVideo) localVideo.srcObject = null;
            if (remoteVideo) remoteVideo.srcObject = null;

            if (activeCallOverlay) activeCallOverlay.classList.add('d-none');
            if (activeCallTimer) activeCallTimer.textContent = '00:00';

            if (message && typeof window.showToast === 'function') {
                window.showToast(message, 'secondary');
            }
        }

        // Call Action Listeners
        if (btnStartAudioCall) btnStartAudioCall.addEventListener('click', () => startCall(false));
        if (btnStartVideoCall) btnStartVideoCall.addEventListener('click', () => startCall(true));
        if (btnEndCall) btnEndCall.addEventListener('click', () => endCall(true, 'Call ended'));

        // Toggle Microphone
        if (btnToggleMic) {
            btnToggleMic.addEventListener('click', function() {
                if (localStream) {
                    const audioTrack = localStream.getAudioTracks()[0];
                    if (audioTrack) {
                        audioTrack.enabled = !audioTrack.enabled;
                        btnToggleMic.classList.toggle('active-mute', !audioTrack.enabled);
                        const icon = document.getElementById('icon-mic');
                        if (icon) icon.className = audioTrack.enabled ? 'bi bi-mic-fill' : 'bi bi-mic-mute-fill';
                    }
                }
            });
        }

        // Toggle Camera
        if (btnToggleCam) {
            btnToggleCam.addEventListener('click', function() {
                if (localStream) {
                    const videoTrack = localStream.getVideoTracks()[0];
                    if (videoTrack) {
                        videoTrack.enabled = !videoTrack.enabled;
                        btnToggleCam.classList.toggle('active-mute', !videoTrack.enabled);
                        const icon = document.getElementById('icon-cam');
                        if (icon) icon.className = videoTrack.enabled ? 'bi bi-camera-video-fill' : 'bi bi-camera-video-off-fill';
                    }
                }
            });
        }

        // Screen Sharing
        if (btnShareScreen) {
            btnShareScreen.addEventListener('click', async function() {
                try {
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const screenTrack = screenStream.getVideoTracks()[0];

                    if (peerConnection) {
                        const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            sender.replaceTrack(screenTrack);
                        }
                    }

                    if (localVideo) localVideo.srcObject = screenStream;

                    screenTrack.onended = () => {
                        if (localStream) {
                            const originalVideoTrack = localStream.getVideoTracks()[0];
                            const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
                            if (sender && originalVideoTrack) {
                                sender.replaceTrack(originalVideoTrack);
                            }
                            if (localVideo) localVideo.srcObject = localStream;
                        }
                    };
                } catch (err) {
                    console.warn('Screen share canceled:', err);
                }
            });
        }

        // =============================================================
        // 2. EMOJI PICKER IMPLEMENTATION
        // =============================================================
        const emojiCategories = [
            {
                name: 'Popular & Smileys',
                emojis: ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','😉','😍','🥰','😘','😋','😛','😜','🤪','😎','🤩','🥳','🤗','🤔','🤫','🤭','😴','😷','🤒','🤕','🤯','🤠','😈','💀','💩','👻','👽','🤖']
            },
            {
                name: 'Gestures & People',
                emojis: ['👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','✋','🤚','🖐️','🖖','👋','🤝','👏','🙌','👐','🤲','🙏','✍️','💪','🦾','💅','🤳']
            },
            {
                name: 'Hearts & Emotions',
                emojis: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💯','🔥','✨','🌟','⭐','💥','💫','💦','💨']
            },
            {
                name: 'Celebrations & Objects',
                emojis: ['🎉','🎊','🎈','🎂','🎁','🏆','🥇','🥈','🥉','🎯','🚀','✈️','💡','📱','💻','📷','☕','🍕','🍔','🍻','🥂','🌈','☀️','🌙','⚡','🍀','🌸','🌺']
            }
        ];

        const emojiToggleBtn = document.getElementById('btn-emoji-toggle');
        const emojiPopup = document.getElementById('emoji-picker-popup');
        const emojiCloseBtn = document.getElementById('btn-close-emoji');
        const emojiListContainer = document.getElementById('emoji-list-container');
        const emojiSearchInput = document.getElementById('emoji-search-input');
        const messageInput = document.getElementById('message-body-input');

        function renderEmojiList(filter = '') {
            if (!emojiListContainer) return;
            emojiListContainer.innerHTML = '';

            const lowerFilter = filter.toLowerCase().trim();

            emojiCategories.forEach(cat => {
                const filtered = lowerFilter 
                    ? cat.emojis.filter(e => e.includes(lowerFilter)) 
                    : cat.emojis;

                if (filtered.length > 0) {
                    const groupTitle = document.createElement('div');
                    groupTitle.className = 'small fw-bold text-muted mt-2 mb-1 px-1';
                    groupTitle.textContent = cat.name;
                    emojiListContainer.appendChild(groupTitle);

                    const grid = document.createElement('div');
                    grid.className = 'd-flex flex-wrap gap-1';

                    filtered.forEach(emoji => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'btn btn-sm btn-light border-0 p-1 fs-5';
                        btn.style.width = '36px';
                        btn.style.height = '36px';
                        btn.style.lineHeight = '1';
                        btn.textContent = emoji;

                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            insertEmojiAtCursor(emoji);
                        });

                        grid.appendChild(btn);
                    });

                    emojiListContainer.appendChild(grid);
                }
            });

            if (emojiListContainer.children.length === 0) {
                emojiListContainer.innerHTML = '<div class="text-center text-muted small py-3">No matching emojis</div>';
            }
        }

        function insertEmojiAtCursor(emoji) {
            if (!messageInput) return;
            const start = messageInput.selectionStart || messageInput.value.length;
            const end = messageInput.selectionEnd || messageInput.value.length;
            const text = messageInput.value;

            messageInput.value = text.substring(0, start) + emoji + text.substring(end);
            messageInput.selectionStart = messageInput.selectionEnd = start + emoji.length;
            messageInput.focus();

            messageInput.dispatchEvent(new Event('input'));
        }

        if (emojiToggleBtn && emojiPopup) {
            renderEmojiList();

            emojiToggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                emojiPopup.classList.toggle('d-none');
                if (!emojiPopup.classList.contains('d-none') && emojiSearchInput) {
                    emojiSearchInput.focus();
                }
            });

            if (emojiCloseBtn) {
                emojiCloseBtn.addEventListener('click', function() {
                    emojiPopup.classList.add('d-none');
                });
            }

            if (emojiSearchInput) {
                emojiSearchInput.addEventListener('input', function() {
                    renderEmojiList(this.value);
                });
            }

            document.addEventListener('click', function(e) {
                if (!emojiPopup.contains(e.target) && e.target !== emojiToggleBtn && !emojiToggleBtn.contains(e.target)) {
                    emojiPopup.classList.add('d-none');
                }
            });
        }

        // Automatic Emoticon Replacement
        const emoticonShortcuts = {
            ':)': '😊',
            ':-)': '😊',
            ':D': '😃',
            ':-D': '😃',
            ';)': '😉',
            ';-)': '😉',
            ':P': '😛',
            ':-P': '😛',
            '<3': '❤️',
            ':heart:': '❤️',
            ':fire:': '🔥',
            ':thumb:': '👍',
            ':thumbsup:': '👍',
            ':rocket:': '🚀',
            ':clap:': '👏',
            ':party:': '🎉'
        };

        if (messageInput) {
            messageInput.addEventListener('keyup', function(e) {
                if (e.key === ' ' || e.key === 'Enter') {
                    let val = messageInput.value;
                    let modified = false;

                    for (const [shortcut, emoji] of Object.entries(emoticonShortcuts)) {
                        if (val.includes(shortcut + ' ')) {
                            val = val.replaceAll(shortcut + ' ', emoji + ' ');
                            modified = true;
                        }
                    }

                    if (modified) {
                        messageInput.value = val;
                    }
                }
            });
        }

        // -------------------------------------------------------------
        // 3. Attachment Selection & Preview Management
        // -------------------------------------------------------------
        const attachmentInput = document.getElementById('attachment-input');
        const attachmentPreviewBar = document.getElementById('attachment-preview-bar');
        const attachmentFilename = document.getElementById('attachment-filename');
        const attachmentFilesize = document.getElementById('attachment-filesize');
        const btnRemoveAttachment = document.getElementById('btn-remove-attachment');

        if (attachmentInput) {
            attachmentInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    if (file.size > 10 * 1024 * 1024) {
                        alert('File exceeds the 10MB limit.');
                        this.value = '';
                        return;
                    }
                    if (attachmentFilename) attachmentFilename.textContent = file.name;
                    if (attachmentFilesize) attachmentFilesize.textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    if (attachmentPreviewBar) {
                        attachmentPreviewBar.classList.remove('d-none');
                        attachmentPreviewBar.classList.add('d-flex');
                    }
                }
            });
        }

        if (btnRemoveAttachment && attachmentInput) {
            btnRemoveAttachment.addEventListener('click', function() {
                attachmentInput.value = '';
                if (attachmentPreviewBar) {
                    attachmentPreviewBar.classList.add('d-none');
                    attachmentPreviewBar.classList.remove('d-flex');
                }
            });
        }

        // -------------------------------------------------------------
        // 4. Form Submit Handler (AJAX + FormData with fallback)
        // -------------------------------------------------------------
        const form = document.getElementById('chat-message-form');
        const input = document.getElementById('message-body-input');
        const sendBtn = document.getElementById('btn-send-message');
        const sendIcon = document.getElementById('send-icon');
        const sendSpinner = document.getElementById('send-spinner');

        if (form) {
            form.addEventListener('submit', function(e) {
                const body = input.value.trim();
                const hasFile = attachmentInput && attachmentInput.files && attachmentInput.files.length > 0;

                if (!body && !hasFile) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();

                if (emojiPopup) emojiPopup.classList.add('d-none');

                sendBtn.disabled = true;
                if (sendIcon) sendIcon.classList.add('d-none');
                if (sendSpinner) sendSpinner.classList.remove('d-none');

                const formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Failed to send message.');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.message) {
                        appendMessageBubble(data.message, true);
                        updateSidebarConversation(data.message);
                        input.value = '';
                        if (attachmentInput) attachmentInput.value = '';
                        if (attachmentPreviewBar) {
                            attachmentPreviewBar.classList.add('d-none');
                            attachmentPreviewBar.classList.remove('d-flex');
                        }
                    }
                })
                .catch(err => {
                    console.error('AJAX send error:', err);
                    if (typeof window.showToast === 'function') {
                        window.showToast('Could not send message. Please verify Reverb/Network.', 'danger');
                    }
                })
                .finally(() => {
                    sendBtn.disabled = false;
                    if (sendIcon) sendIcon.classList.remove('d-none');
                    if (sendSpinner) sendSpinner.classList.add('d-none');
                    input.focus();
                });
            });
        }
    @endif
});
</script>
@endpush
@endsection
