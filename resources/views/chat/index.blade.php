@extends('layouts.app')

@section('title', 'ChatApp - Real-Time Messenger')

@section('content')
<div class="container-fluid px-2 px-md-3">
    <div class="chat-container">
        <div class="card chat-card border-0">
            <div class="row g-0 h-100">

                <!-- ============================================== -->
                <!-- 1. LEFT COLUMN: Conversations & Contacts List   -->
                <!-- ============================================== -->
                <div class="col-12 col-md-4 col-lg-3 chat-sidebar" id="chat-sidebar">
                    <!-- Search & New Chat Controls -->
                    <div class="chat-sidebar-header">
                        <div class="d-flex gap-2 mb-2">
                            <button class="btn btn-primary btn-sm flex-fill d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                <i class="bi bi-chat-plus"></i>
                                <span>New Chat</span>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#newGroupModal" title="Create Group">
                                <i class="bi bi-people"></i>
                                <span>Group</span>
                            </button>
                        </div>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="search-input" placeholder="Search conversations...">
                        </div>
                    </div>

                    <!-- Scrollable List of Chats -->
                    <div class="chat-list" id="conversation-list">
                        <div class="text-center py-5 text-muted small">
                            <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                            <div>Loading conversations...</div>
                        </div>
                    </div>
                </div>

                <!-- ============================================== -->
                <!-- 2. RIGHT COLUMN: Active Chat Messages & Input  -->
                <!-- ============================================== -->
                <div class="col-12 col-md-8 col-lg-9 chat-main-area d-none d-md-flex" id="chat-main-area">

                    <!-- Empty State (When no chat is selected) -->
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-center p-4 text-muted" id="empty-state">
                        <i class="bi bi-chat-dots display-3 text-secondary mb-3 opacity-50"></i>
                        <h5 class="fw-bold text-dark">No Conversation Selected</h5>
                        <p class="small text-muted mb-3" style="max-width: 360px;">
                            Choose a conversation from the sidebar or click <strong>New Chat</strong> to message your contacts in real time.
                        </p>
                        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#newChatModal">
                            <i class="bi bi-person-plus me-1"></i> Start New Chat
                        </button>
                    </div>

                    <!-- Active Conversation Box (Hidden by default until a chat is clicked) -->
                    <div class="d-flex flex-column h-100 d-none" id="active-chat-box">

                        <!-- Header: User/Group info -->
                        <div class="chat-header">
                            <div class="d-flex align-items-center gap-3">
                                <!-- Mobile Back Button -->
                                <button class="btn btn-outline-secondary btn-sm d-md-none" id="btn-back-to-list" title="Back to conversations">
                                    <i class="bi bi-arrow-left"></i>
                                </button>
                                <div class="avatar-wrap">
                                    <img src="" alt="" class="avatar-img" id="active-avatar">
                                    <span class="status-dot" id="active-status-dot"></span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold" id="active-name">Chat Name</h6>
                                    <small class="text-muted" id="active-status">Offline</small>
                                </div>
                            </div>

                            <!-- Header Actions -->
                            <div>
                                <button class="btn btn-outline-danger btn-sm" id="btn-clear-chat" title="Clear chat history">
                                    <i class="bi bi-trash"></i> <span class="d-none d-sm-inline">Clear</span>
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable Message Stream -->
                        <div class="chat-messages" id="messages-container">
                            <!-- Messages will be dynamically rendered here -->
                        </div>

                        <!-- Chat Input Box -->
                        <div class="chat-footer">
                            <!-- File preview before sending (if attached) -->
                            <div class="mb-2 d-none" id="attachment-preview-bar">
                                <span class="badge bg-secondary p-2 d-inline-flex align-items-center gap-2" id="attachment-preview-badge">
                                    <i class="bi bi-paperclip"></i>
                                    <span id="attachment-name">file.jpg</span>
                                    <button type="button" class="btn-close btn-close-white btn-sm" id="btn-remove-attachment" aria-label="Remove"></button>
                                </span>
                            </div>

                            <form id="message-form" class="m-0" enctype="multipart/form-data">
                                <input type="file" id="file-input" name="attachments[]" class="d-none" accept="image/*,.pdf,.doc,.docx,.txt,.zip">
                                
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-attach" title="Attach image or document">
                                        <i class="bi bi-paperclip"></i>
                                    </button>
                                    <input type="text" class="form-control" id="message-text" placeholder="Type a message..." autocomplete="off">
                                    <button type="submit" class="btn btn-primary px-3" id="btn-send">
                                        <i class="bi bi-send-fill" id="send-icon"></i>
                                        <span class="spinner-border spinner-border-sm d-none" id="send-spinner" role="status"></span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- 3. MODALS: New Direct Chat & New Group         -->
<!-- ============================================== -->

<!-- Modal: Start New 1-on-1 Chat -->
<div class="modal fade" id="newChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Start New Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="search-users-input" placeholder="Search user by name or email...">
                </div>
                <div class="list-group list-group-flush border rounded" id="users-modal-list" style="max-height: 300px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted small">Loading users...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create New Group -->
<div class="modal fade" id="newGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-people text-primary me-2"></i> Create Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="create-group-form">
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <label for="group-name-input" class="form-label small fw-semibold">Group Name</label>
                        <input type="text" class="form-control" id="group-name-input" name="name" required placeholder="e.g. Project Team, Family">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Select Members</label>
                        <div class="border rounded p-2 bg-light" id="group-members-list" style="max-height: 200px; overflow-y: auto;">
                            <div class="text-center py-3 text-muted small">Loading users...</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold" id="btn-submit-group">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Current Authenticated User Data
    const CURRENT_USER_ID = parseInt($('meta[name="user-id"]').attr('content'));
    const CURRENT_USER_NAME = $('meta[name="user-name"]').attr('content');

    // App State Variables
    let activeConversationId = null;
    let activeConversationData = null;
    let activeChannel = null;
    let conversationsList = [];
    let onlineUsers = new Set();
    let attachedFile = null;

    // Configure CSRF token for all jQuery AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // -------------------------------------------------------------
    // 1. Initialize Real-Time Channels & Load Data
    // -------------------------------------------------------------
    loadConversations();
    setupPresenceChannel();
    setupUserChannel();

    // -------------------------------------------------------------
    // 2. Real-Time Setup (Laravel Echo / Reverb)
    // -------------------------------------------------------------
    function setupPresenceChannel() {
        if (!window.Echo) return;

        window.Echo.join('chat.presence')
            .here((users) => {
                users.forEach(u => onlineUsers.add(u.id));
                updateOnlineStatusIndicators();
            })
            .joining((user) => {
                onlineUsers.add(user.id);
                updateOnlineStatusIndicators();
            })
            .leaving((user) => {
                onlineUsers.delete(user.id);
                updateOnlineStatusIndicators();
            });
    }

    function setupUserChannel() {
        if (!window.Echo) return;

        // Listen for new conversations or updates targeted at this user
        window.Echo.private(`user.${CURRENT_USER_ID}`)
            .listen('.conversation.updated', (e) => {
                loadConversations();
                if (e.conversation && e.conversation.id !== activeConversationId) {
                    window.playNotificationSound();
                    window.showToast(`New message in ${e.conversation.name}`, 'info');
                }
            });
    }

    function updateOnlineStatusIndicators() {
        // Update sidebar items
        $('.chat-list-item').each(function() {
            const otherId = parseInt($(this).data('other-user-id'));
            if (otherId) {
                const isOnline = onlineUsers.has(otherId);
                $(this).find('.status-dot').toggleClass('online', isOnline);
            }
        });

        // Update active chat header
        if (activeConversationData && activeConversationData.type === 'direct' && activeConversationData.other_user) {
            const isOnline = onlineUsers.has(parseInt(activeConversationData.other_user.id));
            $('#active-status-dot').toggleClass('online', isOnline);
            $('#active-status').text(isOnline ? 'Online' : 'Offline');
        }
    }

    // -------------------------------------------------------------
    // 3. Load & Render Conversations List
    // -------------------------------------------------------------
    function loadConversations() {
        $.get('/api/conversations', function(res) {
            if (res.success) {
                conversationsList = res.conversations;
                renderConversations(conversationsList);
            }
        });
    }

    function renderConversations(list) {
        const $container = $('#conversation-list');
        $container.empty();

        if (list.length === 0) {
            $container.html(`
                <div class="text-center py-5 text-muted px-3">
                    <i class="bi bi-chat-square-text fs-3 text-secondary mb-2 d-block"></i>
                    <p class="small mb-2">No conversations yet.</p>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">Start a Chat</button>
                </div>
            `);
            return;
        }

        list.forEach(conv => {
            const isActive = conv.id === activeConversationId;
            const otherUserId = conv.other_user ? conv.other_user.id : null;
            const isOnline = otherUserId ? onlineUsers.has(parseInt(otherUserId)) : false;

            let snippet = 'No messages yet';
            let time = '';
            if (conv.latest_message) {
                snippet = (conv.latest_message.sender_name === 'You' ? 'You: ' : '') + 
                          (conv.latest_message.body || (conv.latest_message.has_attachments ? '📎 Attachment' : ''));
                time = conv.latest_message.formatted_time || '';
            }

            const unreadHtml = conv.unread_count > 0 
                ? `<span class="badge bg-primary rounded-pill">${conv.unread_count}</span>` 
                : '';

            const item = `
                <div class="chat-list-item ${isActive ? 'active' : ''}" data-id="${conv.id}" data-other-user-id="${otherUserId || ''}">
                    <div class="avatar-wrap me-3">
                        <img src="${conv.avatar_url}" alt="${escapeHtml(conv.name)}" class="avatar-img">
                        ${conv.type === 'direct' ? `<span class="status-dot ${isOnline ? 'online' : ''}"></span>` : ''}
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-truncate" style="font-size: 0.9rem;">${escapeHtml(conv.name)}</strong>
                            <small class="text-muted" style="font-size: 0.72rem;">${time}</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-truncate small ${conv.unread_count > 0 ? 'fw-bold text-dark' : 'text-muted'}" style="font-size: 0.82rem;">${escapeHtml(snippet)}</span>
                            ${unreadHtml}
                        </div>
                    </div>
                </div>
            `;
            $container.append(item);
        });
    }

    // Filter conversations on typing in search box
    $('#search-input').on('input', function() {
        const q = $(this).val().toLowerCase().trim();
        if (!q) {
            renderConversations(conversationsList);
            return;
        }
        const filtered = conversationsList.filter(c => {
            return (c.name && c.name.toLowerCase().includes(q)) ||
                   (c.latest_message && c.latest_message.body && c.latest_message.body.toLowerCase().includes(q));
        });
        renderConversations(filtered);
    });

    // -------------------------------------------------------------
    // 4. Open and Switch Active Conversation
    // -------------------------------------------------------------
    $(document).on('click', '.chat-list-item', function() {
        const convId = parseInt($(this).data('id'));
        openConversation(convId);
    });

    function openConversation(convId) {
        if (activeConversationId === convId) return;

        // Leave previous WebSocket room channel
        if (activeChannel && window.Echo) {
            window.Echo.leave(`chat.conversation.${activeConversationId}`);
        }

        activeConversationId = convId;
        removeAttachment();

        // Update active CSS in sidebar
        $('.chat-list-item').removeClass('active');
        $(`.chat-list-item[data-id="${convId}"]`).addClass('active').find('.badge').remove();

        // Toggle UI panels (Desktop & Mobile)
        $('#empty-state').addClass('d-none');
        $('#active-chat-box').removeClass('d-none');
        $('#chat-sidebar').addClass('d-mobile-none');
        $('#chat-main-area').removeClass('d-none d-mobile-none').addClass('d-flex');

        // Fetch conversation information
        $.get(`/api/conversations/${convId}`, function(res) {
            if (res.success) {
                activeConversationData = res.conversation;
                renderActiveChatHeader(res.conversation);
            }
        });

        // Load conversation messages
        loadMessages(convId);

        // Mark messages as read
        $.post(`/api/conversations/${convId}/read`);

        // Subscribe to real-time messages on this channel
        subscribeToRoom(convId);
    }

    function renderActiveChatHeader(conv) {
        $('#active-avatar').attr('src', conv.avatar_url);
        $('#active-name').text(conv.name);

        if (conv.type === 'direct' && conv.other_user) {
            const isOnline = onlineUsers.has(parseInt(conv.other_user.id));
            $('#active-status-dot').removeClass('d-none').toggleClass('online', isOnline);
            $('#active-status').text(isOnline ? 'Online' : 'Offline');
        } else {
            $('#active-status-dot').addClass('d-none');
            $('#active-status').text(`${conv.participants.length} members`);
        }
    }

    function subscribeToRoom(convId) {
        if (!window.Echo) return;

        activeChannel = window.Echo.private(`chat.conversation.${convId}`);

        // Listen for new messages sent in this conversation
        activeChannel.listen('.message.sent', (e) => {
            appendMessage(e);
            scrollToBottom();
            $.post(`/api/conversations/${convId}/read`);

            if (e.sender_id !== CURRENT_USER_ID) {
                window.playNotificationSound();
            }
        });
    }

    // -------------------------------------------------------------
    // 5. Load, Render & Send Messages
    // -------------------------------------------------------------
    function loadMessages(convId) {
        $('#messages-container').html(`
            <div class="text-center py-5 text-muted small">
                <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
                <div>Loading messages...</div>
            </div>
        `);

        $.get(`/api/conversations/${convId}/messages`, function(res) {
            if (res.success) {
                $('#messages-container').empty();
                if (res.messages.length === 0) {
                    $('#messages-container').html(`
                        <div class="text-center py-5 text-muted small">
                            <i class="bi bi-chat-text fs-3 mb-2 d-block text-secondary"></i>
                            No messages yet. Send a message below to start the conversation!
                        </div>
                    `);
                    return;
                }

                res.messages.forEach(msg => appendMessage(msg));
                scrollToBottom();
            }
        });
    }

    function appendMessage(msg) {
        // Clear empty placeholder if present
        if ($('#messages-container .spinner-border, #messages-container .bi-chat-text').length) {
            $('#messages-container').empty();
        }

        const isSender = (msg.is_sender) || (msg.sender_id === CURRENT_USER_ID);
        const senderName = isSender ? 'You' : (msg.sender ? msg.sender.name : 'User');

        // Render attachments if any
        let attachmentsHtml = '';
        if (msg.attachments && msg.attachments.length > 0) {
            msg.attachments.forEach(att => {
                if (att.is_image) {
                    attachmentsHtml += `
                        <div>
                            <a href="${att.url}" target="_blank">
                                <img src="${att.url}" class="attachment-img" alt="${escapeHtml(att.original_name)}">
                            </a>
                        </div>
                    `;
                } else {
                    attachmentsHtml += `
                        <a href="${att.url}" download="${att.original_name}" class="attachment-card">
                            <i class="bi bi-file-earmark-arrow-down fs-5"></i>
                            <span class="text-truncate">${escapeHtml(att.original_name)}</span>
                        </a>
                    `;
                }
            });
        }

        // Check icon
        let checkHtml = '';
        if (isSender) {
            checkHtml = msg.status === 'read' 
                ? `<i class="bi bi-check-all text-info ms-1"></i>` 
                : `<i class="bi bi-check ms-1"></i>`;
        }

        const messageHtml = `
            <div class="message-row ${isSender ? 'outgoing' : 'incoming'}">
                <div class="message-bubble">
                    ${!isSender && activeConversationData && activeConversationData.type === 'group' ? `<small class="fw-bold d-block text-primary mb-1">${escapeHtml(senderName)}</small>` : ''}
                    ${attachmentsHtml}
                    ${msg.body ? `<div>${escapeHtml(msg.body)}</div>` : ''}
                    <div class="message-time">
                        <span>${msg.formatted_time || ''}</span>
                        ${checkHtml}
                    </div>
                </div>
            </div>
        `;

        $('#messages-container').append(messageHtml);
    }

    function scrollToBottom() {
        const el = document.getElementById('messages-container');
        if (el) el.scrollTop = el.scrollHeight;
    }

    // Submit Message Form
    $('#message-form').on('submit', function(e) {
        e.preventDefault();
        if (!activeConversationId) return;

        const body = $('#message-text').val().trim();
        if (!body && !attachedFile) return;

        const formData = new FormData();
        if (body) formData.append('body', body);
        if (attachedFile) formData.append('attachments[]', attachedFile);

        // Button loading state
        $('#btn-send').prop('disabled', true);
        $('#send-icon').addClass('d-none');
        $('#send-spinner').removeClass('d-none');

        $.ajax({
            url: `/api/conversations/${activeConversationId}/messages`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    appendMessage(res.message);
                    scrollToBottom();
                    $('#message-text').val('');
                    removeAttachment();
                    loadConversations();
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to send message.';
                window.showToast(msg, 'danger');
            },
            complete: function() {
                $('#btn-send').prop('disabled', false);
                $('#send-icon').removeClass('d-none');
                $('#send-spinner').addClass('d-none');
                $('#message-text').focus();
            }
        });
    });

    // File Attachment handlers
    $('#btn-attach').on('click', function() {
        $('#file-input').click();
    });

    $('#file-input').on('change', function() {
        if (this.files && this.files[0]) {
            attachedFile = this.files[0];
            $('#attachment-name').text(attachedFile.name);
            $('#attachment-preview-bar').removeClass('d-none');
        }
        $(this).val('');
    });

    $('#btn-remove-attachment').on('click', function() {
        removeAttachment();
    });

    function removeAttachment() {
        attachedFile = null;
        $('#attachment-preview-bar').addClass('d-none');
    }

    // Clear Chat History
    $('#btn-clear-chat').on('click', function() {
        if (!activeConversationId) return;
        if (confirm('Clear chat history for this conversation?')) {
            $.post(`/api/conversations/${activeConversationId}/clear`, function(res) {
                if (res.success) {
                    $('#messages-container').empty();
                    window.showToast('Chat history cleared', 'info');
                    loadConversations();
                }
            });
        }
    });

    // Mobile Back Button
    $('#btn-back-to-list').on('click', function() {
        $('#chat-sidebar').removeClass('d-mobile-none');
        $('#chat-main-area').addClass('d-mobile-none').removeClass('d-flex');
    });

    // -------------------------------------------------------------
    // 6. Start New Chat Modal (User Search & Connect)
    // -------------------------------------------------------------
    $('#newChatModal').on('show.bs.modal', function() {
        fetchUsersForModal('');
    });

    $('#search-users-input').on('input', function() {
        fetchUsersForModal($(this).val().trim());
    });

    function fetchUsersForModal(q) {
        $.get('/api/users/search', { q: q }, function(res) {
            const $list = $('#users-modal-list');
            $list.empty();

            if (res.success && res.users.length > 0) {
                res.users.forEach(u => {
                    const isOnline = onlineUsers.has(u.id);
                    $list.append(`
                        <div class="list-group-item d-flex align-items-center justify-content-between p-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-wrap">
                                    <img src="${u.avatar_url}" class="avatar-img-sm">
                                    <span class="status-dot ${isOnline ? 'online' : ''}"></span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">${escapeHtml(u.name)}</h6>
                                    <small class="text-muted">${escapeHtml(u.email)}</small>
                                </div>
                            </div>
                            <button class="btn btn-outline-primary btn-sm rounded-pill btn-start-chat" data-user-id="${u.id}">
                                <i class="bi bi-chat"></i> Chat
                            </button>
                        </div>
                    `);
                });
            } else {
                $list.html('<div class="text-center py-3 text-muted small">No users found.</div>');
            }
        });
    }

    $(document).on('click', '.btn-start-chat', function() {
        const targetUserId = parseInt($(this).data('user-id'));
        bootstrap.Modal.getInstance(document.getElementById('newChatModal')).hide();

        $.post('/api/conversations/direct', { user_id: targetUserId }, function(res) {
            if (res.success) {
                loadConversations();
                openConversation(res.conversation_id);
            }
        });
    });

    // -------------------------------------------------------------
    // 7. Create Group Modal
    // -------------------------------------------------------------
    $('#newGroupModal').on('show.bs.modal', function() {
        $.get('/api/users/search', function(res) {
            const $list = $('#group-members-list');
            $list.empty();

            if (res.success && res.users.length > 0) {
                res.users.forEach(u => {
                    $list.append(`
                        <div class="form-check d-flex align-items-center gap-2 py-1">
                            <input class="form-check-input" type="checkbox" name="members[]" value="${u.id}" id="grp-usr-${u.id}">
                            <label class="form-check-label d-flex align-items-center gap-2 cursor-pointer w-100" for="grp-usr-${u.id}">
                                <img src="${u.avatar_url}" class="avatar-img-sm" style="width: 26px; height: 26px;">
                                <span class="small fw-semibold">${escapeHtml(u.name)}</span>
                            </label>
                        </div>
                    `);
                });
            }
        });
    });

    $('#create-group-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        $('#btn-submit-group').prop('disabled', true).text('Creating...');

        $.ajax({
            url: '/api/conversations/group',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('newGroupModal')).hide();
                    $('#create-group-form')[0].reset();
                    window.showToast('Group created successfully!', 'success');
                    loadConversations();
                    openConversation(res.conversation_id);
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to create group.';
                window.showToast(msg, 'danger');
            },
            complete: function() {
                $('#btn-submit-group').prop('disabled', false).text('Create Group');
            }
        });
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
