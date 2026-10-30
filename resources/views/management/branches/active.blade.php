@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Branches</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Management</li>
                        <li class="breadcrumb-item active">Branches</li>
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
                                <a class="btn btn-danger btn-md mb-3" href="{{ url('management/branches/deleted') }}">
                                    <span class="fa fa-archive"></span> Deleted Branches
                                </a>
                            
                                <button type="button" class="btn btn-success mb-3" data-toggle="modal"
                                    data-target="#addBranchModal">
                                    <span class="fa fa-plus"></span> Add Branch
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-12 col-md-7">
                            <form method="GET" action="{{ url('management/branches/active') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search branches..." value="{{ request('search') }}">
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
                                        <th style="vertical-align: middle; text-align: center">Branch</th>
                                        <th style="vertical-align: middle; text-align: center">Latitude</th>
                                        <th style="vertical-align: middle; text-align: center">Longitude</th>
                                        <th style="vertical-align: middle; text-align: center">Created By</th>
                                        <th style="vertical-align: middle; text-align: center">Modified By</th>
                                        <th style="vertical-align: middle; text-align: center" width="110px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($branches as $branch)
                                        <tr>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $loop->iteration }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $branch->branch_name }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $branch->branch_latitude }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                {{ $branch->branch_longitude }}
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($branch->created_first_name))
                                                    {{ $branch->created_first_name }} {{ $branch->created_last_name }} -
                                                    {{ \Carbon\Carbon::parse($branch->branch_date_created)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if (!empty($branch->modified_first_name))
                                                    {{ $branch->modified_first_name }}
                                                    {{ $branch->modified_last_name }} -
                                                    {{ \Carbon\Carbon::parse($branch->branch_date_modified)->format('m/d/Y | h:i A') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td style="vertical-align: middle; text-align: center">
                                                @if ($branch->branch_id != 1)
                                                    <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                        data-toggle="modal"
                                                        data-target="#updateBranchModal-{{ $branch->branch_id }}">
                                                        <span class="fa fa-edit"></span>
                                                    </a>

                                                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                        <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                            data-toggle="modal"
                                                            data-target="#deleteModal-{{ $branch->branch_id }}">
                                                            <span class="fa fa-trash"></span>
                                                        </a>
                                                    @endif
                                                @else
                                                    -
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

    {{-- Add Branch Modal --}}
    <div class="modal fade" id="addBranchModal" tabindex="-1" role="dialog" aria-labelledby="addBranchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <form action="{{ url('management/branches/add') }}" method="POST">
                @csrf

                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addBranchModalLabel">
                            <span class="fa fa-plus text-white"></span> Add Branch
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- Branch Name --}}
                            <div class="col-md-12 mb-3">
                                <label for="add_branch_name">Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_branch_name" name="branch_name"
                                    placeholder="Branch Name" required>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Latitude --}}
                            <div class="col-md-6 mb-2">
                                <label for="add_branch_latitude">Latitude <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_branch_latitude"
                                    name="branch_latitude" placeholder="Latitude" required readonly>
                            </div>

                            {{-- Longitude --}}
                            <div class="col-md-6 mb-2">
                                <label for="add_branch_longitude">Longitude <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_branch_longitude"
                                    name="branch_longitude" placeholder="Longitude" required readonly>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-info btn-sm" id="pinMapBtn">
                                    <span class="fa fa-map-marker-alt"></span> Pin Map
                                </button>
                                <small class="text-muted ml-2">Click the map to set branch location</small>
                            </div>
                        </div>

                        {{-- Map Container (hidden by default) --}}
                        <div class="row" id="mapContainer" style="display: none;">
                            <div class="col-md-12">
                                <div id="branchMap"
                                    style="width: 100%; height: 350px; border: 1px solid #ccc; border-radius: 4px;"></div>
                                <small class="text-muted">
                                    <span class="fa fa-info-circle"></span>
                                    Click anywhere on the map to pin the branch location. You can also drag the marker.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Branch
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @foreach ($branches as $branch)
        {{-- Update Branch Modal --}}
        <div class="modal fade" id="updateBranchModal-{{ $branch->branch_id }}" tabindex="-1" role="dialog"
            aria-labelledby="updateBranchModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <form action="{{ url('management/branches/update/' . $branch->branch_id) }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-warning text-black">
                            <h5 class="modal-title text-black" id="updateBranchModalLabel-{{ $branch->branch_id }}">
                                <span class="fa fa-edit"></span> Update Branch
                            </h5>
                            <button type="button" class="close text-black" data-dismiss="modal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        <div class="modal-body">
                            {{-- Branch Name --}}
                            <div class="form-group">
                                <label>Branch Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="branch_name"
                                    value="{{ $branch->branch_name }}" required>
                            </div>

                            <div class="row">
                                {{-- Latitude --}}
                                <div class="col-md-6 mb-2">
                                    <label>Latitude <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control update-branch-latitude"
                                        id="update_branch_latitude_{{ $branch->branch_id }}" name="branch_latitude"
                                        value="{{ $branch->branch_latitude }}" placeholder="Latitude" required readonly>
                                </div>

                                {{-- Longitude --}}
                                <div class="col-md-6 mb-2">
                                    <label>Longitude <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control update-branch-longitude"
                                        id="update_branch_longitude_{{ $branch->branch_id }}" name="branch_longitude"
                                        value="{{ $branch->branch_longitude }}" placeholder="Longitude" required
                                        readonly>
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-info btn-sm update-pin-map-btn"
                                        data-id="{{ $branch->branch_id }}">
                                        <span class="fa fa-map-marker-alt"></span> Pin Map
                                    </button>
                                    <small class="text-muted ml-2">Click the map to change
                                        branch location</small>
                                </div>
                            </div>

                            {{-- Map Container --}}
                            <div class="row update-map-container" id="updateMapContainer-{{ $branch->branch_id }}"
                                style="display: none;">
                                <div class="col-md-12">
                                    <div id="updateBranchMap-{{ $branch->branch_id }}"
                                        style="width: 100%; height: 350px; border: 1px solid #ccc; border-radius: 4px;">
                                    </div>
                                    <small class="text-muted">
                                        <span class="fa fa-info-circle"></span>
                                        Click anywhere on the map to repin the branch
                                        location. You can also drag the marker.
                                    </small>
                                </div>
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
        <div class="modal fade" id="deleteModal-{{ $branch->branch_id }}" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ManagementController@branches_delete', [$branch->branch_id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="exampleModalLabel">
                                Please Confirm
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>DELETE</strong> branch -
                                <strong>{{ $branch->branch_name }}</strong>?
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

    {{-- Leaflet CSS (in your <head> or here) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    {{-- Leaflet JS --}}
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Add Branch Map Logic --}}
    <script>
        let branchMap = null;
        let branchMarker = null;
        let mapInitialized = false;

        document.getElementById('pinMapBtn').addEventListener('click', function() {
            const mapContainer = document.getElementById('mapContainer');

            if (mapContainer.style.display === 'none') {
                mapContainer.style.display = 'block';

                if (!mapInitialized) {
                    initBranchMap();
                } else {
                    // Leaflet needs a size invalidation when shown after being hidden
                    setTimeout(() => branchMap.invalidateSize(), 100);
                }
            } else {
                mapContainer.style.display = 'none';
            }
        });

        function initBranchMap() {
            mapInitialized = true;

            const defaultLat = 7.1907;
            const defaultLng = 125.4553;

            branchMap = L.map('branchMap').setView([defaultLat, defaultLng], 13);

            // OpenStreetMap tiles - free, no API key
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(branchMap);

            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        branchMap.setView([lat, lng], 15);
                    },
                    function() {
                        // Denied - stay on default
                    }
                );
            }

            // Click map to place marker
            branchMap.on('click', function(e) {
                placeMarker(e.latlng);
            });
        }

        function placeMarker(latlng) {
            if (branchMarker) {
                branchMap.removeLayer(branchMarker);
            }

            branchMarker = L.marker(latlng, {
                draggable: true
            }).addTo(branchMap);
            branchMarker.bindPopup('📍 Branch Location').openPopup();

            updateCoordinates(latlng);

            branchMarker.on('dragend', function(event) {
                updateCoordinates(event.target.getLatLng());
            });
        }

        function updateCoordinates(latlng) {
            document.getElementById('add_branch_latitude').value = latlng.lat.toFixed(7);
            document.getElementById('add_branch_longitude').value = latlng.lng.toFixed(7);
        }

        // Reset when modal closes
        $('#addBranchModal').on('hidden.bs.modal', function() {
            document.getElementById('add_branch_longitude').value = '';
            document.getElementById('add_branch_latitude').value = '';
            document.getElementById('mapContainer').style.display = 'none';

            if (branchMarker && branchMap) {
                branchMap.removeLayer(branchMarker);
                branchMarker = null;
            }

            // Full reset so geolocation re-runs on next open
            if (branchMap) {
                branchMap.remove();
                branchMap = null;
            }

            mapInitialized = false;
        });
    </script>

    {{-- Update Branch Map Logic --}}
    <script>
        const updateMapInstances = {};

        document.querySelectorAll('.update-pin-map-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const mapContainer = document.getElementById('updateMapContainer-' + id);

                if (mapContainer.style.display === 'none') {
                    mapContainer.style.display = 'block';

                    if (!updateMapInstances[id]) {
                        initUpdateBranchMap(id);
                    } else {
                        setTimeout(() => updateMapInstances[id].map.invalidateSize(), 100);
                    }
                } else {
                    mapContainer.style.display = 'none';
                }
            });
        });

        function initUpdateBranchMap(id) {
            const latInput = document.getElementById('update_branch_latitude_' + id);
            const lngInput = document.getElementById('update_branch_longitude_' + id);

            const existingLat = parseFloat(latInput.value) || 7.1907;
            const existingLng = parseFloat(lngInput.value) || 125.4553;

            const map = L.map('updateBranchMap-' + id).setView([existingLat, existingLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            // Auto-pin existing coordinates
            let marker = L.marker([existingLat, existingLng], {
                draggable: true
            }).addTo(map);
            marker.bindPopup('📍 Branch Location').openPopup();

            marker.on('dragend', function(event) {
                updateUpdateCoordinates(id, event.target.getLatLng());
            });

            map.on('click', function(e) {
                if (marker) map.removeLayer(marker);

                marker = L.marker(e.latlng, {
                    draggable: true
                }).addTo(map);
                marker.bindPopup('📍 Branch Location').openPopup();
                updateUpdateCoordinates(id, e.latlng);

                marker.on('dragend', function(event) {
                    updateUpdateCoordinates(id, event.target.getLatLng());
                });
            });

            updateMapInstances[id] = {
                map,
                marker
            };
        }

        function updateUpdateCoordinates(id, latlng) {
            document.getElementById('update_branch_latitude_' + id).value = latlng.lat.toFixed(7);
            document.getElementById('update_branch_longitude_' + id).value = latlng.lng.toFixed(7);
        }

        // Cleanup on modal close
        document.querySelectorAll('[id^="updateBranchModal-"]').forEach(function(modal) {
            $(modal).on('hidden.bs.modal', function() {
                const id = this.id.replace('updateBranchModal-', '');
                const mapContainer = document.getElementById('updateMapContainer-' + id);

                if (mapContainer) mapContainer.style.display = 'none';

                if (updateMapInstances[id]) {
                    updateMapInstances[id].map.remove();
                    delete updateMapInstances[id];
                }
            });
        });
    </script>
@endsection