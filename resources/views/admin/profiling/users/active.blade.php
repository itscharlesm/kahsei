@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Users</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Profiling</li>
                        <li class="breadcrumb-item active">Users</li>
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
            <div class="card">
                <div class="card-body overflow-auto">
                    <div class="row">
                        <div class="col-md-12">
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                                <a class="btn btn-danger mb-3" href="{{ url('admin/profiling/users/deleted') }}">
                                    <span class="fa fa-archive"></span> Deleted Users
                                </a>

                                <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal"
                                    data-bs-target="#addUserModal">
                                    <span class="fa fa-plus"></span> Add User
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <!-- Table Column -->
                        <div class="col-lg-9 col-md-7">
                            <form method="GET" action="{{ url('admin/profiling/users/active') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search users..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fa fa-search"></span> Search
                                    </button>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center">Name</th>
                                        <th class="align-middle text-center" width="130px">Role(s)</th>
                                        <th class="align-middle text-center" width="110px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td class="align-middle text-start">
                                                {{ $user->usr_last_name }}, {{ $user->usr_first_name }}
                                                {{ $user->usr_middle_name }}
                                                <br />
                                                <small>{{ $user->usr_email }}</small>
                                                <br />
                                                <em><small>Last login: {{ getLastLogin($user->usr_id) }}</small></em>
                                            </td>
                                            <td class="align-middle text-center">
                                                @if (!empty($user->roles))
                                                    @foreach (explode(', ', $user->roles) as $role)
                                                        <span class="badge bg-success">{{ $role }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="badge bg-danger">No Role Assigned</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">
                                                <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateRoleModal-{{ $user->usr_id }}">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                <a class="btn btn-info btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal" data-bs-target="#resetModal-{{ $user->usr_id }}">
                                                    <span class="fa fa-key"></span>
                                                </a>
                                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                    <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $user->usr_id }}">
                                                        <span class="fa fa-trash"></span>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Role Info Column -->
                        <div class="col-lg-3 col-md-5">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <strong><i class="fa fa-info-circle"></i> Role Information</strong>
                                </div>
                                <div class="card-body overflow-auto">
                                    @foreach ($roles as $role)
                                        <div class="mb-3">
                                            <h6 class="text-dark mb-1">
                                                <i class="fa fa-user-tag"></i> {{ $role->rol_name }}
                                            </h6>
                                            <p class="text-muted small mb-0">
                                                {{ $role->rol_description ?? 'No description available' }}
                                            </p>
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

    {{-- Add User Modal --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ url('admin/profiling/users/add') }}" method="POST">
                    @csrf
                    {{-- Hidden fields (filled by the location dropdowns) --}}
                    <input type="hidden" name="region" id="add_hidden_region" value="">
                    <input type="hidden" name="province" id="add_hidden_province" value="">
                    <input type="hidden" name="municipality" id="add_hidden_municipality" value="">
                    <input type="hidden" name="barangay" id="add_hidden_barangay" value="">

                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addUserModalLabel">
                            <span class="fa fa-plus text-white"></span> Add User
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            {{-- First Name --}}
                            <div class="col-md-4 mb-3">
                                <label for="add_first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_first_name" name="usr_first_name"
                                    placeholder="First Name" required>
                            </div>

                            {{-- Middle Name --}}
                            <div class="col-md-4 mb-3">
                                <label for="add_middle_name">Middle Name</label>
                                <input type="text" class="form-control" id="add_middle_name" name="usr_middle_name"
                                    placeholder="Middle Name">
                            </div>

                            {{-- Last Name --}}
                            <div class="col-md-4 mb-3">
                                <label for="add_last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="add_last_name" name="usr_last_name"
                                    placeholder="Last Name" required>
                            </div>

                            {{-- Email --}}
                            <div class="col-md-6 mb-3">
                                <label for="add_email">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="add_email" name="usr_email" placeholder="Email"
                                    required>
                            </div>

                            {{-- Mobile --}}
                            <div class="col-md-6 mb-3">
                                <label for="add_mobile">
                                    Mobile Number
                                    <small class="text-muted">(9123456789)</small>
                                </label>
                                <input type="number" class="form-control" id="add_mobile" name="usr_mobile"
                                    placeholder="Mobile Number" oninput="this.value=this.value.slice(0,10)">
                            </div>

                            {{-- Street --}}
                            <div class="col-md-12 mb-3">
                                <label for="add_street">Street</label>
                                <input type="text" class="form-control" id="add_street" name="street"
                                    placeholder="Street / House No. / Bldg.">
                            </div>

                            {{-- Region --}}
                            <div class="col-md-3 mb-3">
                                <label for="add_region">Region</label>
                                <select id="add_region" class="form-select">
                                    <option value="">-- SELECT REGION --</option>
                                    @foreach ($regions as $r)
                                        <option value="{{ $r->reg_id }}" data-name="{{ $r->reg_name }}">
                                            {{ $r->reg_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Province --}}
                            <div class="col-md-3 mb-3">
                                <label for="add_province">Province</label>
                                <select id="add_province" class="form-select" disabled>
                                    <option value="">-- SELECT PROVINCE --</option>
                                </select>
                            </div>

                            {{-- Municipality --}}
                            <div class="col-md-3 mb-3">
                                <label for="add_municipality">City/Municipality</label>
                                <select id="add_municipality" class="form-select" disabled>
                                    <option value="">-- SELECT MUNICIPALITY --</option>
                                </select>
                            </div>

                            {{-- Barangay --}}
                            <div class="col-md-3 mb-3">
                                <label for="add_barangay">Barangay</label>
                                <select id="add_barangay" class="form-select" disabled>
                                    <option value="">-- SELECT BARANGAY --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($users as $user)
        {{-- Update User Modal --}}
        <div class="modal fade" id="updateRoleModal-{{ $user->usr_id }}" tabindex="-1"
            aria-labelledby="updateRoleModalLabel-{{ $user->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('admin/profiling/users/update/user', $user->usr_id) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="updateRoleModalLabel-{{ $user->usr_id }}">
                                Update User
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Name</label>
                                <div class="form-control-plaintext">
                                    {{ $user->usr_first_name }} {{ $user->usr_last_name }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="roles-{{ $user->usr_id }}">Roles <span class="text-danger">*</span></label>
                                <select class="select3 w-100" id="roles-{{ $user->usr_id }}" multiple="multiple"
                                    data-placeholder="Select Role(s)" name="roles[]">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->rol_id }}" @if (isset($user->roles) && in_array($role->rol_name, explode(', ', $user->roles))) selected @endif>
                                            {{ $role->rol_name }}
                                        </option>
                                    @endforeach
                                </select>
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

        {{-- Reset Password Modal --}}
        <div class="modal fade" id="resetModal-{{ $user->usr_id }}" tabindex="-1"
            aria-labelledby="resetModalLabel-{{ $user->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@users_reset_password', [$user->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="resetModalLabel-{{ $user->usr_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to RESET this user's password to
                                <strong>123456</strong>?
                            </p>
                            <small>{{ $user->usr_first_name }}
                                {{ $user->usr_last_name }}<br>{{ $user->usr_email }}</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-info">
                                <span class="fa fa-refresh"></span> Confirm Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Delete Modal --}}
        <div class="modal fade" id="deleteModal-{{ $user->usr_id }}" tabindex="-1"
            aria-labelledby="deleteModalLabel-{{ $user->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@users_delete', [$user->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="deleteModalLabel-{{ $user->usr_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>DELETE</strong> user
                                <strong>{{ $user->usr_first_name }}
                                    {{ $user->usr_last_name }}</strong>?
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

    {{-- Location dropdowns (Add User) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addRegionSel = document.getElementById('add_region');
            const addProvSel = document.getElementById('add_province');
            const addMuniSel = document.getElementById('add_municipality');
            const addBrgySel = document.getElementById('add_barangay');

            const addHiddenRegion = document.getElementById('add_hidden_region');
            const addHiddenProv = document.getElementById('add_hidden_province');
            const addHiddenMuni = document.getElementById('add_hidden_municipality');
            const addHiddenBrgy = document.getElementById('add_hidden_barangay');

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const baseUrl = window.location.origin;

            function addResetSelect(el, placeholder) {
                el.innerHTML = `<option value="">${placeholder}</option>`;
                el.disabled = true;
            }

            async function addLoadOptions(el, endpoint, textKey, valueKey, placeholder) {
                el.innerHTML = '<option value="">Loading...</option>';
                try {
                    const response = await fetch(`${baseUrl}${endpoint}`, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!response.ok) throw new Error(`HTTP ${response.status}`);
                    const data = await response.json();
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
                } catch (err) {
                    console.error(`Error loading ${el.id}:`, err);
                    el.innerHTML = `<option value="">Error loading data</option>`;
                }
            }

            addRegionSel.addEventListener('change', function () {
                const regionId = this.value;
                addHiddenRegion.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';

                addResetSelect(addProvSel, '-- SELECT PROVINCE --');
                addResetSelect(addMuniSel, '-- SELECT MUNICIPALITY --');
                addResetSelect(addBrgySel, '-- SELECT BARANGAY --');
                addHiddenProv.value = '';
                addHiddenMuni.value = '';
                addHiddenBrgy.value = '';

                if (regionId) {
                    addLoadOptions(addProvSel, `/locations/${regionId}/provinces`, 'prov_name', 'prov_id',
                        '-- SELECT PROVINCE --');
                }
            });

            addProvSel.addEventListener('change', function () {
                const provId = this.value;
                addHiddenProv.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';

                addResetSelect(addMuniSel, '-- SELECT MUNICIPALITY --');
                addResetSelect(addBrgySel, '-- SELECT BARANGAY --');
                addHiddenMuni.value = '';
                addHiddenBrgy.value = '';

                if (provId) {
                    addLoadOptions(addMuniSel, `/locations/${provId}/municipalities`, 'mun_name', 'mun_id',
                        '-- SELECT MUNICIPALITY --');
                }
            });

            addMuniSel.addEventListener('change', function () {
                const muniId = this.value;
                addHiddenMuni.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';

                addResetSelect(addBrgySel, '-- SELECT BARANGAY --');
                addHiddenBrgy.value = '';

                if (muniId) {
                    addLoadOptions(addBrgySel, `/locations/${muniId}/barangays`, 'brg_name', 'brg_id',
                        '-- SELECT BARANGAY --');
                }
            });

            addBrgySel.addEventListener('change', function () {
                addHiddenBrgy.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
            });

            // Reset modal fields on close
            document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
                this.querySelector('form').reset();
                addResetSelect(addProvSel, '-- SELECT PROVINCE --');
                addResetSelect(addMuniSel, '-- SELECT MUNICIPALITY --');
                addResetSelect(addBrgySel, '-- SELECT BARANGAY --');
                addHiddenRegion.value = '';
                addHiddenProv.value = '';
                addHiddenMuni.value = '';
                addHiddenBrgy.value = '';
            });
        });
    </script>
@endsection