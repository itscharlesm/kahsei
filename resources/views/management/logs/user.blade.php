@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">User History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Logs</li>
                        <li class="breadcrumb-item active">User History</li>
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
                        <!-- Table Column -->
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('histories/users') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search users..." value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <span class="fa fa-search"></span> Search
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <table id="logsTable" class="table table-hover table-bordered table-sm responsive">
                                <thead>
                                    <tr>
                                        <th class="text-center">No</th>
                                        <th class="text-center">Date</th>
                                        <th class="text-center">Name</th>
                                        <th class="text-center">Title</th>
                                        <th class="text-center">Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($logs as $index => $log)
                                        <tr>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $logs->firstItem() + $index }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ \Carbon\Carbon::parse($log->log_date)->format('m/d/Y | h:i A') }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $log->usr_last_name }}, {{ $log->usr_first_name }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $log->log_title ?? '-' }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $log->log_details ?? '-' }}
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