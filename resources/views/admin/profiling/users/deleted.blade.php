@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Users</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Profiling</li>
                        <li class="breadcrumb-item">Users</li>
                        <li class="breadcrumb-item active">Deleted</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="app-content">
        @include('admin.layouts.partials.onclick')
        @include('admin.layouts.partials.alerts')
        @include('admin.layouts.partials.modal_style')
        <div class="container-fluid">
            <div class="card">
                <div class="card-body overflow-auto">
                    <div class="row">
                        <div class="col-md-12">
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                                <a class="btn btn-success mb-3" href="{{ url('admin/profiling/users/active') }}">
                                    <span class="fa fa-users"></span> Users
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-9 col-md-7">
                            <form method="GET" action="{{ url('admin/profiling/users/deleted') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search deleted users..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fa fa-search"></span> Search
                                    </button>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center">Name</th>
                                        <th class="align-middle text-center" width="130px">Role(s)</th>
                                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                            <th class="align-middle text-center" width="110px">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td class="align-middle text-start">
                                                {{ $user->usr_last_name }}, {{ $user->usr_first_name }}
                                                {{ $user->usr_middle_name }}
                                                <br />
                                                <small>{{ $user->usr_email }}</small>
                                                <br />
                                                <em><small>Last login: {{ getLastLogin($user->usr_id) }}</small></em>
                                            </td>
                                            <td class="align-middle text-center">
                                                @if (!empty($user->roles))
                                                    @foreach (explode(', ', $user->roles) as $role)
                                                        <span class="badge bg-success">{{ $role }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="badge bg-danger">No Role Assigned</span>
                                                @endif
                                            </td>
                                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                <td class="align-middle text-center">
                                                    <a class="btn btn-success btn-sm" href="javascript:void(0)"
                                                        data-bs-toggle="modal" data-bs-target="#restoreModal-{{ $user->usr_id }}">
                                                        <span class="fa fa-refresh"></span>
                                                    </a>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Role Info Column -->
                        <div class="col-lg-3 col-md-5">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><i class="fa fa-info-circle"></i> Role Information</strong>
                                </div>
                                <div class="card-body overflow-auto">
                                    @foreach ($roles as $role)
                                        <div class="mb-3">
                                            <h6 class="text-dark mb-1">
                                                <i class="fa fa-user-tag"></i> {{ $role->rol_name }}
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                {{ $role->rol_description ?? 'No description available' }}
                                            </p>
                                            <hr>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($users as $user)
        {{-- Restore Modal --}}
        <div class="modal fade" id="restoreModal-{{ $user->usr_id }}" tabindex="-1"
            aria-labelledby="restoreModalLabel-{{ $user->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@users_restore', [$user->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white" id="restoreModalLabel-{{ $user->usr_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>RESTORE</strong> user
                                <strong>{{ $user->usr_first_name }}
                                    {{ $user->usr_last_name }}</strong>?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-success">
                                <span class="fa fa-refresh"></span> Confirm Restore
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('page_scripts')
    {{-- Dynamic Search While Typing --}}
    <script>
        document.getElementById("searchInput").addEventListener("keyup", function () {
            let value = this.value.toLowerCase();

            // Select all tables with ID containing "Table"
            let tables = document.querySelectorAll('table[id*="Table"]');

            tables.forEach(function (table) {
                let rows = table.querySelectorAll("tbody tr");

                rows.forEach(function (row) {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(value) ? "" : "none";
                });
            });
        });
    </script>
@endsection