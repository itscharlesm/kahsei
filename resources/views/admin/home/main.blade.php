@extends('admin.layouts.themes.main')

@section('title', 'Home')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Home</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item active"><a href="{{ url('home') }}">Home</a></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="app-content">
        <div class="container-fluid">

            @include('admin.layouts.partials.onclick')

            @php
                // Roles that can see announcements (EASY TO UPDATE)
                $announcementRoles = ['SUPERADMIN', 'ADMIN'];

                // Check access
                $canViewAnnouncements = false;
                foreach ($announcementRoles as $role) {
                    if (session($role) == '1') {
                        $canViewAnnouncements = true;
                        break;
                    }
                }
            @endphp

            <ul class="nav nav-tabs" role="tablist">
                @if ($canViewAnnouncements)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" id="announcements-tab" data-bs-toggle="tab" href="#announcements-content"
                            role="tab">
                            Announcements
                        </a>
                    </li>
                @endif
            </ul>

            <div class="tab-content">

                {{-- Announcements --}}
                @if ($canViewAnnouncements)
                    <div class="tab-pane fade show active" id="announcements-content" role="tabpanel">
                        <div class="row mt-3">

                            {{-- Announcements list --}}
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <span class="badge text-bg-info">
                                            <i class="fa fa-bullhorn"></i> Announcements
                                        </span>
                                    </h5>
                                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                                        <a class="btn btn-primary" href="javascript:void(0)" data-bs-toggle="modal"
                                            data-bs-target="#newAnnouncementModal">
                                            <i class="fa fa-comment"></i> Compose
                                        </a>
                                    @endif
                                </div>

                                @if ($announcements->count() == 0)
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-newspaper text-primary me-1"></i> Announcement
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">No announcement yet!</p>
                                        </div>
                                    </div>
                                @else
                                    @foreach ($announcements as $announcement)
                                        <div class="card mb-3">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">
                                                    <i class="fas fa-newspaper text-primary me-1"></i>
                                                    {{ $announcement->ann_title }}
                                                </h5>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i>
                                                    {{ \Carbon\Carbon::parse($announcement->ann_date_created)->diffForHumans() }}
                                                </small>
                                            </div>
                                            <div class="card-body">
                                                @if ($announcement->ann_image != '')
                                                    <a href="{{ asset('images/announcements/' . $announcement->ann_image) }}">
                                                        <img src="{{ asset('images/announcements/' . $announcement->ann_image) }}" alt=""
                                                            class="img-fluid rounded mb-2">
                                                    </a>
                                                @endif
                                                <p class="mb-0">{{ $announcement->ann_content }}</p>
                                            </div>
                                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                                                <div class="card-footer">
                                                    <a class="btn btn-danger btn-sm" href="javascript:void(0)" data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal-{{ $announcement->ann_uuid }}">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            {{-- Recent users --}}
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header text-bg-success">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-history"></i> Recent Users
                                        </h5>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        @foreach ($logins as $login)
                                            <li class="list-group-item d-flex align-items-center">
                                                <img class="rounded-circle me-3" src="{{ asset(getAvatar($login->usr_id)) }}"
                                                    alt="user image" style="width:50px; height:50px; object-fit:cover;">
                                                <div>
                                                    <div class="fw-semibold">
                                                        {{ $login->usr_last_name }}, {{ $login->usr_first_name }}
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ \Carbon\Carbon::parse($login->log_date_max)->diffForHumans() }}
                                                    </small>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modals (kept outside the cards so they stack correctly) --}}
    @if ($canViewAnnouncements)
        @foreach ($announcements as $announcement)
            <div class="modal fade" id="deleteModal-{{ $announcement->ann_uuid }}" tabindex="-1"
                aria-labelledby="deleteModalLabel-{{ $announcement->ann_uuid }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="deleteModalLabel-{{ $announcement->ann_uuid }}">
                                <i class="fa fa-exclamation-triangle"></i> Confirm Delete
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete the announcement:
                            <strong>{{ $announcement->ann_title }}</strong>?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form
                                action="{{ action('App\Http\Controllers\AnnouncementController@delete', [$announcement->ann_uuid]) }}"
                                method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <span class="fa fa-trash"></span> Yes, Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    {{-- New Announcement Modal --}}
    <div class="modal fade" id="newAnnouncementModal" tabindex="-1" aria-labelledby="newAnnouncementModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="newAnnouncementModalLabel">Create New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ action('App\Http\Controllers\AnnouncementController@save') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="ann_title">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ann_title" name="ann_title" placeholder="Title"
                                required />
                        </div>
                        <div class="mb-3">
                            <label for="ann_content">Message Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="ann_content" name="ann_content" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ann_image">Image</label>
                            <input type="file" class="form-control" id="ann_image" name="ann_image"
                                accept=".jpeg, .jpg, .png" />
                            <small class="form-text text-muted">
                                Please upload a valid image file in jpg or png format. Size of image should not be more
                                than 3MB.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="fa fa-save"></span> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection