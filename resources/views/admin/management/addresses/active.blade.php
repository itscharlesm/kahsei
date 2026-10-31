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
                                <a class="btn btn-danger mb-3" href="{{ url('management/addresses/deleted') }}">
                                    <span class="fa fa-archive"></span> Deleted Addresses
                                </a>

                                <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal"
                                    data-bs-target="#addAddressModal">
                                    <span class="fa fa-plus"></span> Add Address
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('management/addresses/active') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search addresses..." value="{{ request('search') }}">
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
                                        <th class="align-middle text-center" width="110px">Action</th>
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
                                            <td class="align-middle text-center">
                                                <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateAddressModal-{{ $address->add_id }}">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                    <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $address->add_id }}">
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

    {{-- Add Address Modal --}}
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('management/addresses/add') }}" method="POST">
                    @csrf

                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addAddressModalLabel">
                            <span class="fa fa-plus text-white"></span> Add Address
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Address Name --}}
                            <div class="col-md-12 mb-3">
                                <label for="add_address_name">Address Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_address_name" name="add_name"
                                    placeholder="Address Identifier" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Address Identifier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($addresses as $address)
        {{-- Update Address Modal --}}
        <div class="modal fade" id="updateAddressModal-{{ $address->add_id }}" tabindex="-1"
            aria-labelledby="updateAddressModalLabel-{{ $address->add_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('management/addresses/update/' . $address->add_id) }}" method="POST">
                        @csrf

                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="updateAddressModalLabel-{{ $address->add_id }}">
                                <span class="fa fa-edit"></span> Update Address Identifier
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            {{-- Address Identifier --}}
                            <div class="mb-3">
                                <label>Address Identifier <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="add_name" value="{{ $address->add_name }}"
                                    required>
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
        <div class="modal fade" id="deleteModal-{{ $address->add_id }}" tabindex="-1"
            aria-labelledby="deleteModalLabel-{{ $address->add_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ManagementController@addresses_delete', [$address->add_id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="deleteModalLabel-{{ $address->add_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>DELETE</strong> address
                                identifier -
                                <strong>{{ $address->add_name }}</strong>?
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