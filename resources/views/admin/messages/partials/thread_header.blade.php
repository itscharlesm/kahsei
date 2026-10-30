<div class="messages-thread-header">
    <a href="{{ url('messages') }}" class="messages-mobile-back d-md-none" aria-label="Back to messages">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="messages-thread-avatar-wrap">
        <div class="messages-thread-avatar elevation-1">
            @if (!empty($chatContext->avatar_url))
                <img src="{{ $chatContext->avatar_url }}" alt="{{ $chatContext->title }}">
            @else
                <i class="{{ $chatContext->avatar_icon }}"></i>
            @endif
        </div>

        @if ($chatContext->can_update_avatar)
            <button type="button" class="btn btn-primary messages-avatar-edit-button" data-toggle="modal"
                data-target="#groupAvatarModal" aria-label="Change group avatar" title="Change group avatar">
                <i class="fas fa-camera"></i>
            </button>
        @endif
    </div>

    <div class="messages-thread-context flex-grow-1">
        <div class="messages-thread-title-row">
            <p class="messages-thread-title text-truncate mb-0">{{ $chatContext->title }}</p>
            @if ($chatContext->can_rename)
                <button type="button" class="btn btn-link messages-thread-title-action" data-toggle="modal"
                    data-target="#renameGroupModal" aria-label="Rename group chat" title="Rename group chat">
                    <i class="fas fa-pen"></i>
                </button>
            @endif
        </div>
        <span class="messages-thread-subtitle text-truncate">{{ $chatContext->subtitle }}</span>
    </div>
</div>

@if ($chatContext->can_update_avatar)
    <div class="modal fade" id="groupAvatarModal" tabindex="-1" role="dialog" aria-labelledby="groupAvatarModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="groupAvatarModalLabel">
                        <i class="fas fa-camera mr-1"></i> Change Group Avatar
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="messages-group-avatar-preview mx-auto mb-3">
                        @if (!empty($chatContext->avatar_url))
                            <img src="{{ $chatContext->avatar_url }}" alt="{{ $chatContext->title }}">
                        @else
                            <i class="{{ $chatContext->avatar_icon }}"></i>
                        @endif
                    </div>

                    <form id="groupAvatarForm" method="POST"
                        action="{{ url('messages/chat/' . $mesg_group_id . '/avatar') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-0">
                            <label for="groupAvatarInput" class="messages-compose-label">
                                Avatar image <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="group_avatar" id="groupAvatarInput"
                                class="form-control-file {{ $errors->has('group_avatar') ? 'is-invalid' : '' }}"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                            <small class="form-text text-muted">JPG, PNG, or WEBP up to 8 MB.</small>
                            @if ($errors->has('group_avatar'))
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $errors->first('group_avatar') }}</strong>
                                </span>
                            @endif
                        </div>
                    </form>

                    @if ($chatContext->has_group_avatar)
                        <form id="removeGroupAvatarForm" method="POST"
                            action="{{ url('messages/chat/' . $mesg_group_id . '/avatar/remove') }}">
                            @csrf
                        </form>
                    @endif
                </div>

                <div class="modal-footer">
                    @if ($chatContext->has_group_avatar)
                        <button type="submit" form="removeGroupAvatarForm" class="btn btn-outline-danger mr-auto"
                            id="removeGroupAvatarButton">
                            <i class="fas fa-trash-alt mr-1"></i> Remove
                        </button>
                    @endif
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" form="groupAvatarForm" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if ($chatContext->can_rename)
    <div class="modal fade" id="renameGroupModal" tabindex="-1" role="dialog" aria-labelledby="renameGroupModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ url('messages/chat/' . $mesg_group_id . '/rename') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="renameGroupModalLabel">
                            <i class="fas fa-pen mr-1"></i> Rename Group Chat
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label for="renameGroupName" class="messages-compose-label">
                                Group name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="mesg_group_name" id="renameGroupName"
                                class="form-control messages-group-name-input {{ $errors->has('mesg_group_name') ? 'is-invalid' : '' }}"
                                value="{{ old('mesg_group_name', $chatContext->group_name ?: $chatContext->title) }}"
                                maxlength="100" required>
                            @if ($errors->has('mesg_group_name'))
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $errors->first('mesg_group_name') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
