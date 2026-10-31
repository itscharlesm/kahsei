@extends('admin.layouts.themes.main')

@section('page_styles')
    @include('admin.messages.partials.styles')
@endsection

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Messages</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Messages</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        @include('admin.layouts.partials.onclick')
        @include('admin.layouts.partials.alerts')

        <div class="container-fluid">
            <div class="row messages-shell messages-panel">
                <div class="col-12 col-md-4 col-lg-3 px-0" id="msg-sidebar">
                    @include('admin.messages.partials.sidebar', [
                        'conversations' => $conversations,
                        'search' => $search,
                        'searchAction' => url('messages'),
                        'searchHiddenInputs' => [],
                    ])
                </div>

                <div class="col-md-8 col-lg-9 px-0 d-none d-md-flex flex-column" id="msg-main-panel">
                    <div class="card h-100 mb-0 d-flex align-items-center justify-content-center messages-thread-card">
                        <div class="messages-placeholder">
                            <i class="fas fa-comments fa-4x mb-3 d-block"></i>
                            <h5 class="mb-2 text-dark">Your Messages</h5>
                            <p class="mb-3">Select a conversation on the left or start a new one.</p>
                            <a href="#" data-toggle="modal" data-target="#composeModal" class="btn btn-primary">
                                <i class="fas fa-edit mr-1"></i> New Message
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('admin.messages.partials.compose_modal', ['users' => $users])
@endsection

@section('page_scripts')
    @include('admin.messages.partials.scripts')
@endsection
