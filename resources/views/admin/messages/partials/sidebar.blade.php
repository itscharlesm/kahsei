<div class="card card-primary card-outline h-100 mb-0 messages-sidebar-card">
    <div class="messages-sidebar-header d-flex align-items-center justify-content-between">
        <div>
            <div class="fw-bold">Chats</div>
            <small class="text-muted">Find a conversation or start a new one.</small>
        </div>

        <a href="#" data-bs-toggle="modal" data-bs-target="#composeModal"
            class="btn btn-primary btn-sm messages-compose-trigger" title="New Message">
            <span class="messages-compose-trigger-icon" aria-hidden="true">
                <i class="fas fa-edit"></i>
            </span>
        </a>
    </div>

    <div class="messages-search-wrap">
        <form method="GET" action="{{ $searchAction }}">
            @if (!empty($searchHiddenInputs))
                @foreach ($searchHiddenInputs as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
            @endif

            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" name="search" class="form-control border-start-0"
                    placeholder="Search messages or people..." value="{{ $search }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>

    <div class="messages-list">
        @forelse ($conversations as $conversation)
            @include('admin.messages.partials.sidebar_item', ['conversation' => $conversation])
        @empty
            <div class="messages-empty-state">
                <i class="fas {{ !empty($search) ? 'fa-search' : 'fa-comments' }} fa-2x mb-3 d-block"></i>
                <div class="fw-bold mb-1">
                    {{ !empty($search) ? 'No matching conversations' : 'No conversations yet' }}
                </div>
                <div>
                    {{ !empty($search) ? 'Try a different name or keyword.' : 'Start a new message to begin chatting.' }}
                </div>
            </div>
        @endforelse
    </div>

    @if ($conversations instanceof \Illuminate\Pagination\LengthAwarePaginator && $conversations->hasPages())
        <div class="messages-pagination">
            {{ $conversations->onEachSide(1)->links('admin.messages.partials.pagination') }}
        </div>
    @endif
</div>