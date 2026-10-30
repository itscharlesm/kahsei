@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Chemicals</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Management</li>
                        <li class="breadcrumb-item active">Chemicals</li>
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
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('DATA OFFICER') == '1')
                                <a class="btn btn-danger btn-md mb-3" href="{{ url('management/chemicals/deleted') }}">
                                    <span class="fa fa-archive"></span> Deleted Chemicals
                                </a>
                                
                                <button type="button" class="btn btn-success mb-3" data-toggle="modal"
                                    data-target="#addChemicalModal">
                                    <span class="fa fa-plus"></span> Add Chemical
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('management/chemicals/active') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search chemicals..." value="{{ request('search') }}">
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
                                        <th style="vertical-align: middle; text-align: center">No</th>
                                        <th style="vertical-align: middle; text-align: center">Chemical</th>
                                        <th style="vertical-align: middle; text-align: center">Created By</th>
                                        <th style="vertical-align: middle; text-align: center">Modified By</th>
                                        <th style="vertical-align: middle; text-align: center" width="110px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($chemicals as $chemical)
                                        <tr>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $chemical->chem_name }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($chemical->created_first_name))
                                                    {{ $chemical->created_first_name }} {{ $chemical->created_last_name }} -
                                                    {{ \Carbon\Carbon::parse($chemical->chem_date_created)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($chemical->modified_first_name))
                                                    {{ $chemical->modified_first_name }}
                                                    {{ $chemical->modified_last_name }} -
                                                    {{ \Carbon\Carbon::parse($chemical->chem_date_modified)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                    data-toggle="modal"
                                                    data-target="#updateChemicalModal-{{ $chemical->chem_id }}">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                    <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                        data-toggle="modal" data-target="#deleteModal-{{ $chemical->chem_id }}">
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
    </section>

    {{-- Add Chemicals Modal --}}
    <div class="modal fade" id="addChemicalModal" tabindex="-1" role="dialog" aria-labelledby="addChemicalModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xs" role="document">
            <form action="{{ url('management/chemicals/add') }}" method="POST">
                @csrf

                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addChemicalModalLabel">
                            <span class="fa fa-plus text-white"></span> Add Chemical
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Chemical Name --}}
                            <div class="col-md-12 mb-3">
                                <label for="chem_name">Chemical Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="chem_chemical_name" name="chem_name"
                                    placeholder="Chemical" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Chemical
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @foreach ($chemicals as $chemical)
        {{-- Update Chemicals Modal --}}
        <div class="modal fade" id="updateChemicalModal-{{ $chemical->chem_id }}" tabindex="-1" role="dialog"
            aria-labelledby="updateChemicalModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xs" role="document">
                <form action="{{ url('management/chemicals/update/' . $chemical->chem_id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-black">
                            <h5 class="modal-title text-black" id="updateChemicalModalLabel-{{ $chemical->chem_id }}">
                                <span class="fa fa-edit"></span> Update Chemical
                            </h5>

                            <button type="button" class="close text-black" data-dismiss="modal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            {{-- Chemical --}}
                            <div class="form-group">
                                <label>Chemical <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="chem_name" value="{{ $chemical->chem_name }}"
                                    required>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>

                            <button type="submit" class="btn btn-warning">
                                <span class="fa fa-save"></span> Update
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete Modal --}}
        <div class="modal fade" id="deleteModal-{{ $chemical->chem_id }}" tabindex="-1" role="dialog"
            aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ManagementController@chemicals_delete', [$chemical->chem_id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="deleteModalLabel">
                                Please Confirm
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>DELETE</strong> chemical -
                                <strong>{{ $chemical->chem_name }}</strong>?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
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