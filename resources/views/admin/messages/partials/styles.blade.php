<style>
    .app-main {
        --messages-workspace-height: calc(100vh - (3.5rem + 1px) - (3.5rem + 1px));
        display: flex;
        flex-direction: column;
        height: var(--messages-workspace-height);
        min-height: 0 !important;
        overflow: hidden;
        padding-bottom: 0;
    }

    @supports (height: 100dvh) {
        .app-main {
            --messages-workspace-height: calc(100dvh - (3.5rem + 1px) - (3.5rem + 1px));
        }
    }

    .app-main>.app-content-header {
        flex: 0 0 auto;
    }

    .app-main>.app-content {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
    }

    .app-main>.app-content>.container-fluid {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
    }

    .wrapper .content-wrapper {
        --messages-workspace-height: calc(100vh - (3.5rem + 1px) - (3.5rem + 1px));
        display: flex;
        flex-direction: column;
        height: var(--messages-workspace-height);
        /* AdminLTE injects an inline min-height; the message workspace owns its viewport height. */
        min-height: 0 !important;
        overflow: hidden;
    }

    @supports (height: 100dvh) {
        .wrapper .content-wrapper {
            --messages-workspace-height: calc(100dvh - (3.5rem + 1px) - (3.5rem + 1px));
        }
    }

    .content-wrapper>.content-header {
        flex: 0 0 auto;
    }

    .content-wrapper>.content {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
    }

    .content-wrapper>.content>.container-fluid {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-height: 0;
    }

    .messages-shell {
        flex: 1 1 auto;
        height: auto;
        max-height: var(--messages-visible-max-height, none);
        min-height: 0;
        overflow: hidden;
    }

    .messages-panel>[class*="col-"] {
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    .messages-panel .card {
        border-radius: 0;
        border-top: none;
        height: 100%;
    }

    .messages-sidebar-card,
    .messages-thread-card {
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    .messages-sidebar-card {
        display: flex;
        flex-direction: column;
    }

    .messages-active-chat-panel {
        min-height: 0;
    }

    .messages-active-chat-card {
        display: grid;
        grid-template-rows: auto minmax(0, 1fr) auto;
        height: 100%;
        min-height: 0;
        overflow: hidden;
    }

    .messages-active-chat-card>.messages-thread-header {
        grid-row: 1;
        min-width: 0;
    }

    .messages-active-chat-card>.messages-thread-list {
        grid-row: 2;
    }

    .messages-active-chat-card>.messages-composer {
        grid-row: 3;
        min-width: 0;
        width: 100%;
    }

    .messages-sidebar-header,
    .messages-search-wrap,
    .messages-thread-header,
    .messages-composer {
        background: #fff;
        flex: 0 0 auto;
    }

    .messages-sidebar-header,
    .messages-thread-header {
        border-bottom: 1px solid #e5e9f2;
        padding: 0.9rem 1rem;
    }

    .messages-search-wrap {
        border-bottom: 1px solid #eef2f7;
        padding: 0.75rem 1rem 0.9rem;
    }

    .messages-search-wrap .input-group-text,
    .messages-search-wrap .form-control,
    .messages-search-wrap .btn {
        border-color: #d7deea;
    }

    .messages-search-wrap .form-control:focus {
        border-color: #4f86ff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.12);
    }

    .messages-compose-trigger {
        align-items: center;
        border: none;
        box-shadow: 0 10px 24px rgba(0, 123, 255, 0.18);
        display: inline-flex;
        letter-spacing: 0.01em;
        border-radius: 999px;
        font-size: 0.84rem;
        font-weight: 700;
        height: 40px;
        line-height: 1;
        justify-content: center;
        padding: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        width: 40px;
    }

    .messages-compose-trigger:hover,
    .messages-compose-trigger:focus {
        box-shadow: 0 12px 28px rgba(0, 123, 255, 0.24);
        transform: translateY(-1px);
    }

    .messages-compose-trigger:focus {
        outline: none;
    }

    .messages-compose-trigger-icon {
        align-items: center;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 999px;
        display: inline-flex;
        height: 28px;
        justify-content: center;
        width: 28px;
    }

    .messages-list {
        background: #fff;
        flex: 1 1 auto;
        max-height: none;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .messages-thread-list {
        background: linear-gradient(180deg, #f8fafc 0%, #f3f6fb 100%);
        flex: 1;
        overflow-y: auto;
        padding: 1.25rem clamp(0.9rem, 2vw, 1.5rem);
        scroll-padding-block: 1.25rem;
        scrollbar-gutter: stable;
    }

    .messages-active-chat-card>.messages-thread-list {
        min-height: 0;
        min-width: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .messages-list-item {
        border-bottom: 1px solid #eef2f7;
        color: #1f2937;
        display: flex;
        gap: 0.85rem;
        padding: 0.9rem 1rem;
        position: relative;
        text-decoration: none;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .messages-list-item:hover {
        background: #f8fbff;
        color: #111827;
        text-decoration: none;
    }

    .messages-list-item.is-active {
        background: #eef4ff;
        box-shadow: inset 3px 0 0 #007bff;
    }

    .messages-avatar,
    .messages-thread-avatar,
    .messages-message-avatar {
        align-items: center;
        background: #e9eef7;
        border-radius: 50%;
        color: #546274;
        display: inline-flex;
        flex-shrink: 0;
        justify-content: center;
        overflow: hidden;
    }

    .messages-avatar {
        height: 46px;
        width: 46px;
    }

    .messages-thread-avatar {
        height: 42px;
        width: 42px;
    }

    .messages-message-avatar {
        height: 32px;
        width: 32px;
    }

    .messages-avatar img,
    .messages-thread-avatar img,
    .messages-message-avatar img {
        height: 100%;
        object-fit: cover;
        width: 100%;
    }

    .messages-conversation-body {
        min-width: 0;
    }

    .messages-conversation-row {
        align-items: flex-start;
        display: flex;
        gap: 0.75rem;
        justify-content: space-between;
        margin-bottom: 0.2rem;
    }

    .messages-conversation-title {
        color: #111827;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
    }

    .messages-conversation-time {
        color: #7a8699;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }

    .messages-conversation-meta {
        color: #6b7280;
        font-size: 0.8rem;
        line-height: 1.4;
        margin: 0;
    }

    .messages-empty-state,
    .messages-placeholder {
        color: #6b7280;
        text-align: center;
    }

    .messages-empty-state {
        padding: 2.5rem 1.25rem;
    }

    .messages-empty-state i,
    .messages-placeholder i {
        color: #c7d0dc;
    }

    .messages-placeholder {
        padding: 3rem 1.5rem;
    }

    .messages-pagination {
        background: #fff;
        border-top: 1px solid #eef2f7;
        flex: 0 0 auto;
        padding: 0.65rem 0.85rem;
    }

    .messages-pagination-nav {
        display: grid;
        gap: 0.55rem;
    }

    .messages-pagination-summary {
        color: #7a8699;
        font-size: 0.76rem;
        text-align: center;
    }

    .messages-pagination-list {
        gap: 0.35rem;
    }

    .messages-pagination .pagination {
        flex-wrap: wrap;
        margin: 0;
    }

    .messages-pagination .page-item .page-link {
        border: 1px solid #d7deea;
        border-radius: 10px;
        color: #5b677a;
        font-size: 0.78rem;
        line-height: 1.1;
        min-width: 34px;
        padding: 0.4rem 0.6rem;
        text-align: center;
    }

    .messages-pagination .page-item.active .page-link {
        background: #007bff;
        border-color: #007bff;
        color: #fff;
    }

    .messages-pagination .page-item.disabled .page-link {
        background: #f8fafc;
        color: #a0aec0;
    }

    .messages-pagination .page-item:not(.active):not(.disabled) .page-link:hover,
    .messages-pagination .page-item:not(.active):not(.disabled) .page-link:focus {
        background: #eef4ff;
        border-color: #bfd3ff;
        color: #2f5fb3;
        box-shadow: none;
    }

    .messages-thread-header {
        align-items: center;
        display: flex;
        gap: 0.85rem;
    }

    .messages-thread-avatar-wrap {
        flex: 0 0 auto;
        position: relative;
    }

    .messages-avatar-edit-button {
        align-items: center;
        border: 2px solid #fff;
        border-radius: 999px;
        bottom: -5px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.18);
        display: inline-flex;
        font-size: 0.68rem;
        height: 24px;
        justify-content: center;
        padding: 0;
        position: absolute;
        right: -7px;
        width: 24px;
    }

    .messages-avatar-edit-button:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.22);
    }

    .messages-thread-context {
        min-width: 0;
    }

    .messages-thread-title-row {
        align-items: center;
        display: flex;
        gap: 0.4rem;
        min-width: 0;
    }

    .messages-thread-title {
        color: #111827;
        font-size: 1rem;
        flex: 0 1 auto;
        font-weight: 700;
        line-height: 1.2;
        margin: 0;
        min-width: 0;
    }

    .messages-thread-title-action {
        align-items: center;
        border-radius: 999px;
        color: #6b7280;
        display: inline-flex;
        flex: 0 0 auto;
        height: 28px;
        justify-content: center;
        padding: 0;
        text-decoration: none;
        width: 28px;
    }

    .messages-thread-title-action:hover,
    .messages-thread-title-action:focus {
        background: #eef4ff;
        color: #0b63e6;
        text-decoration: none;
    }

    .messages-thread-subtitle {
        color: #6b7280;
        display: block;
        font-size: 0.82rem;
        margin-top: 0.2rem;
    }

    .messages-mobile-back {
        color: #6b7280;
        font-size: 1rem;
    }

    .messages-thread-load {
        margin-bottom: 1rem;
        text-align: center;
    }

    .messages-thread-load .btn {
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 600;
        padding-left: 0.9rem;
        padding-right: 0.9rem;
    }

    .messages-thread-item {
        align-items: flex-start;
        display: flex;
        gap: 0.55rem;
        margin-bottom: 1rem;
        min-width: 0;
    }

    .messages-thread-item.is-mine {
        justify-content: flex-end;
    }

    .messages-thread-item.is-other {
        justify-content: flex-start;
    }

    .messages-thread-content {
        display: flex;
        flex-direction: column;
        max-width: min(62%, 34rem);
        min-width: 0;
    }

    .messages-thread-item.is-mine .messages-thread-content {
        align-items: flex-end;
    }

    .messages-thread-item.is-other .messages-thread-content {
        align-items: flex-start;
    }

    .messages-thread-sender {
        color: #6b7280;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.01em;
        line-height: 1.2;
        margin: 0 0 0.35rem 0.55rem;
    }

    .messages-bubble {
        border-radius: 14px;
        box-shadow: 0 2px 7px rgba(15, 23, 42, 0.06);
        display: block;
        font-size: 0.9rem;
        line-height: 1.35;
        max-width: 100%;
        padding: 0.5rem 0.72rem;
        width: fit-content;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .messages-bubble-other {
        background: #fff;
        border: 1px solid #dbe3ef;
        border-bottom-left-radius: 5px;
        color: #172033;
    }

    .messages-bubble-mine {
        background: linear-gradient(135deg, #1677ff 0%, #0b63e6 100%);
        border: 1px solid rgba(11, 99, 230, 0.45);
        border-bottom-right-radius: 5px;
        box-shadow: 0 3px 10px rgba(11, 99, 230, 0.16);
        color: #fff;
    }

    .messages-bubble-unsent {
        background: #f3f5f9;
        border: 1px dashed #b8c2d4;
        box-shadow: none;
        color: #718096;
        font-style: italic;
    }

    .messages-attachment-stack {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        max-width: 100%;
    }

    .messages-thread-item.is-mine .messages-attachment-stack {
        align-items: flex-end;
    }

    .messages-thread-item.is-other .messages-attachment-stack {
        align-items: flex-start;
    }

    .messages-image-container {
        max-width: min(100%, 320px);
    }

    .messages-image-link {
        border-radius: 16px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.12);
        display: block;
        max-width: 100%;
        overflow: hidden;
    }

    .messages-image-container.has-error .messages-image-link {
        display: none;
    }

    .messages-image-link:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.22);
        outline: none;
    }

    .messages-message-image {
        background: #e9eef7;
        display: block;
        max-height: 320px;
        max-width: min(100%, 320px);
        object-fit: cover;
        width: 100%;
    }

    .messages-image-error {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #d9e2ee;
        border-radius: 12px;
        color: #637084;
        display: flex;
        flex-wrap: wrap;
        font-size: 0.82rem;
        gap: 0.45rem;
        min-height: 72px;
        padding: 0.75rem;
    }

    .messages-image-error[hidden] {
        display: none;
    }

    .messages-image-error .js-message-image-retry {
        font-size: inherit;
        font-weight: 700;
        padding: 0;
    }

    .messages-thread-meta {
        display: flex;
        margin-top: 0.32rem;
        width: 100%;
    }

    .messages-thread-item.is-mine .messages-thread-meta {
        justify-content: flex-end;
    }

    .messages-thread-item.is-other .messages-thread-meta {
        justify-content: flex-start;
    }

    .messages-thread-timestamp {
        color: #7a8699;
        font-size: 0.71rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        line-height: 1.35;
        padding: 0 0.45rem;
    }

    .messages-edited-label {
        color: #8b97aa;
        font-style: italic;
        margin-left: 0.25rem;
    }

    .messages-message-actions {
        display: flex;
        gap: 0.35rem;
        justify-content: flex-end;
        margin-top: 0.2rem;
        padding: 0 0.35rem;
    }

    .messages-thread-item.is-other .messages-message-actions {
        justify-content: flex-start;
    }

    .messages-message-action {
        color: #637084;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 0;
        text-decoration: none;
    }

    .messages-message-action:hover,
    .messages-message-action:focus {
        color: #0b63e6;
        text-decoration: none;
    }

    .messages-composer {
        border-top: 1px solid #e5e9f2;
        flex: 0 0 auto;
        padding: 0.75rem clamp(0.75rem, 1.5vw, 1rem);
        padding: 0.75rem clamp(0.75rem, 1.5vw, 1rem) calc(0.75rem + env(safe-area-inset-bottom, 0px));
    }

    .messages-composer-form {
        align-items: flex-end;
        background: #fff;
        border: 1px solid #d4dfee;
        border-radius: 24px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
        display: flex;
        gap: 0.65rem;
        margin: 0;
        padding: 0.48rem 0.5rem 0.48rem 0.9rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .messages-composer-form:focus-within {
        border-color: #4f86ff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.12), 0 6px 18px rgba(15, 23, 42, 0.08);
    }

    .messages-attachment-button {
        align-items: center;
        border-radius: 999px;
        color: #637084;
        display: inline-flex;
        height: 38px;
        justify-content: center;
        margin: 0;
        padding: 0;
        width: 38px;
    }

    .messages-attachment-button:hover,
    .messages-attachment-button:focus {
        background: #eef4ff;
        color: #0b63e6;
        text-decoration: none;
    }

    .messages-image-preview {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #d9e2ee;
        border-radius: 12px;
        display: flex;
        gap: 0.65rem;
        margin-bottom: 0.6rem;
        max-width: 360px;
        padding: 0.45rem;
    }

    .messages-image-preview[hidden] {
        display: none;
    }

    .messages-image-preview-thumb {
        background: #e9eef7;
        border-radius: 8px;
        flex: 0 0 auto;
        height: 48px;
        object-fit: cover;
        width: 48px;
    }

    .messages-image-preview-name {
        color: #4b5563;
        font-size: 0.8rem;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .messages-image-preview-remove {
        color: #637084;
        margin-left: auto;
        padding: 0.2rem 0.45rem;
    }

    .messages-image-preview-remove:hover,
    .messages-image-preview-remove:focus {
        color: #dc3545;
        text-decoration: none;
    }

    .messages-compose-attachment-button {
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .messages-compose-image-preview {
        margin-bottom: 0;
        max-width: 100%;
    }

    .messages-composer-input,
    .messages-compose-textarea {
        background: transparent;
        border: none;
        box-shadow: none;
        color: #172033;
        font-size: 0.94rem;
        line-height: 1.55;
        margin: 0;
        max-height: 160px;
        min-height: 28px;
        overflow-y: auto;
        padding: 0;
        resize: none;
        width: 100%;
    }

    .messages-composer-input::placeholder,
    .messages-compose-textarea::placeholder {
        color: #8b97aa;
    }

    .messages-composer-input:focus,
    .messages-compose-textarea:focus {
        background: transparent;
        border: none;
        box-shadow: none;
        outline: none;
    }

    .messages-send-button {
        align-items: center;
        border-radius: 999px;
        box-shadow: 0 6px 16px rgba(0, 123, 255, 0.22);
        display: inline-flex;
        height: 40px;
        justify-content: center;
        padding: 0;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        width: 40px;
    }

    .messages-send-button:hover,
    .messages-send-button:focus {
        box-shadow: 0 8px 18px rgba(0, 123, 255, 0.28);
        transform: translateY(-1px);
    }

    .messages-send-button i {
        font-size: 0.95rem;
    }

    #composeModal .modal-content,
    #renameGroupModal .modal-content,
    #editMessageModal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
    }

    #composeModal .modal-header,
    #composeModal .modal-footer,
    #renameGroupModal .modal-header,
    #renameGroupModal .modal-footer,
    #editMessageModal .modal-header,
    #editMessageModal .modal-footer {
        background: #f8fafc;
        border-color: #e5e9f2;
        padding: 0.95rem 1.15rem;
    }

    #composeModal .modal-body,
    #groupAvatarModal .modal-body,
    #renameGroupModal .modal-body,
    #editMessageModal .modal-body {
        padding: 1.15rem;
    }

    #composeModal .modal-title,
    #groupAvatarModal .modal-title,
    #renameGroupModal .modal-title,
    #editMessageModal .modal-title {
        color: #111827;
        font-size: 0.98rem;
        font-weight: 700;
    }

    .messages-group-avatar-preview {
        align-items: center;
        background: #e9eef7;
        border-radius: 50%;
        color: #546274;
        display: flex;
        height: 86px;
        justify-content: center;
        overflow: hidden;
        width: 86px;
    }

    .messages-group-avatar-preview img {
        height: 100%;
        object-fit: cover;
        width: 100%;
    }

    .messages-group-avatar-preview i {
        font-size: 1.7rem;
    }

    .messages-compose-label {
        color: #111827;
        display: block;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        margin-bottom: 0.45rem;
        text-transform: uppercase;
    }

    .messages-compose-textarea-wrap,
    #composeModal .select2-container--default .select2-selection--multiple {
        background: #fff;
        border: 1px solid #d9e2ee !important;
        border-radius: 14px !important;
        box-shadow: none !important;
    }

    #composeModal .select2-container--default .select2-selection--multiple {
        min-height: 46px !important;
        padding: 0.35rem 0.45rem !important;
    }

    #composeModal .select2-container--default.select2-container--focus .select2-selection--multiple,
    .messages-compose-textarea-wrap:focus-within {
        border-color: #4f86ff !important;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.12) !important;
    }

    .messages-compose-textarea-wrap {
        padding: 0.7rem 0.85rem;
    }

    .messages-group-name-input {
        border: 1px solid #d9e2ee;
        border-radius: 14px;
        color: #172033;
        font-size: 0.94rem;
        min-height: 46px;
        padding: 0.65rem 0.85rem;
    }

    .messages-group-name-input:focus {
        border-color: #4f86ff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.12);
    }

    #composeModal .select2-selection__choice {
        background: #e9f2ff !important;
        border: none !important;
        border-radius: 999px !important;
        color: #0b5ed7 !important;
        font-size: 0.78rem !important;
        font-weight: 600 !important;
        margin: 0.2rem 0.35rem 0.2rem 0 !important;
        padding: 0.2rem 0.65rem !important;
    }

    #composeModal .select2-selection__choice__remove {
        color: #0b5ed7 !important;
        margin-right: 0.35rem !important;
    }

    #composeModal .select2-dropdown {
        border: 1px solid #d9e2ee !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12) !important;
        overflow: hidden;
    }

    #composeModal .select2-results__option {
        font-size: 0.86rem;
        padding: 0.55rem 0.8rem !important;
    }

    #composeModal .select2-results__option--highlighted {
        background: #eef4ff !important;
        color: #0b5ed7 !important;
    }

    @media (max-width: 767.98px) {

        .messages-sidebar-card,
        .messages-thread-card {
            min-height: 0;
        }

        .messages-list {
            max-height: none;
        }

        .messages-thread-list {
            min-height: 0;
            padding: 1rem 0.75rem;
        }

        .messages-active-chat-card>.messages-thread-list {
            min-height: 0;
        }

        .messages-thread-content {
            max-width: min(80%, 26rem);
        }

        .messages-composer {
            padding: 0.6rem 0.7rem;
            padding: 0.6rem 0.7rem calc(0.6rem + env(safe-area-inset-bottom, 0px));
        }

        .messages-composer-form {
            border-radius: 22px;
            gap: 0.5rem;
            padding: 0.42rem 0.45rem 0.42rem 0.8rem;
        }

        .messages-message-image {
            max-height: 260px;
            max-width: 280px;
        }

        .messages-compose-trigger {
            height: 38px;
            width: 38px;
        }

        .messages-compose-trigger-icon {
            height: 26px;
            width: 26px;
        }

        #composeModal .modal-dialog,
        #groupAvatarModal .modal-dialog,
        #renameGroupModal .modal-dialog,
        #editMessageModal .modal-dialog {
            margin: 0.75rem;
        }
    }
</style>