@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Addresses</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Management</li>
                        <li class="breadcrumb-item active">Addresses</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="app-content">
        @include('layouts.partials.onclick')
        @include('layouts.partials.alerts')
        @include('layouts.partials.modal_style')
        <div class="container-fluid">
            <div class="card">
                <div class="card-body overflow-auto">
                    <div class="row">
                        <div class="col-md-12">
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('DATA OFFICER') == '1')
                                <a class="btn btn-success mb-3" href="{{ url('management/addresses/active') }}">
                                    <span class="fa fa-location-dot"></span> Addresses
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('management/addresses/deleted') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search deleted address identifiers..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fa fa-search"></span> Search
                                    </button>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center">No</th>
                                        <th class="align-middle text-center">Address Identifier</th>
                                        <th class="align-middle text-center">Created By</th>
                                        <th class="align-middle text-center">Modified By</th>
                                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                            <th class="align-middle text-center" width="110px">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($addresses as $address)
                                        <tr>
                                            <td class="align-middle text-center">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ $address->add_name }}
                                            </td>
                                            <td class="align-middle text-center">
                                                @if (!empty($address->created_first_name))
                                                    {{ $address->created_first_name }} {{ $address->created_last_name }} -
                                                    {{ \Carbon\Carbon::parse($address->add_date_created)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">
                                                @if (!empty($address->modified_first_name))
                                                    {{ $address->modified_first_name }}
                                                    {{ $address->modified_last_name }} -
                                                    {{ \Carbon\Carbon::parse($address->add_date_modified)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                <td class="align-middle text-center">
                                                    <a class="btn btn-success btn-sm mb-1" href="javascript:void(0)"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#restoreModal-{{ $address->add_id }}">
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

    @foreach ($addresses as $address)
        {{-- Restore Modal --}}
        <div class="modal fade" id="restoreModal-{{ $address->add_id }}" tabindex="-1"
            aria-labelledby="restoreModalLabel-{{ $address->add_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ManagementController@addresses_restore', [$address->add_id]) }}">
                        @csrf
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white" id="restoreModalLabel-{{ $address->add_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>RESTORE</strong> address
                                identifier -
                                <strong>{{ $address->add_name }}</strong>?
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