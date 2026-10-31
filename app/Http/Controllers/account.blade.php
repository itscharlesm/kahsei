@extends('layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Account Information</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ action('App\Http\Controllers\AdminController@home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Account</li>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-alt text-warning mr-2"></i>
                        My Personal Information
                    </h5>
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ url('account/update') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- PROFILE IMAGE --}}
                        <div class="text-center mb-4">
                            <a href="{{ asset(getAvatar(session('usr_id'))) }}" id="avatarLink" download>
                                <img src="{{ asset(getAvatar(session('usr_id'))) }}" id="avatarPreview"
                                    class="img-circle elevation-2"
                                    style="width:120px;height:120px;object-fit:cover;cursor:pointer;">
                            </a>

                            <input type="file" id="avatarInput" name="avatar" hidden accept=".jpg,.jpeg,.png,.webp">
                        </div>

                        <div class="row">

                            {{-- FIRST NAME --}}
                            <div class="col-md-4 mb-3">
                                <label>First Name</label>
                                <input type="text" name="usr_first_name" class="form-control editable"
                                    value="{{ $user->usr_first_name }}" readonly>
                            </div>

                            {{-- MIDDLE NAME --}}
                            <div class="col-md-4 mb-3">
                                <label>Middle Name</label>
                                <input type="text" name="usr_middle_name" class="form-control editable"
                                    value="{{ $user->usr_middle_name }}" readonly>
                            </div>

                            {{-- LAST NAME --}}
                            <div class="col-md-4 mb-3">
                                <label>Last Name</label>
                                <input type="text" name="usr_last_name" class="form-control editable"
                                    value="{{ $user->usr_last_name }}" readonly>
                            </div>

                            {{-- BIRTHDATE --}}
                            <div class="col-md-6 mb-3">
                                <label>Birthdate</label>
                                <input type="date" name="usr_birth_date" class="form-control editable"
                                    value="{{ $user->usr_birth_date }}" readonly>
                            </div>

                            {{-- AGE (AUTO COMPUTE) --}}
                            <div class="col-md-6 mb-3">
                                <label>Age</label>
                                <input type="text" id="ageField" class="form-control"
                                    value="{{ \Carbon\Carbon::parse($user->usr_birth_date)->age }} years old" readonly>
                            </div>

                        </div>

                        {{-- BUTTONS --}}
                        <div class="text-right">
                            <button type="button" id="editBtn" class="btn btn-warning">
                                <span class="fa fa-edit"></span> Edit
                            </button>

                            <button type="button" id="cancelBtn" class="btn btn-secondary" style="display:none;">
                                <span class="fa fa-close"></span> Cancel
                            </button>

                            <button type="submit" id="saveBtn" class="btn btn-success" style="display:none;">
                                <span class="fa fa-save"></span> Save
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            {{-- ADDRESS SECTION --}}
            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-map-marker-alt text-warning mr-2"></i>
                        My Addresses
                        <span class="badge badge-success ml-1">{{ $addresses->where('uadd_active', 1)->count() }}</span>
                    </h5>
                    <button type="button" class="btn btn-success btn-sm ml-auto" data-toggle="modal"
                        data-target="#addAddressModal">
                        <i class="fas fa-plus mr-1"></i> Add Address
                    </button>
                </div>

                <div class="card-body">
                    @if ($addresses->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No addresses yet. Click <strong>Add Address</strong> to save your first
                                location.</p>
                            <button type="button" class="btn btn-outline-warning btn-sm" data-toggle="modal"
                                data-target="#addAddressModal">
                                <i class="fas fa-plus mr-1"></i> Add Address
                            </button>
                        </div>
                    @else
                        <div class="row">
                            @foreach ($addresses as $addr)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    {{-- Address Card --}}
                                    <div
                                        class="card h-100 {{ $addr->uadd_active ? 'border-success' : 'border-secondary' }}">
                                        <div
                                            class="card-header py-2 {{ $addr->uadd_active ? 'bg-success text-white' : 'bg-secondary text-white' }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="font-weight-bold">
                                                    <i class="fas fa-home mr-1"></i>
                                                    {{ $addr->add_name ?? 'Address #' . $loop->iteration }}
                                                </span>
                                                @if ($addr->uadd_active)
                                                    <span class="badge badge-light text-success">
                                                        <i class="fas fa-check-circle mr-1"></i>Active
                                                    </span>
                                                @else
                                                    <span class="badge badge-light text-secondary">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body py-2">
                                            @if ($addr->uadd_street)
                                                <p class="mb-1 small">
                                                    <i class="fas fa-road text-muted mr-1"></i>
                                                    {{ $addr->uadd_street }}
                                                </p>
                                            @endif

                                            @php
                                                $locationLine = collect([
                                                    $addr->uadd_barangay,
                                                    $addr->uadd_city,
                                                    $addr->uadd_province,
                                                ])
                                                    ->filter()
                                                    ->implode(', ');
                                            @endphp

                                            @if ($locationLine)
                                                <p class="mb-1 small">
                                                    <i class="fas fa-map-marked-alt text-muted mr-1"></i>
                                                    {{ $locationLine }}
                                                </p>
                                            @endif

                                            @if ($addr->uadd_region)
                                                <p class="mb-0 small text-muted">
                                                    <i class="fas fa-globe-asia mr-1"></i>
                                                    {{ $addr->uadd_region }}
                                                </p>
                                            @endif

                                            <p class="mb-1 small">
                                                <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                                {{ $addr->uadd_latitude }}, {{ $addr->uadd_longitude }}
                                            </p>
                                        </div>
                                        <div class="card-footer py-2">
                                            <button type="button" class="btn btn-outline-warning btn-sm btn-block"
                                                data-toggle="modal" data-target="#editAddressModal-{{ $addr->uadd_id }}">
                                                <i class="fas fa-edit mr-1"></i> Edit Address
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- EDIT ADDRESS MODAL --}}
                                <div class="modal fade" id="editAddressModal-{{ $addr->uadd_id }}" tabindex="-1"
                                    role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ url('account/address/edit') }}">
                                                @csrf
                                                <input type="hidden" name="uadd_id" value="{{ $addr->uadd_id }}">

                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit mr-2"></i> Edit Address
                                                    </h5>
                                                    <button type="button" class="close"
                                                        data-dismiss="modal">&times;</button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="row">

                                                        {{-- ADDRESS LABEL --}}
                                                        <div class="col-md-12 mb-3">
                                                            <label class="font-weight-bold">Address Label</label>
                                                            <select name="add_id" class="form-control">
                                                                <option value="">-- KEEP CURRENT --</option>
                                                                @foreach ($address_labels as $al)
                                                                    <option value="{{ $al->add_id }}"
                                                                        {{ $addr->add_id == $al->add_id ? 'selected' : '' }}>
                                                                        {{ $al->add_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        {{-- STREET --}}
                                                        <div class="col-md-12 mb-3">
                                                            <label class="font-weight-bold">Street / House No.</label>
                                                            <input type="text" name="street" class="form-control"
                                                                value="{{ $addr->uadd_street }}">
                                                        </div>

                                                        {{-- CURRENT LOCATION DISPLAY --}}
                                                        <div class="col-md-12 mb-3">
                                                            <label class="font-weight-bold">Current Location</label>
                                                            <div class="form-control bg-light text-muted">
                                                                <i class="fas fa-map-marker-alt text-warning mr-1"></i>
                                                                {{ collect([$addr->uadd_barangay, $addr->uadd_city, $addr->uadd_province, $addr->uadd_region])->filter()->implode(', ') ?:'—' }}
                                                            </div>
                                                            <small class="text-muted">Select new dropdowns below only if
                                                                you want to change the location.</small>
                                                        </div>

                                                        {{-- REGION --}}
                                                        <div class="col-md-3 mb-3">
                                                            <label class="font-weight-bold">Region</label>
                                                            <select id="edit_region_{{ $addr->uadd_id }}"
                                                                class="form-control">
                                                                <option value="">-- KEEP CURRENT --</option>
                                                                @foreach ($regions as $r)
                                                                    <option value="{{ $r->reg_id }}"
                                                                        data-name="{{ $r->reg_name }}">
                                                                        {{ $r->reg_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <input type="hidden" name="region"
                                                                id="edit_hidden_region_{{ $addr->uadd_id }}">
                                                        </div>

                                                        {{-- PROVINCE --}}
                                                        <div class="col-md-3 mb-3">
                                                            <label class="font-weight-bold">Province</label>
                                                            <select id="edit_province_{{ $addr->uadd_id }}"
                                                                class="form-control" disabled>
                                                                <option value="">-- SELECT PROVINCE --</option>
                                                            </select>
                                                            <input type="hidden" name="province"
                                                                id="edit_hidden_province_{{ $addr->uadd_id }}">
                                                        </div>

                                                        {{-- MUNICIPALITY --}}
                                                        <div class="col-md-3 mb-3">
                                                            <label class="font-weight-bold">Municipality / City</label>
                                                            <select id="edit_municipality_{{ $addr->uadd_id }}"
                                                                class="form-control" disabled>
                                                                <option value="">-- SELECT MUNICIPALITY --</option>
                                                            </select>
                                                            <input type="hidden" name="municipality"
                                                                id="edit_hidden_municipality_{{ $addr->uadd_id }}">
                                                        </div>

                                                        {{-- BARANGAY --}}
                                                        <div class="col-md-3 mb-3">
                                                            <label class="font-weight-bold">Barangay</label>
                                                            <select id="edit_barangay_{{ $addr->uadd_id }}"
                                                                class="form-control" disabled>
                                                                <option value="">-- SELECT BARANGAY --</option>
                                                            </select>
                                                            <input type="hidden" name="barangay"
                                                                id="edit_hidden_barangay_{{ $addr->uadd_id }}">
                                                        </div>

                                                        {{-- ACTIVE TOGGLE --}}
                                                        <div class="col-md-12 mt-1">
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input"
                                                                    id="edit_active_{{ $addr->uadd_id }}"
                                                                    name="uadd_active" value="1"
                                                                    {{ $addr->uadd_active ? 'checked' : '' }}>
                                                                <label class="custom-control-label font-weight-bold"
                                                                    for="edit_active_{{ $addr->uadd_id }}">
                                                                    Mark as Active Address
                                                                </label>
                                                            </div>
                                                        </div>

                                                        {{-- LATITUDE / LONGITUDE --}}
                                                        <div class="col-md-6 mb-3">
                                                            <label class="font-weight-bold">Latitude <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control edit-address-latitude"
                                                                id="edit_address_latitude_{{ $addr->uadd_id }}" name="latitude"
                                                                value="{{ $addr->uadd_latitude }}" placeholder="Latitude">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="font-weight-bold">Longitude <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control edit-address-longitude"
                                                                id="edit_address_longitude_{{ $addr->uadd_id }}" name="longitude"
                                                                value="{{ $addr->uadd_longitude }}" placeholder="Longitude">
                                                        </div>

                                                        <div class="col-md-12 mb-3">
                                                            <button type="button" class="btn btn-info btn-sm edit-address-pin-map-btn"
                                                                data-id="{{ $addr->uadd_id }}">
                                                                <span class="fa fa-map-marker-alt"></span> Pin Map
                                                            </button>
                                                            <small class="text-muted ml-2">Click the map to change this address's location</small>
                                                        </div>

                                                        <div class="col-md-12 mb-3 edit-address-map-container"
                                                            id="editAddressMapContainer-{{ $addr->uadd_id }}" style="display:none;">
                                                            <div id="editAddressMap-{{ $addr->uadd_id }}"
                                                                style="width:100%;height:350px;border:1px solid #ccc;border-radius:4px;"></div>
                                                            <small class="text-muted">
                                                                <span class="fa fa-info-circle"></span>
                                                                Click anywhere on the map to repin the location. You can also drag the marker.
                                                            </small>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">
                                                        <i class="fas fa-times mr-1"></i> Cancel
                                                    </button>
                                                    <button type="submit" id="saveBtn" class="btn btn-warning">
                                                        <span class="fa fa-save"></span> Update Address
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                {{-- END EDIT MODAL --}}
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ADD ADDRESS MODAL --}}
    <div class="modal fade" id="addAddressModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ url('account/address/add') }}">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-plus mr-2 text-white"></i> Add New Address
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="row">

                            {{-- ADDRESS LABEL --}}
                            <div class="col-md-12 mb-3">
                                <label class="font-weight-bold">Address Label</label>
                                <select name="add_id" class="form-control">
                                    @foreach ($address_labels as $al)
                                        <option value="{{ $al->add_id }}">{{ $al->add_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- STREET --}}
                            <div class="col-md-12 mb-3">
                                <label class="font-weight-bold">Street / House No.</label>
                                <input type="text" name="street" class="form-control"
                                    placeholder="e.g. 123 Rizal Street">
                            </div>

                            {{-- REGION --}}
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Region</label>
                                <select id="modal_add_region" class="form-control">
                                    <option value="">-- SELECT REGION --</option>
                                    @foreach ($regions as $r)
                                        <option value="{{ $r->reg_id }}" data-name="{{ $r->reg_name }}">
                                            {{ $r->reg_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="region" id="modal_add_hidden_region">
                            </div>

                            {{-- PROVINCE --}}
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Province</label>
                                <select id="modal_add_province" class="form-control" disabled>
                                    <option value="">-- SELECT PROVINCE --</option>
                                </select>
                                <input type="hidden" name="province" id="modal_add_hidden_province">
                            </div>

                            {{-- MUNICIPALITY --}}
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Municipality / City</label>
                                <select id="modal_add_municipality" class="form-control" disabled>
                                    <option value="">-- SELECT MUNICIPALITY --</option>
                                </select>
                                <input type="hidden" name="municipality" id="modal_add_hidden_municipality">
                            </div>

                            {{-- BARANGAY --}}
                            <div class="col-md-3 mb-3">
                                <label class="font-weight-bold">Barangay</label>
                                <select id="modal_add_barangay" class="form-control" disabled>
                                    <option value="">-- SELECT BARANGAY --</option>
                                </select>
                                <input type="hidden" name="barangay" id="modal_add_hidden_barangay">
                            </div>

                            {{-- LATITUDE / LONGITUDE --}}
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Latitude <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_address_latitude" name="latitude"
                                    placeholder="Latitude">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold">Longitude <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_address_longitude" name="longitude"
                                    placeholder="Longitude">
                            </div>

                            <div class="col-md-12 mb-3">
                                <button type="button" class="btn btn-info btn-sm" id="addAddressPinMapBtn">
                                    <span class="fa fa-map-marker-alt"></span> Pin Map
                                </button>
                                <small class="text-muted ml-2">Click the map to set this address's location</small>
                            </div>

                            <div class="col-md-12 mb-3" id="addAddressMapContainer" style="display:none;">
                                <div id="addAddressMap" style="width:100%;height:350px;border:1px solid #ccc;border-radius:4px;"></div>
                                <small class="text-muted">
                                    <span class="fa fa-info-circle"></span>
                                    Click anywhere on the map to pin the location. You can also drag the marker.
                                </small>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times mr-1"></i> Cancel
                        </button>
                        <button type="submit" id="saveBtn" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const editableFields = document.querySelectorAll('.editable');

        editBtn.addEventListener('click', () => {
            editableFields.forEach(el => el.removeAttribute('readonly'));
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        });

        cancelBtn.addEventListener('click', () => {
            location.reload();
        });
    </script>

    <script>
        const birthdateInput = document.querySelector('input[name="usr_birth_date"]');
        const ageField = document.getElementById('ageField');

        birthdateInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();

            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();

            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            ageField.value = age + " years old";
        });
    </script>

    <script>
        const avatarLink = document.getElementById('avatarLink');
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreview = document.getElementById('avatarPreview');

        let editMode = false;

        editBtn.addEventListener('click', () => {
            editMode = true;

            avatarLink.removeAttribute('href');
            avatarLink.removeAttribute('download');
        });

        avatarPreview.addEventListener('click', function(e) {
            if (editMode) {
                e.preventDefault();
                avatarInput.click();
            }
        });

        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const baseUrl = window.location.origin;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            function resetSelect(el, placeholder) {
                el.innerHTML = `<option value="">${placeholder}</option>`;
                el.disabled = true;
            }

            async function loadOptions(el, endpoint, textKey, valueKey, placeholder) {
                el.innerHTML = '<option value="">Loading...</option>';
                try {
                    const res = await fetch(`${baseUrl}${endpoint}`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    const data = await res.json();
                    el.innerHTML = `<option value="">${placeholder}</option>`;
                    if (data && data.length > 0) {
                        data.forEach(item => {
                            el.insertAdjacentHTML('beforeend',
                                `<option value="${item[valueKey]}" data-name="${item[textKey]}">${item[textKey]}</option>`
                            );
                        });
                        el.disabled = false;
                    } else {
                        el.innerHTML = `<option value="">No options available</option>`;
                    }
                } catch (e) {
                    el.innerHTML = `<option value="">Error loading</option>`;
                }
            }

            // ---- ADD ADDRESS MODAL ----
            const maRegion = document.getElementById('modal_add_region');
            const maProv = document.getElementById('modal_add_province');
            const maMuni = document.getElementById('modal_add_municipality');
            const maBrgy = document.getElementById('modal_add_barangay');
            const maHR = document.getElementById('modal_add_hidden_region');
            const maHP = document.getElementById('modal_add_hidden_province');
            const maHM = document.getElementById('modal_add_hidden_municipality');
            const maHB = document.getElementById('modal_add_hidden_barangay');

            maRegion.addEventListener('change', function() {
                maHR.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                resetSelect(maProv, '-- SELECT PROVINCE --');
                resetSelect(maMuni, '-- SELECT MUNICIPALITY --');
                resetSelect(maBrgy, '-- SELECT BARANGAY --');
                maHP.value = maHM.value = maHB.value = '';
                if (this.value) loadOptions(maProv, `/locations/${this.value}/provinces`, 'prov_name',
                    'prov_id', '-- SELECT PROVINCE --');
            });
            maProv.addEventListener('change', function() {
                maHP.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                resetSelect(maMuni, '-- SELECT MUNICIPALITY --');
                resetSelect(maBrgy, '-- SELECT BARANGAY --');
                maHM.value = maHB.value = '';
                if (this.value) loadOptions(maMuni, `/locations/${this.value}/municipalities`, 'mun_name',
                    'mun_id', '-- SELECT MUNICIPALITY --');
            });
            maMuni.addEventListener('change', function() {
                maHM.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                resetSelect(maBrgy, '-- SELECT BARANGAY --');
                maHB.value = '';
                if (this.value) loadOptions(maBrgy, `/locations/${this.value}/barangays`, 'brg_name',
                    'brg_id', '-- SELECT BARANGAY --');
            });
            maBrgy.addEventListener('change', function() {
                maHB.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
            });

            // Reset Add modal on close
            document.getElementById('addAddressModal').addEventListener('hidden.bs.modal', function() {
                this.querySelector('form').reset();
                resetSelect(maProv, '-- SELECT PROVINCE --');
                resetSelect(maMuni, '-- SELECT MUNICIPALITY --');
                resetSelect(maBrgy, '-- SELECT BARANGAY --');
                maHR.value = maHP.value = maHM.value = maHB.value = '';
            });

            // ---- EDIT ADDRESS MODALS (one per address, identified by uadd_id) ----
            @foreach ($addresses as $addr)
                (function() {
                    const id = '{{ $addr->uadd_id }}';
                    const edReg = document.getElementById(`edit_region_${id}`);
                    const edProv = document.getElementById(`edit_province_${id}`);
                    const edMuni = document.getElementById(`edit_municipality_${id}`);
                    const edBrgy = document.getElementById(`edit_barangay_${id}`);
                    const edHR = document.getElementById(`edit_hidden_region_${id}`);
                    const edHP = document.getElementById(`edit_hidden_province_${id}`);
                    const edHM = document.getElementById(`edit_hidden_municipality_${id}`);
                    const edHB = document.getElementById(`edit_hidden_barangay_${id}`);

                    edReg.addEventListener('change', function() {
                        edHR.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                        resetSelect(edProv, '-- SELECT PROVINCE --');
                        resetSelect(edMuni, '-- SELECT MUNICIPALITY --');
                        resetSelect(edBrgy, '-- SELECT BARANGAY --');
                        edHP.value = edHM.value = edHB.value = '';
                        if (this.value) loadOptions(edProv, `/locations/${this.value}/provinces`,
                            'prov_name', 'prov_id', '-- SELECT PROVINCE --');
                    });
                    edProv.addEventListener('change', function() {
                        edHP.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                        resetSelect(edMuni, '-- SELECT MUNICIPALITY --');
                        resetSelect(edBrgy, '-- SELECT BARANGAY --');
                        edHM.value = edHB.value = '';
                        if (this.value) loadOptions(edMuni, `/locations/${this.value}/municipalities`,
                            'mun_name', 'mun_id', '-- SELECT MUNICIPALITY --');
                    });
                    edMuni.addEventListener('change', function() {
                        edHM.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                        resetSelect(edBrgy, '-- SELECT BARANGAY --');
                        edHB.value = '';
                        if (this.value) loadOptions(edBrgy, `/locations/${this.value}/barangays`,
                            'brg_name', 'brg_id', '-- SELECT BARANGAY --');
                    });
                    edBrgy.addEventListener('change', function() {
                        edHB.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
                    });

                    // Reset on modal close
                    document.getElementById(`editAddressModal-${id}`).addEventListener('hidden.bs.modal',
                        function() {
                            resetSelect(edProv, '-- SELECT PROVINCE --');
                            resetSelect(edMuni, '-- SELECT MUNICIPALITY --');
                            resetSelect(edBrgy, '-- SELECT BARANGAY --');
                            edReg.value = '';
                            edHR.value = edHP.value = edHM.value = edHB.value = '';
                        });
                })();
            @endforeach

        });
    </script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Add Address Map Logic --}}
    <script>
        let addAddressMap = null;
        let addAddressMarker = null;
        let addAddressMapInitialized = false;

        document.getElementById('addAddressPinMapBtn').addEventListener('click', function() {
            const container = document.getElementById('addAddressMapContainer');

            if (container.style.display === 'none') {
                container.style.display = 'block';

                if (!addAddressMapInitialized) {
                    initAddAddressMap();
                } else {
                    setTimeout(() => addAddressMap.invalidateSize(), 100);
                }
            } else {
                container.style.display = 'none';
            }
        });

        function initAddAddressMap() {
            addAddressMapInitialized = true;

            const defaultLat = 7.1907;
            const defaultLng = 125.4553;

            addAddressMap = L.map('addAddressMap').setView([defaultLat, defaultLng], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(addAddressMap);

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        addAddressMap.setView([position.coords.latitude, position.coords.longitude], 15);
                    },
                    function() {
                        // Denied - stay on default
                    }
                );
            }

            // Give Leaflet a moment to size correctly since it was just unhidden
            setTimeout(() => addAddressMap.invalidateSize(), 100);

            addAddressMap.on('click', function(e) {
                placeAddAddressMarker(e.latlng);
            });
        }

        function placeAddAddressMarker(latlng) {
            if (addAddressMarker) addAddressMap.removeLayer(addAddressMarker);

            addAddressMarker = L.marker(latlng, { draggable: true }).addTo(addAddressMap);
            addAddressMarker.bindPopup('📍 Address Location').openPopup();

            updateAddAddressCoordinates(latlng);

            addAddressMarker.on('dragend', function(event) {
                updateAddAddressCoordinates(event.target.getLatLng());
            });
        }

        function updateAddAddressCoordinates(latlng) {
            document.getElementById('add_address_latitude').value = latlng.lat.toFixed(7);
            document.getElementById('add_address_longitude').value = latlng.lng.toFixed(7);
        }

        // Reset when modal closes
        $('#addAddressModal').on('hidden.bs.modal', function() {
            document.getElementById('add_address_latitude').value = '';
            document.getElementById('add_address_longitude').value = '';
            document.getElementById('addAddressMapContainer').style.display = 'none';

            if (addAddressMarker && addAddressMap) {
                addAddressMap.removeLayer(addAddressMarker);
                addAddressMarker = null;
            }
            if (addAddressMap) {
                addAddressMap.remove();
                addAddressMap = null;
            }
            addAddressMapInitialized = false;
        });

        // Manual validation on submit - required doesn't work on fields
        // hidden inside display:none containers, so we check manually
        document.querySelector('#addAddressModal form').addEventListener('submit', function(e) {
            const lat = document.getElementById('add_address_latitude').value;
            const lng = document.getElementById('add_address_longitude').value;

            if (!lat || !lng) {
                e.preventDefault();

                // Open the map so the user can immediately pin their location
                const container = document.getElementById('addAddressMapContainer');
                container.style.display = 'block';

                if (!addAddressMapInitialized) {
                    initAddAddressMap();
                } else {
                    setTimeout(() => addAddressMap.invalidateSize(), 100);
                }

                alert('Please pin your address location on the map before saving.');
            }
        });
    </script>

    {{-- Edit Address Map Logic --}}
    <script>
        const editAddressMapInstances = {};

        document.querySelectorAll('.edit-address-pin-map-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const container = document.getElementById('editAddressMapContainer-' + id);

                if (container.style.display === 'none') {
                    container.style.display = 'block';

                    if (!editAddressMapInstances[id]) {
                        initEditAddressMap(id);
                    } else {
                        setTimeout(() => editAddressMapInstances[id].map.invalidateSize(), 100);
                    }
                } else {
                    container.style.display = 'none';
                }
            });
        });

        function initEditAddressMap(id) {
            const latInput = document.getElementById('edit_address_latitude_' + id);
            const lngInput = document.getElementById('edit_address_longitude_' + id);

            const existingLat = parseFloat(latInput.value) || 7.1907;
            const existingLng = parseFloat(lngInput.value) || 125.4553;

            const map = L.map('editAddressMap-' + id).setView([existingLat, existingLng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            let marker = null;

            // Only auto-pin if this address already has coordinates
            if (latInput.value && lngInput.value) {
                marker = L.marker([existingLat, existingLng], { draggable: true }).addTo(map);
                marker.bindPopup('📍 Address Location').openPopup();
                marker.on('dragend', function(event) {
                    updateEditAddressCoordinates(id, event.target.getLatLng());
                });
            }

            map.on('click', function(e) {
                if (marker) map.removeLayer(marker);

                marker = L.marker(e.latlng, { draggable: true }).addTo(map);
                marker.bindPopup('📍 Address Location').openPopup();
                updateEditAddressCoordinates(id, e.latlng);

                marker.on('dragend', function(event) {
                    updateEditAddressCoordinates(id, event.target.getLatLng());
                });
            });

            editAddressMapInstances[id] = { map, marker };
        }

        function updateEditAddressCoordinates(id, latlng) {
            document.getElementById('edit_address_latitude_' + id).value = latlng.lat.toFixed(7);
            document.getElementById('edit_address_longitude_' + id).value = latlng.lng.toFixed(7);
        }

        document.querySelectorAll('[id^="editAddressModal-"]').forEach(function(modal) {
            $(modal).on('hidden.bs.modal', function() {
                const id = this.id.replace('editAddressModal-', '');
                const container = document.getElementById('editAddressMapContainer-' + id);

                if (container) container.style.display = 'none';

                if (editAddressMapInstances[id]) {
                    editAddressMapInstances[id].map.remove();
                    delete editAddressMapInstances[id];
                }
            });
        });
    </script>
@endsection