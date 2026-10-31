@extends('admin.layouts.themes.main')

@section('page_styles')
    @include('admin.messages.partials.styles')
@endsection

@section('content')
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Messages</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ url('messages') }}">Messages</a>
                        </li>
                        <li class="breadcrumb-item active">Chat</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        @include('admin.layouts.partials.onclick')
        @include('admin.layouts.partials.alerts')

        <div class="container-fluid">
            <div class="row messages-shell messages-panel">
                <div class="col-md-4 col-lg-3 px-0 d-none d-md-block" id="msg-sidebar">
                    @include('admin.messages.partials.sidebar', [
                        'conversations' => $conversations,
                        'search' => $search,
                        'searchAction' => url('messages/chat/' . $mesg_group_id),
                        'searchHiddenInputs' => [],
                    ])
                </div>

                <div class="col-12 col-md-8 col-lg-9 px-0 d-flex flex-column messages-active-chat-panel" id="msg-chat-panel">
                    <div class="card mb-0 messages-thread-card messages-active-chat-card">
                        @include('admin.messages.partials.thread_header', ['chatContext' => $chatContext])

                        <div class="messages-thread-list" id="chatMessages">
                            @if ($messages->hasPages() && $messages->currentPage() > 1)
                                <div class="messages-thread-load">
                                    <a href="{{ $messages->previousPageUrl() }}&anchor=top" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-chevron-up me-1"></i> Load older messages
                                    </a>
                                </div>
                            @endif

                            @forelse ($messages as $msg)
                                @php
                                    $isMine = $msg->mes_created_by == session('usr_id');
                                    $isUnsent = (int) $msg->mes_active === 0;
                                    $messageImageUrl = !$isUnsent ? ($msg->mes_image_url ?? null) : null;
                                    $hasImage = !empty($messageImageUrl);
                                    $wasEdited = !$isUnsent && !empty($msg->mes_date_modified);
                                    $messageCreatedAt = \Carbon\Carbon::parse($msg->mes_date_created);
                                    $canModifyMessage = $isMine && !$isUnsent && $messageCreatedAt->copy()->addHour()->gte(\Carbon\Carbon::now());
                                @endphp

                                <div class="messages-thread-item {{ $isMine ? 'is-mine' : 'is-other' }}">
                                    @if (!$isMine)
                                        <div class="messages-message-avatar">
                                            <img src="{{ asset(getAvatar($msg->mes_created_by)) }}" alt="{{ $msg->sender_name }}">
                                        </div>
                                    @endif

                                    <div class="messages-thread-content">
                                        @if ($isGroup && !$isMine)
                                            <div class="messages-thread-sender">{{ $msg->usr_first_name }}</div>
                                        @endif

                                        @if ($isUnsent)
                                            <div class="messages-bubble messages-bubble-unsent">This message was unsent</div>
                                        @else
                                            <div class="messages-attachment-stack">
                                                @if ($hasImage)
                                                    <div class="messages-image-container js-message-image-container">
                                                        <a href="{{ $messageImageUrl }}" class="messages-image-link"
                                                            target="_blank" rel="noopener" aria-label="Open image message from {{ $msg->sender_name }}">
                                                            <img src="{{ $messageImageUrl }}" data-image-url="{{ $messageImageUrl }}"
                                                                class="messages-message-image js-message-image" loading="lazy" decoding="async"
                                                                alt="Image message from {{ $msg->sender_name }}">
                                                        </a>
                                                        <div class="messages-image-error" role="status" hidden>
                                                            <i class="fas fa-image" aria-hidden="true"></i>
                                                            <span>Image could not be loaded.</span>
                                                            <button type="button" class="btn btn-link js-message-image-retry">Retry</button>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if (trim((string) $msg->mes_content) !== '')
                                                    <div class="messages-bubble {{ $isMine ? 'messages-bubble-mine' : 'messages-bubble-other' }}">{{ $msg->mes_content }}</div>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="messages-thread-meta">
                                            <div class="messages-thread-timestamp">
                                                {{ \Carbon\Carbon::parse($msg->mes_date_created)->format('M j, g:i A') }}
                                                @if ($wasEdited)
                                                    <span class="messages-edited-label">Edited</span>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($canModifyMessage)
                                            <div class="messages-message-actions">
                                                <button type="button" class="btn btn-link messages-message-action js-message-edit-trigger"
                                                    data-message-id="{{ $msg->mes_id }}"
                                                    data-message-url="{{ url('messages/' . $msg->mes_id) }}"
                                                    data-message-content="{{ $msg->mes_content }}">
                                                    <i class="fas fa-pen"></i> Edit
                                                </button>
                                                <form method="POST" action="{{ url('messages/' . $msg->mes_id . '/unsend') }}"
                                                    class="d-inline js-message-unsend-form">
                                                    @csrf
                                                    <button type="submit" class="btn btn-link messages-message-action text-danger">
                                                        <i class="fas fa-ban"></i> Unsend
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($isMine)
                                        <div class="messages-message-avatar">
                                            <img src="{{ asset(getAvatar(session('usr_id'))) }}" alt="You">
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="messages-empty-state py-5">
                                    <i class="fas fa-comment-slash fa-2x mb-3 d-block"></i>
                                    <div class="fw-bold mb-1">No messages yet</div>
                                    <div>Say hello to start the conversation.</div>
                                </div>
                            @endforelse

                            @if ($messages->hasMorePages())
                                <div class="messages-thread-load mt-2">
                                    <a href="{{ $messages->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary">
                                        Load newer messages
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="messages-composer">
                            <div class="messages-image-preview" id="messageImagePreview" data-input="#messageImageInput" hidden>
                                <img src="" alt="Selected image preview" class="messages-image-preview-thumb">
                                <span class="messages-image-preview-name"></span>
                                <button type="button" class="btn btn-link messages-image-preview-remove js-message-image-remove"
                                    aria-label="Remove selected image">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <form method="POST" action="{{ url('messages/send') }}" enctype="multipart/form-data"
                                class="messages-composer-form" id="sendForm">
                                @csrf
                                <input type="hidden" name="mesg_group_id" value="{{ $mesg_group_id }}">

                                <input type="file" name="mes_image" id="messageImageInput" class="d-none js-message-image-input"
                                    data-preview="#messageImagePreview" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                                <label for="messageImageInput" class="btn btn-link messages-attachment-button flex-shrink-0" title="Attach image">
                                    <i class="fas fa-image"></i>
                                    <span class="visually-hidden">Attach image</span>
                                </label>

                                <textarea name="mes_content" id="messageInput" class="form-control messages-composer-input"
                                    placeholder="Type a message..." rows="1"></textarea>

                                <button type="submit" class="btn btn-primary messages-send-button flex-shrink-0" aria-label="Send message">
                                    <i class="fas fa-paper-plane"></i>
                                    <span class="visually-hidden">Send</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.messages.partials.compose_modal', ['users' => $users])

    <div class="modal fade" id="editMessageModal" tabindex="-1" aria-labelledby="editMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ url('messages/0') }}" id="editMessageForm">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editMessageModalLabel">
                            <i class="fas fa-pen me-1"></i> Edit Message
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <label for="editMessageContent" class="messages-compose-label">Message</label>
                        <div class="messages-compose-textarea-wrap">
                            <textarea name="mes_content" id="editMessageContent" class="form-control messages-compose-textarea"
                                rows="4" placeholder="Type your message..."></textarea>
                        </div>
                        <small class="form-text text-muted mt-2">Attached images cannot be changed.</small>

                        @if ($errors->getBag('editMessage')->has('mes_content'))
                            <div class="invalid-feedback d-block">
                                {{ $errors->getBag('editMessage')->first('mes_content') }}
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page_scripts')
    @include('admin.messages.partials.scripts')
@endsection