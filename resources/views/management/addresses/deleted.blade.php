@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Addresses</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Management</li>
                        <li class="breadcrumb-item active">Addresses</li>
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
                                <a class="btn btn-success btn-md mb-3" href="{{ url('management/addresses/active') }}">
                                    <span class="fa fa-location"></span> Addresses
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
                                        <th style="vertical-align: middle; text-align: center">Address Identifier</th>
                                        <th style="vertical-align: middle; text-align: center">Created By</th>
                                        <th style="vertical-align: middle; text-align: center">Modified By</th>
                                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                            <th style="vertical-align: middle; text-align: center" width="110px">Action
                                            </th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($addresses as $address)
                                        <tr>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $address->add_name }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($address->created_first_name))
                                                    {{ $address->created_first_name }} {{ $address->created_last_name }} -
                                                    {{ \Carbon\Carbon::parse($address->add_date_created)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($address->modified_first_name))
                                                    {{ $address->modified_first_name }}
                                                    {{ $address->modified_last_name }} -
                                                    {{ \Carbon\Carbon::parse($address->add_date_modified)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                <td style="vertical-align: middle; text-align: center">
                                                    <a class="btn btn-success btn-sm mb-1" href="javascript:void(0)"
                                                        data-toggle="modal"
                                                        data-target="#restoreModal-{{ $address->add_id }}">
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
    </section>

    @foreach ($addresses as $address)
        {{-- Restore Modal --}}
        <div class="modal fade" id="restoreModal-{{ $address->add_id }}" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ManagementController@addresses_restore', [$address->add_id]) }}">
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
                            <p>Are you sure you want to <strong>RESTORE</strong> address
                                identifier -
                                <strong>{{ $address->add_name }}</strong>?
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