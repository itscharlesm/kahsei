@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Price History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Logs</li>
                        <li class="breadcrumb-item active">Price History</li>
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
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('histories/prices') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search by updater, client or SA number..."
                                        value="{{ request('search') }}">
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
                                        <th class="text-center">Service Order</th>
                                        <th class="text-center">Client</th>
                                        <th class="text-center">Old Final Price</th>
                                        <th class="text-center">New Final Price</th>
                                        <th class="text-center">Difference</th>
                                        <th class="text-center">Updated By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($logs as $index => $log)
                                        @php
                                            $diff = $log->upcl_new_final_price - $log->upcl_old_final_price;
                                        @endphp
                                        <tr>
                                            {{-- Auto number (respects pagination + latest first) --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $logs->firstItem() + $index }}
                                            </td>

                                            {{-- Date --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ \Carbon\Carbon::parse($log->upcl_date_created)->format('m/d/Y | h:i A') }}
                                            </td>

                                            {{-- Service Order --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                SA-{{ str_pad($log->svc_sa_number, 6, '0', STR_PAD_LEFT) }}
                                            </td>

                                            {{-- Client --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $log->client_last_name }}, {{ $log->client_first_name }}
                                            </td>

                                            {{-- Old Price --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                ₱{{ number_format($log->upcl_old_final_price, 2) }}
                                            </td>

                                            {{-- New Price --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                ₱{{ number_format($log->upcl_new_final_price, 2) }}
                                            </td>

                                            {{-- Difference --}}
                                            <td style="vertical-align: middle; text-align: center"
                                                class="{{ $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : '') }}">
                                                {{ $diff > 0 ? '+' : ($diff < 0 ? '-' : '') }}₱{{ number_format(abs($diff), 2) }}
                                            </td>

                                            {{-- Updated By --}}
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $log->updater_last_name }}, {{ $log->updater_first_name }}
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
        document.getElementById("searchInput").addEventListener("keyup", function () {
            let value = this.value.toLowerCase();

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