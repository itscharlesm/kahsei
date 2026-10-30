<style>
    /* MODAL BASE STYLING (UNIVERSAL) */

    /* Header */
    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        padding: 14px 20px;
    }

    /* Title */
    .modal-title {
        font-size: 15px;
        font-weight: 600;
        color: #000000;
    }

    /* Body */
    .modal-body {
        padding: 20px;
    }

    /* Footer */
    .modal-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        padding: 12px 20px;
    }

    /* LABELS */
    .modal label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #000000;
        margin-bottom: 6px;
    }

    /* TEXTAREA */
    .modal textarea.form-control {
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        color: #333;
        transition: border-color 0.15s, box-shadow 0.15s;
        resize: none;
    }

    .modal textarea.form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
        outline: none;
    }

    /* BUTTONS */

    /* Secondary */
    .modal .btn-secondary {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        padding: 7px 16px;
        border: 1.5px solid #dee2e6;
        background: #fff;
        color: #000000;
        transition: background 0.15s, color 0.15s;
    }

    .modal .btn-secondary:hover {
        background: #f1f3f5;
        color: #333;
    }

    /* Success */
    .modal .btn-success {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        padding: 7px 18px;
        background-color: #198754;
        border-color: #198754;
        transition: background 0.15s, transform 0.1s;
    }

    .modal .btn-success:hover {
        background-color: #157347;
        border-color: #157347;
    }

    .modal .btn-success:active {
        transform: scale(0.97);
    }

    /* Primary */
    .modal .btn-primary {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        padding: 7px 18px;
        background-color: #0d6efd;
        border-color: #0d6efd;
        transition: background 0.15s, transform 0.1s;
    }

    .modal .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0b5ed7;
    }

    .modal .btn-primary:active {
        transform: scale(0.97);
    }

    /* Info */
    .modal .btn-info {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        padding: 7px 18px;
        background-color: #0dcaf0;
        border-color: #0dcaf0;
        transition: background 0.15s, transform 0.1s;
    }

    .modal .btn-info:hover {
        background-color: #31d2f2;
        border-color: #25cff2;
    }

    .modal .btn-info:active {
        transform: scale(0.97);
    }

    /* Warning */
    .modal .btn-warning {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        padding: 7px 18px;
        background-color: #ffc107;
        border-color: #ffc107;
        color: #212529;
        transition: background 0.15s, transform 0.1s;
    }

    .modal .btn-warning:hover {
        background-color: #ffca2c;
        border-color: #ffc720;
        color: #212529;
    }

    .modal .btn-warning:active {
        transform: scale(0.97);
    }

    /* Danger */
    .modal .btn-danger {
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        padding: 7px 18px;
        background-color: #dc3545;
        border-color: #dc3545;
        transition: background 0.15s, transform 0.1s;
    }

    .modal .btn-danger:hover {
        background-color: #bb2d3b;
        border-color: #b02a37;
    }

    .modal .btn-danger:active {
        transform: scale(0.97);
    }

    /* SELECT2 (SINGLE) */

    /* Container */
    .modal .select2-container--default .select2-selection--single {
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        height: 42px !important;
        background-color: #fff !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
        display: flex !important;
        align-items: center !important;
        padding: 0 10px !important;
    }

    /* Focus */
    .modal .select2-container--default.select2-container--focus .select2-selection--single,
    .modal .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12) !important;
    }

    /* Selected text */
    .modal .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #212529 !important;
        line-height: normal !important;
        padding-left: 0 !important;
        font-size: 13px !important;
    }

    /* Arrow */
    .modal .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
        right: 8px !important;
    }

    /* Placeholder */
    .modal .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d !important;
    }

    /* Dropdown */
    .select2-dropdown {
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
        font-size: 13px !important;
    }

    /* Options */
    .select2-container--default .select2-results__option {
        padding: 9px 14px !important;
    }

    .select2-container--default .select2-results__option--highlighted {
        background-color: #e7f1ff !important;
        color: #0b5ed7 !important;
    }

    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: #cfe2ff !important;
        color: #084298 !important;
        font-weight: 500 !important;
    }

    /* Search field */
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: 6px !important;
        padding: 6px 10px !important;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1) !important;
    }

    /* Full width */
    .select2-container {
        width: 100% !important;
    }

    /* SELECT2 (MULTIPLE) */

    /* Container */
    .modal .select2-container--default .select2-selection--multiple {
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        min-height: 42px !important;
        max-height: 160px !important;
        overflow-y: auto !important;
        padding: 4px 6px !important;
        background-color: #fff !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
    }

    /* Focus */
    .modal .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12) !important;
    }

    /* Tags */
    .modal .select2-selection__choice {
        background-color: #e7f1ff !important;
        border-radius: 20px !important;
        color: #0b5ed7 !important;
        font-size: 12px !important;
        padding: 3px 10px !important;
        margin: 3px 4px 3px 0 !important;
    }

    /* Remove icon */
    .modal .select2-selection__choice__remove {
        color: #0d6efd !important;
        font-weight: 700 !important;
    }

    .modal .select2-selection__choice__remove:hover {
        color: #0b5ed7 !important;
    }

    /* Dropdown */
    .select2-dropdown {
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08) !important;
        font-size: 13px !important;
    }

    /* Options */
    .select2-container--default .select2-results__option {
        padding: 9px 14px !important;
    }

    .select2-container--default .select2-results__option--highlighted {
        background-color: #e7f1ff !important;
        color: #0b5ed7 !important;
    }

    .select2-container--default .select2-results__option[aria-selected="true"] {
        background-color: #cfe2ff !important;
        color: #084298 !important;
        font-weight: 500 !important;
    }

    /* Search field */
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #e2e8f0 !important;
        border-radius: 6px !important;
        padding: 6px 10px !important;
    }

    .select2-search--dropdown .select2-search__field:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.1) !important;
    }

    /* Full width */
    .select2-container {
        width: 100% !important;
    }

    /* LIST / CONVERSATION UI (OPTIONAL) */

    .msg-list-item {
        transition: background .15s;
    }

    .msg-list-item:hover {
        background: #f8f9fa;
    }

    .msg-display-name,
    .msg-preview {
        max-width: 160px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    /* CARD CLEANUP (OPTIONAL LAYOUT) */

    #msg-sidebar .card,
    #msg-main-panel .card {
        border-radius: 0;
        border-top: none;
    }
</style>