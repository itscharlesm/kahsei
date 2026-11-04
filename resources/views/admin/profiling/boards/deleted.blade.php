@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Board of Members</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Profiling</li>
                        <li class="breadcrumb-item">Board of Members</li>
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
                                <a class="btn btn-success mb-3" href="{{ url('admin/profiling/board-of-members/active') }}">
                                    <span class="fa fa-users"></span> Board of Members
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <form method="GET" action="{{ url('admin/profiling/board-of-members/deleted') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search deleted members..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fa fa-search"></span> Search
                                    </button>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center">Name</th>
                                        <th class="align-middle text-center">Position</th>
                                        <th class="align-middle text-center">TIN</th>
                                        <th class="align-middle text-center">Birthdate</th>
                                        <th class="align-middle text-center">Age</th>
                                        <th class="align-middle text-center">Civil Status</th>
                                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                            <th class="align-middle text-center" width="110px">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($boards as $board)
                                         @php
                                            $birth = !empty($board->usr_birth_date)
                                                ? \Carbon\Carbon::parse($board->usr_birth_date)
                                                : null;

                                            $address = collect([
                                                $board->uadd_street,
                                                $board->uadd_barangay,
                                                $board->uadd_city,
                                                $board->uadd_province,
                                                $board->uadd_region,
                                            ])
                                                ->filter()
                                                ->implode(', ');
                                        @endphp
                                        <tr>
                                            <td class="align-middle text-start">
                                                <div class="d-flex align-items-center">
                                                    <img class="rounded-circle me-3 flex-shrink-0"
                                                        src="{{ asset(getAvatar($board->usr_id)) }}" alt="user image" loading="lazy"
                                                        style="width:50px; height:50px; object-fit:cover;">
                                                    <div>
                                                        {{ $board->usr_last_name }}, {{ $board->usr_first_name }}
                                                        {{ $board->usr_middle_name }}
                                                        <br />
                                                        <small>{{ $board->usr_email }}</small>
                                                        <br />
                                                        <small>{{ $address !== '' ? $address : '-' }}</small>
                                                        <br />
                                                        <em><small>Last login: {{ getLastLogin($board->usr_id) }}</small></em>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle text-center">
                                                @if (!empty($board->pos_name))
                                                    <span class="badge bg-success">{{ $board->pos_name }}</span>
                                                @else
                                                    <span class="badge bg-danger">No Position</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ trim((string) $board->usr_tin) !== '' ? $board->usr_tin : '-' }}
                                            </td>
                                            <td class="align-middle text-center"
                                                data-order="{{ $birth ? $birth->format('Y-m-d') : '' }}">
                                                {{ $birth ? $birth->format('m/d/Y') : '-' }}
                                            </td>
                                            <td class="align-middle text-center"
                                                data-order="{{ $birth ? $birth->age : -1 }}">
                                                {{ $birth ? $birth->age . ' yrs old' : '-' }}
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ !empty($board->usr_civil_status) ? $board->usr_civil_status : '-' }}
                                            </td>
                                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                <td class="align-middle text-center">
                                                    <a class="btn btn-success btn-sm" href="javascript:void(0)"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#restoreModal-{{ $board->usr_id }}">
                                                        <span class="fa fa-refresh"></span>
                                                    </a>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($boards as $board)
        {{-- Restore Modal --}}
        <div class="modal fade" id="restoreModal-{{ $board->usr_id }}" tabindex="-1"
            aria-labelledby="restoreModalLabel-{{ $board->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@boards_restore', [$board->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white" id="restoreModalLabel-{{ $board->usr_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>RESTORE</strong> member
                                <strong>{{ $board->usr_first_name }}
                                    {{ $board->usr_last_name }}</strong>?
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