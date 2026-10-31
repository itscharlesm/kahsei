@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Services</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Management</li>
                        <li class="breadcrumb-item active">Services</li>
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

                    {{-- Search Bar --}}
                    <form method="GET" action="{{ url('management/services/active') }}" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search" id="searchInput" class="form-control"
                                placeholder="Search service areas..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <span class="fa fa-search"></span> Search
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="row">
                        {{-- COLUMN 1: General Service Area Pricing --}}
                        <div class="col-lg-4 col-md-5">
                            <div class="card mb-4">
                                <div class="card-header bg-light d-flex align-items-center">
                                    <span class="fa fa-map-marker-alt mr-2"></span>
                                    <div>
                                        <strong>Service Area Pricing</strong>
                                        <small class="d-block" style="font-size:11px; opacity:.85;">
                                            Standard cost per area &amp; branch
                                        </small>
                                    </div>
                                    <span class="badge badge-light ml-auto">{{ $services->total() }} areas</span>
                                </div>
                                <div class="card-body p-0">
                                    <table id="serviceTable"
                                        class="table table-hover table-bordered table-sm responsive mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="vertical-align:middle; text-align:center">Area</th>
                                                <th style="vertical-align:middle; text-align:center">Cost</th>
                                                <th style="vertical-align:middle; text-align:center">Branch</th>
                                                <th style="vertical-align:middle; text-align:center" width="80px">Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($services as $service)
                                                <tr>
                                                    <td style="vertical-align:middle">{{ $service->svcpa_area }}</td>
                                                    <td style="vertical-align:middle; text-align:center">
                                                        ₱ {{ number_format($service->svcpa_cost, 2) }}
                                                    </td>
                                                    <td style="vertical-align:middle; text-align:center">
                                                        {{ $service->branch_name }}
                                                    </td>
                                                    <td style="vertical-align:middle; text-align:center">
                                                        <a class="btn btn-warning btn-sm" href="javascript:void(0)"
                                                            data-toggle="modal"
                                                            data-target="#editServiceModal-{{ $service->svcpa_id }}">
                                                            <span class="fa fa-edit"></span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- COLUMN 2: Termite Service Pricing --}}
                        <div class="col-lg-5 col-md-5">
                            <div class="card">
                                <div class="card-header bg-light d-flex align-items-center">
                                    <span class="fa fa-bug mr-2"></span>
                                    <div>
                                        <strong>Termite Service Pricing</strong>
                                        <small class="d-block" style="font-size:11px; opacity:.85;">
                                            Cost per sqm details &amp; branch — termite treatments
                                        </small>
                                    </div>
                                    <span class="badge badge-light ml-auto">{{ count($termiteServices) }} entries</span>
                                </div>
                                <div class="card-body p-0">
                                    <table id="serviceTable"
                                        class="table table-hover table-bordered table-sm responsive mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="vertical-align:middle; text-align:center">Sqm / Details</th>
                                                <th style="vertical-align:middle; text-align:center">Cost</th>
                                                <th style="vertical-align:middle; text-align:center">Branch</th>
                                                <th style="vertical-align:middle; text-align:center" width="80px">Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($termiteServices as $termite)
                                                <tr>
                                                    <td style="vertical-align:middle">
                                                        {{ $termite->svcpat_sqm_details }}
                                                    </td>
                                                    <td style="vertical-align:middle; text-align:center">
                                                        ₱ {{ number_format($termite->svcpat_cost, 2) }}
                                                    </td>
                                                    <td style="vertical-align:middle; text-align:center">
                                                        {{ $termite->branch_name }}
                                                    </td>
                                                    <td style="vertical-align:middle; text-align:center">
                                                        <a class="btn btn-warning btn-sm" href="javascript:void(0)"
                                                            data-toggle="modal"
                                                            data-target="#editTermiteModal-{{ $termite->svcpat_id }}">
                                                            <span class="fa fa-edit"></span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- COLUMN 3: Service Packages --}}
                        <div class="col-lg-3 col-md-2">
                            <div class="card">
                                <div class="card-header bg-light d-flex align-items-center">
                                    <span class="fa fa-info-circle"></span>
                                    <div class="ml-2">
                                        <strong>Service Pricing</strong>
                                    </div>
                                    <span class="badge badge-light ml-auto">
                                        3 parts
                                    </span>
                                </div>

                                <div class="card-body">
                                    {{-- Device Pricing --}}
                                    <div class="card mb-3" style="overflow-y:auto;">
                                        <div class="card-header bg-light d-flex align-items-center">
                                            <span class="fa fa-microchip"></span>
                                            <div class="ml-2">
                                                <strong>Device Pricing</strong>
                                            </div>
                                            <span class="badge badge-light ml-auto">
                                                {{ $deviceCosts->count() }} items
                                            </span>
                                        </div>

                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm table-hover mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th style="text-align:center; vertical-align:middle;">
                                                                Branch
                                                            </th>
                                                            <th style="text-align:center; vertical-align:middle;">
                                                                Cost
                                                            </th>
                                                            <th style="text-align:center; vertical-align:middle;"
                                                                width="60">
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($deviceCosts as $device)
                                                            <tr>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    {{ $device->branch_name }}
                                                                </td>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    ₱ {{ number_format($device->svcpad_cost, 2) }}
                                                                </td>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    <a class="btn btn-warning btn-sm" href="javascript:void(0)"
                                                                        data-toggle="modal"
                                                                        data-target="#editDeviceCostModal-{{ $device->svcpad_id }}">
                                                                        <span class="fa fa-edit"></span>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Location Pricing --}}
                                    <div class="card mb-3">
                                        <div class="card-header bg-light d-flex align-items-center">
                                            <span class="fa fa-location"></span>
                                            <div class="ml-auto">
                                                <strong>Location Pricing</strong>
                                            </div>
                                            <span class="badge badge-light ml-auto">
                                                {{ $locationCosts->count() }} items
                                            </span>
                                        </div>

                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-sm table-hover mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th
                                                                style="text-align:center; vertical-align:middle; white-space:nowrap;">
                                                                Branch
                                                            </th>
                                                            <th
                                                                style="text-align:center; vertical-align:middle; white-space:nowrap;">
                                                                First 10KM Cost
                                                            </th>
                                                            <th
                                                                style="text-align:center; vertical-align:middle; white-space:nowrap;">
                                                                Succeeding KM Cost
                                                            </th>
                                                            <th style="text-align:center; vertical-align:middle;"
                                                                width="60">
                                                                Action
                                                            </th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($locationCosts as $location)
                                                            <tr>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    {{ $location->branch_name }}
                                                                </td>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    ₱ {{ number_format($location->svcpal_first_cost, 2) }}
                                                                </td>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    ₱ {{ number_format($location->svcpal_succeeding_cost, 2) }}
                                                                </td>
                                                                <td style="vertical-align:middle; text-align:center;">
                                                                    <a class="btn btn-warning btn-sm" href="javascript:void(0)"
                                                                        data-toggle="modal"
                                                                        data-target="#editLocationCostModal-{{ $location->svcpal_id }}">
                                                                        <span class="fa fa-edit"></span>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Pest Types --}}
                                    <div class="card">
                                        <div class="card-header bg-light d-flex align-items-center">
                                            <span class="fa fa-bug"></span>
                                            <div class="ml-2">
                                                <strong>Pest Types</strong>
                                            </div>
                                            <span class="badge badge-light ml-auto">
                                                {{ $packages->count() }} types
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            @foreach ($packages as $package)
                                                <div class="mb-3">
                                                    <h6 class="text-dark mb-1">
                                                        <i class="fa fa-bug"></i>
                                                        {{ $package->svcp_pest_type }}
                                                    </h6>

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
        </div>
    </section>

    @foreach ($services as $service)
        {{-- Edit Service Modal --}}
        <div class="modal fade" id="editServiceModal-{{ $service->svcpa_id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ url('management/services/area/cost/update', $service->svcpa_id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title text-black">Edit Service Area
                            </h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Area Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="svcpa_area" value="{{ $service->svcpa_area }}"
                                    readonly>
                            </div>
                            <div class="form-group">
                                <label>Cost (₱) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="svcpa_cost"
                                    value="{{ $service->svcpa_cost }}" required>
                            </div>
                            <div class="form-group">
                                <label>Branch</label>
                                <input type="text" class="form-control" name="branch_name" value="{{ $service->branch_name }}"
                                    readonly>
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
    @endforeach

    @foreach ($termiteServices as $termite)
        {{-- Edit Termite Cost Modal --}}
        <div class="modal fade" id="editTermiteModal-{{ $termite->svcpat_id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form action="{{ url('management/services/area/termites/cost/update', $termite->svcpat_id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title">
                                Edit Termite Service Pricing
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Sqm / Details</label>
                                <input type="text" class="form-control" value="{{ $termite->svcpat_sqm_details }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>Cost (₱) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="svcpat_cost"
                                    value="{{ $termite->svcpat_cost }}" required>
                            </div>
                            <div class="form-group">
                                <label>Branch</label>
                                <input type="text" class="form-control" name="branch_name" value="{{ $termite->branch_name }}"
                                    readonly>
                            </div>
                            <input type="hidden" name="svcpat_sqm_details" value="{{ $termite->svcpat_sqm_details }}">
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
    @endforeach

    @foreach ($deviceCosts as $device)
        {{-- Edit Device Cost Modal --}}
        <div class="modal fade" id="editDeviceCostModal-{{ $device->svcpad_id }}" tabindex="-1" role="dialog"
            aria-hidden="true">

            <div class="modal-dialog" role="document">
                <form action="{{ url('management/services/area/device/cost/update', $device->svcpad_id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title text-black">
                                Edit Device Cost
                            </h5>

                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Branch</label>

                                <input type="text" class="form-control" value="{{ $device->branch_name }}" readonly>
                            </div>

                            <div class="form-group">
                                <label>
                                    Cost (₱)
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" class="form-control" name="svcpad_cost"
                                    value="{{ $device->svcpad_cost }}" required>
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
    @endforeach

    @foreach ($locationCosts as $location)
        {{-- Edit Location Cost Modal --}}
        <div class="modal fade" id="editLocationCostModal-{{ $location->svcpal_id }}" tabindex="-1" role="dialog"
            aria-hidden="true">

            <div class="modal-dialog" role="document">
                <form action="{{ url('management/services/area/location/cost/update', $location->svcpal_id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-white">
                            <h5 class="modal-title text-black">
                                Edit Location Cost
                            </h5>

                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Branch</label>

                                <input type="text" class="form-control" value="{{ $location->branch_name }}" readonly>
                            </div>

                            <div class="form-group">
                                <label>
                                    First 10KM Cost (₱)
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" class="form-control" name="svcpal_first_cost"
                                    value="{{ $location->svcpal_first_cost }}" required>
                            </div>

                            <div class="form-group">
                                <label>
                                    Succeeding KM Cost (₱)
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" step="0.01" class="form-control" name="svcpal_succeeding_cost"
                                    value="{{ $location->svcpal_succeeding_cost }}" required>
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
    @endforeach

    {{-- Dynamic Search While Typing --}}
    <script>
        document.getElementById("searchInput").addEventListener("keyup", function () {
            let value = this.value.toLowerCase();
            let rows = document.querySelectorAll("#servicesTable tbody tr");
            rows.forEach(function (row) {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(value) ? "" : "none";
            });
        });
    </script>
@endsection