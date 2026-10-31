@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Account Information</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Account</li>
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

            {{-- PERSONAL INFORMATION --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-user-alt text-warning me-2"></i>
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
                                    class="rounded-circle shadow object-fit-cover" width="120" height="120"
                                    role="button" alt="Profile image">
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
                        <div class="text-end">
                            <button type="button" id="editBtn" class="btn btn-warning">
                                <span class="fa fa-edit"></span> Edit
                            </button>

                            <button type="button" id="cancelBtn" class="btn btn-secondary d-none">
                                <span class="fa fa-close"></span> Cancel
                            </button>

                            <button type="submit" id="saveBtn" class="btn btn-success d-none">
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
                        <i class="fas fa-map-marker-alt text-warning me-2"></i>
                        My Addresses
                        <span class="badge text-bg-success ms-1">{{ $addresses->where('uadd_active', 1)->count() }}</span>
                    </h5>
                    <button type="button" class="btn btn-success btn-sm ms-auto" data-bs-toggle="modal"
                        data-bs-target="#addAddressModal">
                        <i class="fas fa-plus me-1"></i> Add Address
                    </button>
                </div>

                <div class="card-body">
                    @if ($addresses->isEmpty())
                        <div class="text-center py-4">
                            <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No addresses yet. Click <strong>Add Address</strong> to save your first
                                location.</p>
                            <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal"
                                data-bs-target="#addAddressModal">
                                <i class="fas fa-plus me-1"></i> Add Address
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
                                                <span class="fw-bold">
                                                    <i class="fas fa-home me-1"></i>
                                                    {{ $addr->add_name ?? 'Address #' . $loop->iteration }}
                                                </span>
                                                @if ($addr->uadd_active)
                                                    <span class="badge bg-light text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Active
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-secondary">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body py-2">
                                            @if ($addr->uadd_street)
                                                <p class="mb-1 small">
                                                    <i class="fas fa-road text-muted me-1"></i>
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
                                                    <i class="fas fa-map-marked-alt text-muted me-1"></i>
                                                    {{ $locationLine }}
                                                </p>
                                            @endif

                                            @if ($addr->uadd_region)
                                                <p class="mb-0 small text-muted">
                                                    <i class="fas fa-globe-asia me-1"></i>
                                                    {{ $addr->uadd_region }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="card-footer py-2">
                                            <button type="button" class="btn btn-outline-warning btn-sm w-100"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editAddressModal-{{ $addr->uadd_id }}">
                                                <i class="fas fa-edit me-1"></i> Edit Address
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- EDIT ADDRESS MODALS (outside the cards so the backdrop stacks correctly) --}}
    @foreach ($addresses as $addr)
        <div class="modal fade" id="editAddressModal-{{ $addr->uadd_id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="{{ url('account/address/edit') }}">
                        @csrf
                        <input type="hidden" name="uadd_id" value="{{ $addr->uadd_id }}">

                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">
                                <i class="fas fa-edit me-2"></i> Edit Address
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">

                                {{-- ADDRESS LABEL --}}
                                <div class="col-md-12 mb-3">
                                    <label class="fw-bold">Address Label</label>
                                    <select name="add_id" class="form-select">
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
                                    <label class="fw-bold">Street / House No.</label>
                                    <input type="text" name="street" class="form-control"
                                        value="{{ $addr->uadd_street }}">
                                </div>

                                {{-- CURRENT LOCATION DISPLAY --}}
                                <div class="col-md-12 mb-3">
                                    <label class="fw-bold">Current Location</label>
                                    <div class="form-control bg-light text-muted">
                                        <i class="fas fa-map-marker-alt text-warning me-1"></i>
                                        {{ collect([$addr->uadd_barangay, $addr->uadd_city, $addr->uadd_province, $addr->uadd_region])->filter()->implode(', ') ?:'—' }}
                                    </div>
                                    <small class="text-muted">Select new dropdowns below only if you want to change the
                                        location.</small>
                                </div>

                                {{-- REGION --}}
                                <div class="col-md-3 mb-3">
                                    <label class="fw-bold">Region</label>
                                    <select id="edit_region_{{ $addr->uadd_id }}" class="form-select">
                                        <option value="">-- KEEP CURRENT --</option>
                                        @foreach ($regions as $r)
                                            <option value="{{ $r->reg_id }}" data-name="{{ $r->reg_name }}">
                                                {{ $r->reg_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="region" id="edit_hidden_region_{{ $addr->uadd_id }}">
                                </div>

                                {{-- PROVINCE --}}
                                <div class="col-md-3 mb-3">
                                    <label class="fw-bold">Province</label>
                                    <select id="edit_province_{{ $addr->uadd_id }}" class="form-select" disabled>
                                        <option value="">-- SELECT PROVINCE --</option>
                                    </select>
                                    <input type="hidden" name="province"
                                        id="edit_hidden_province_{{ $addr->uadd_id }}">
                                </div>

                                {{-- MUNICIPALITY --}}
                                <div class="col-md-3 mb-3">
                                    <label class="fw-bold">Municipality / City</label>
                                    <select id="edit_municipality_{{ $addr->uadd_id }}" class="form-select" disabled>
                                        <option value="">-- SELECT MUNICIPALITY --</option>
                                    </select>
                                    <input type="hidden" name="municipality"
                                        id="edit_hidden_municipality_{{ $addr->uadd_id }}">
                                </div>

                                {{-- BARANGAY --}}
                                <div class="col-md-3 mb-3">
                                    <label class="fw-bold">Barangay</label>
                                    <select id="edit_barangay_{{ $addr->uadd_id }}" class="form-select" disabled>
                                        <option value="">-- SELECT BARANGAY --</option>
                                    </select>
                                    <input type="hidden" name="barangay"
                                        id="edit_hidden_barangay_{{ $addr->uadd_id }}">
                                </div>

                                {{-- ACTIVE TOGGLE --}}
                                <div class="col-md-12 mt-1">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" class="form-check-input" role="switch"
                                            id="edit_active_{{ $addr->uadd_id }}" name="uadd_active" value="1"
                                            {{ $addr->uadd_active ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold"
                                            for="edit_active_{{ $addr->uadd_id }}">
                                            Mark as Active Address
                                        </label>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <span class="fa fa-save"></span> Update Address
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    {{-- ADD ADDRESS MODAL --}}
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ url('account/address/add') }}">
                    @csrf
                    <div class="modal-header bg-success">
                        <h5 class="modal-title text-white">
                            <i class="fas fa-plus me-2 text-white"></i> Add New Address
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">

                            {{-- ADDRESS LABEL --}}
                            <div class="col-md-12 mb-3">
                                <label class="fw-bold">Address Label</label>
                                <select name="add_id" class="form-select">
                                    @foreach ($address_labels as $al)
                                        <option value="{{ $al->add_id }}">{{ $al->add_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- STREET --}}
                            <div class="col-md-12 mb-3">
                                <label class="fw-bold">Street / House No.</label>
                                <input type="text" name="street" class="form-control"
                                    placeholder="e.g. 123 Rizal Street">
                            </div>

                            {{-- REGION --}}
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Region</label>
                                <select id="modal_add_region" class="form-select">
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
                                <label class="fw-bold">Province</label>
                                <select id="modal_add_province" class="form-select" disabled>
                                    <option value="">-- SELECT PROVINCE --</option>
                                </select>
                                <input type="hidden" name="province" id="modal_add_hidden_province">
                            </div>

                            {{-- MUNICIPALITY --}}
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Municipality / City</label>
                                <select id="modal_add_municipality" class="form-select" disabled>
                                    <option value="">-- SELECT MUNICIPALITY --</option>
                                </select>
                                <input type="hidden" name="municipality" id="modal_add_hidden_municipality">
                            </div>

                            {{-- BARANGAY --}}
                            <div class="col-md-3 mb-3">
                                <label class="fw-bold">Barangay</label>
                                <select id="modal_add_barangay" class="form-select" disabled>
                                    <option value="">-- SELECT BARANGAY --</option>
                                </select>
                                <input type="hidden" name="barangay" id="modal_add_hidden_barangay">
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Address
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page_scripts')
    <script>
        // ---- EDIT / CANCEL PERSONAL INFO ----
        const editBtn = document.getElementById('editBtn');
        const saveBtn = document.getElementById('saveBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const editableFields = document.querySelectorAll('.editable');

        const avatarLink = document.getElementById('avatarLink');
        const avatarInput = document.getElementById('avatarInput');
        const avatarPreview = document.getElementById('avatarPreview');
        let editMode = false;

        editBtn.addEventListener('click', () => {
            editMode = true;
            editableFields.forEach(el => el.removeAttribute('readonly'));
            editBtn.classList.add('d-none');
            saveBtn.classList.remove('d-none');
            cancelBtn.classList.remove('d-none');

            avatarLink.removeAttribute('href');
            avatarLink.removeAttribute('download');
        });

        cancelBtn.addEventListener('click', () => {
            location.reload();
        });

        // ---- AGE AUTO-COMPUTE ----
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

        // ---- AVATAR ----
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

        // ---- LOCATION DROPDOWNS ----
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
@endsection