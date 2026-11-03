@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Users</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
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
    <section class="content">
        @include('layouts.partials.onclick')
        @include('layouts.partials.alerts')
        @include('layouts.partials.modal_style')
        <div class="container-fluid">
            <div class="card">
                <div class="card-body overflow-auto">
                    <div class="row">
                        <div class="col-md-12">
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                                <a class="btn btn-success btn-md mb-3" href="{{ url('profiling/users/active') }}">
                                    <span class="fa fa-users"></span> Users
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-9 col-md-7">
                            <form method="GET" action="{{ url('profiling/users/deleted') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search deleted users..." value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <span class="fa fa-search"></span> Search
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm responsive">
                                <thead>
                                    <tr>
                                        <th style="vertical-align: middle; text-align: center">Name</th>
                                        <th style="vertical-align: middle; text-align: center">Branch</th>
                                        <th style="vertical-align: middle; text-align: center" width="130px">Role(s)</th>
                                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                                            <th style="vertical-align: middle; text-align: center" width="110px">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td style="vertical-align: middle; text-align: left">
                                                {{ $user->usr_last_name }}, {{ $user->usr_first_name }}
                                                {{ $user->usr_middle_name }}
                                                <br />
                                                <small>{{ $user->usr_email }}</small>
                                                <br />
                                                <em><small>Last login: {{ getLastLogin($user->usr_id) }}</small></em>
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $user->branch_name }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($user->roles))
                                                    @foreach (explode(', ', $user->roles) as $role)
                                                        <span class="badge bg-success">{{ $role }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="badge bg-danger">No Role Assigned</span>
                                                @endif
                                            </td>
                                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                <td style="vertical-align: middle; text-align: center">
                                                    <a class="btn btn-success btn-sm" href="javascript:void(0)"
                                                        data-toggle="modal"
                                                        data-target="#restoreModal-{{ $user->usr_id }}">
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
                                <div class="card-body" style="overflow-y: auto;">
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
    </section>

    @foreach ($users as $user)
        {{-- Restore Modal --}}
        <div class="modal fade" id="restoreModal-{{ $user->usr_id }}" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@users_restore', [$user->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white" id="exampleModalLabel">
                                Please Confirm
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>RESTORE</strong> user
                                <strong>{{ $user->usr_first_name }}
                                    {{ $user->usr_last_name }}</strong>?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
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

    <script>
        $(function() {
            // Initialize Select2 Elements with classic theme
            $('.select2').select2({
                theme: "classic"
            });
        });
    </script>

    {{-- Dynamic Search While Typing --}}
    <script>
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let value = this.value.toLowerCase();

            // Select all tables with ID containing "Table"
            let tables = document.querySelectorAll('table[id*="Table"]');

            tables.forEach(function(table) {
                let rows = table.querySelectorAll("tbody tr");

                rows.forEach(function(row) {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(value) ? "" : "none";
                });
            });
        });
    </script>
@endsection