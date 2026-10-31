<a href="{{ $conversation->link }}" class="messages-list-item {{ $conversation->is_active ? 'is-active' : '' }}">
    <div class="messages-avatar shadow-sm">
        @if (!empty($conversation->avatar_url))
            <img src="{{ $conversation->avatar_url }}" alt="{{ $conversation->title }}">
        @else
            <i class="{{ $conversation->avatar_icon }}"></i>
        @endif
    </div>

    <div class="messages-conversation-body flex-grow-1">
        <div class="messages-conversation-row">
            <p class="messages-conversation-title text-truncate mb-0">{{ $conversation->title }}</p>
            <span class="messages-conversation-time">{{ $conversation->last_message_label }}</span>
        </div>
        <p class="messages-conversation-meta text-truncate mb-0">{{ $conversation->preview }}</p>
    </div>
</a>