@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Positions</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Management</li>
                        <li class="breadcrumb-item active">Positions</li>
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
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('DATA OFFICER') == '1')
                                <a class="btn btn-danger mb-3" href="{{ url('admin/management/positions/deleted') }}">
                                    <span class="fa fa-archive"></span> Deleted Positions
                                </a>

                                <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal"
                                    data-bs-target="#addPositionModal">
                                    <span class="fa fa-plus"></span> Add Position
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('admin/management/positions/active') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search positions..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fa fa-search"></span> Search
                                    </button>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center">No</th>
                                        <th class="align-middle text-center">Position</th>
                                        <th class="align-middle text-center">Description</th>
                                        <th class="align-middle text-center" width="110px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($positions as $position)
                                        <tr>
                                            <td class="align-middle text-center">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ $position->pos_name }}
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ $position->pos_description ?? '-' }}
                                            </td>
                                            <td class="align-middle text-center">
                                                <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updatePositionModal-{{ $position->pos_id }}">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                    <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal-{{ $position->pos_id }}">
                                                        <span class="fa fa-trash"></span>
                                                    </a>
                                                @endif
                                            </td>
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

    {{-- Add Position Modal --}}
    <div class="modal fade" id="addPositionModal" tabindex="-1" aria-labelledby="addPositionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('admin/management/positions/add') }}" method="POST">
                    @csrf

                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addPositionModalLabel">
                            <span class="fa fa-plus text-white"></span> Add Position
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Position Name --}}
                            <div class="col-md-12 mb-3">
                                <label for="add_position_name">Position Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_position_name" name="pos_name"
                                    placeholder="Position" required>
                            </div>
                            {{-- Position Description --}}
                            <div class="col-md-12 mb-3">
                                <label for="add_position_description">Position Description <span
                                        class="text-secondary">(Optional)</span></label>
                                <input type="text" class="form-control" id="add_position_description" name="pos_description"
                                    placeholder="Position Description">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Position
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($positions as $position)
        {{-- Update Position Modal --}}
        <div class="modal fade" id="updatePositionModal-{{ $position->pos_id }}" tabindex="-1"
            aria-labelledby="updatePositionModalLabel-{{ $position->pos_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('admin/management/positions/update/' . $position->pos_id) }}" method="POST">
                        @csrf

                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="updatePositionModalLabel-{{ $position->pos_id }}">
                                <span class="fa fa-edit"></span> Update Position
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            {{-- Position --}}
                            <div class="mb-3">
                                <label>Position <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pos_name" value="{{ $position->pos_name }}"
                                    required>
                            </div>

                            {{-- Position Description --}}
                            <div class="mb-3">
                                <label>Position Description <span class="text-secondary">(Optional)</span></label>
                                <input type="text" class="form-control" name="pos_description"
                                    value="{{ $position->pos_description }}">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <span class="fa fa-save"></span> Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Delete Modal --}}
        <div class="modal fade" id="deleteModal-{{ $position->pos_id }}" tabindex="-1"
            aria-labelledby="deleteModalLabel-{{ $position->pos_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ url('admin/management/positions/delete', [$position->pos_id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="deleteModalLabel-{{ $position->pos_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>DELETE</strong> position
                                <strong>{{ $position->pos_name }}</strong>?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <span class="fa fa-trash"></span> Confirm Delete
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