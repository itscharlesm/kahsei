<div class="modal fade" id="composeModal" tabindex="-1" role="dialog" aria-labelledby="composeModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ url('messages/compose') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="composeModalLabel">
                        <i class="fas fa-edit mr-1"></i> New Message
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="messageRecipients" class="messages-compose-label">
                            To <span class="text-danger">*</span>
                        </label>
                        <select name="recipients[]" id="messageRecipients" class="form-control js-message-recipients"
                            multiple="multiple" required>
                            @foreach ($users as $user)
                                <option value="{{ $user->usr_id }}">
                                    {{ trim($user->usr_first_name . ' ' . ($user->usr_middle_name ? $user->usr_middle_name . ' ' : '') . $user->usr_last_name) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted mt-2">
                            Select one or more recipients for a direct or group message.
                        </small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="composeMessageContent" class="messages-compose-label">
                            Message
                        </label>
                        <div class="messages-compose-textarea-wrap">
                            <textarea name="mes_content" id="composeMessageContent" class="form-control messages-compose-textarea"
                                rows="4" placeholder="Type your message..."></textarea>
                        </div>
                        <small class="form-text text-muted mt-2">Write a message, attach an image, or both.</small>
                    </div>

                    <div class="form-group mb-0 mt-3">
                        <label for="composeMessageImageInput" class="messages-compose-label">Image</label>
                        <input type="file" name="mes_image" id="composeMessageImageInput" class="d-none js-message-image-input"
                            data-preview="#composeMessageImagePreview" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp">
                        <label for="composeMessageImageInput" class="btn btn-outline-primary messages-compose-attachment-button mb-0">
                            <i class="fas fa-image mr-1"></i> Choose Image
                        </label>
                        <small class="form-text text-muted mt-2">JPG, PNG, or WebP up to 8 MB.</small>

                        <div class="messages-image-preview messages-compose-image-preview mt-3" id="composeMessageImagePreview"
                            data-input="#composeMessageImageInput" hidden>
                            <img src="" alt="Selected image preview" class="messages-image-preview-thumb">
                            <span class="messages-image-preview-name"></span>
                            <button type="button" class="btn btn-link messages-image-preview-remove js-message-image-remove"
                                aria-label="Remove selected image">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
