@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Board of Members</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Profiling</li>
                        <li class="breadcrumb-item active">Board of Members</li>
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

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-body overflow-auto">
                    <div class="row">
                        <div class="col-md-12">
                            @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                                <a class="btn btn-danger mb-3" href="{{ url('profiling/board-of-members/deleted') }}">
                                    <span class="fa fa-archive"></span> Deleted Members
                                </a>

                                <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal"
                                    data-bs-target="#addBoardModal">
                                    <span class="fa fa-plus"></span> Add Member
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <form method="GET" action="{{ url('profiling/board-of-members/active') }}" class="mb-3">
                                <div class="input-group">
                                    <input type="text" name="search" id="searchInput" class="form-control"
                                        placeholder="Search board members..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <span class="fa fa-search"></span> Search
                                    </button>
                                </div>
                            </form>

                            <table id="profilingTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center">Name</th>
                                        <th class="align-middle text-center">Position</th>
                                        <th class="align-middle text-center">TIN</th>
                                        <th class="align-middle text-center">Birthdate</th>
                                        <th class="align-middle text-center">Age</th>
                                        <th class="align-middle text-center">Civil Status</th>
                                        <th class="align-middle text-center" width="110px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($boards as $board)
                                        @php
                                            $birth = !empty($board->usr_birth_date)
                                                ? \Carbon\Carbon::parse($board->usr_birth_date)
                                                : null;

                                            $address = collect([
                                                $board->uadd_street,
                                                $board->uadd_barangay,
                                                $board->uadd_city,
                                                $board->uadd_province,
                                                $board->uadd_region,
                                            ])
                                                ->filter()
                                                ->implode(', ');
                                        @endphp
                                        <tr>
                                            <td class="align-middle text-start">
                                                <div class="d-flex align-items-center">
                                                    <img class="rounded-circle me-3 flex-shrink-0"
                                                        src="{{ asset(getAvatar($board->usr_id)) }}" alt="user image"
                                                        loading="lazy"
                                                        style="width:50px; height:50px; object-fit:cover;">
                                                    <div>
                                                        {{ $board->usr_last_name }}, {{ $board->usr_first_name }}
                                                        {{ $board->usr_middle_name }}
                                                        <br />
                                                        <small>{{ $board->usr_email }}</small>
                                                        <br />
                                                        <small>{{ $address !== '' ? $address : '-' }}</small>
                                                        <br />
                                                        <em><small>Last login: {{ getLastLogin($board->usr_id) }}</small></em>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="align-middle text-center">
                                                @if (!empty($board->pos_name))
                                                    <span class="badge bg-success">{{ $board->pos_name }}</span>
                                                @else
                                                    <span class="badge bg-danger">No Position</span>
                                                @endif
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ trim((string) $board->usr_tin) !== '' ? $board->usr_tin : '-' }}
                                            </td>
                                            <td class="align-middle text-center"
                                                data-order="{{ $birth ? $birth->format('Y-m-d') : '' }}">
                                                {{ $birth ? $birth->format('m/d/Y') : '-' }}
                                            </td>
                                            <td class="align-middle text-center"
                                                data-order="{{ $birth ? $birth->age : -1 }}">
                                                {{ $birth ? $birth->age . ' yrs old' : '-' }}
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ !empty($board->usr_civil_status) ? $board->usr_civil_status : '-' }}
                                            </td>
                                            <td class="align-middle text-center">
                                                <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateBoardModal-{{ $board->usr_id }}">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                <a class="btn btn-info btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#resetModal-{{ $board->usr_id }}">
                                                    <span class="fa fa-key"></span>
                                                </a>
                                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1')
                                                    <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal-{{ $board->usr_id }}">
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

    {{-- Add Member Modal --}}
    <div class="modal fade" id="addBoardModal" tabindex="-1" aria-labelledby="addBoardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ url('profiling/board-of-members/add') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    {{-- Hidden fields (filled by the location dropdowns) --}}
                    <input type="hidden" name="region" id="add_hidden_region" value="">
                    <input type="hidden" name="province" id="add_hidden_province" value="">
                    <input type="hidden" name="municipality" id="add_hidden_municipality" value="">
                    <input type="hidden" name="barangay" id="add_hidden_barangay" value="">

                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addBoardModalLabel">
                            <span class="fa fa-plus text-white"></span> Add Board Member
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
                                <input type="email" class="form-control" id="add_email" name="usr_email"
                                    placeholder="Email" required>
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

                            {{-- Birthdate --}}
                            <div class="col-md-6 mb-3">
                                <label for="add_birth_date">Birthdate <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="add_birth_date" name="usr_birth_date"
                                    max="{{ date('Y-m-d') }}" required>
                            </div>

                            {{-- Civil Status --}}
                            <div class="col-md-6 mb-3">
                                <label for="add_civil_status">Civil Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="add_civil_status" name="usr_civil_status" required>
                                    <option value="">-- SELECT CIVIL STATUS --</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>

                            {{-- Position --}}
                            <div class="col-md-6 mb-3">
                                <label for="add_position">Position <span class="text-danger">*</span></label>
                                <select class="form-select" id="add_position" name="usr_position" required>
                                    <option value="">-- SELECT POSITION --</option>
                                    @foreach ($positions as $pos)
                                        <option value="{{ $pos->pos_id }}">{{ $pos->pos_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- TIN --}}
                            <div class="col-md-6 mb-3">
                                <label for="add_tin">TIN</label>
                                <input type="text" class="form-control" id="add_tin" name="usr_tin"
                                    placeholder="TIN" maxlength="50">
                            </div>

                            {{-- Photo --}}
                            <div class="col-md-12 mb-3">
                                <label for="add_avatar">Photo</label>
                                <input type="file" class="form-control" id="add_avatar" name="avatar"
                                    accept=".jpg,.jpeg,.png,.webp">
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
                            <span class="fa fa-save"></span> Save Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($boards as $board)
        @php
            $birth = !empty($board->usr_birth_date) ? \Carbon\Carbon::parse($board->usr_birth_date) : null;
        @endphp

        {{-- Update Member Modal --}}
        <div class="modal fade" id="updateBoardModal-{{ $board->usr_id }}" tabindex="-1"
            aria-labelledby="updateBoardModalLabel-{{ $board->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('profiling/board-of-members/update/user/' . $board->usr_id) }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="updateBoardModalLabel-{{ $board->usr_id }}">
                                Update Board Member
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label>Name</label>
                                <div class="form-control-plaintext">
                                    {{ $board->usr_first_name }} {{ $board->usr_last_name }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="avatar-{{ $board->usr_id }}">Photo</label>
                                <input type="file" class="form-control" id="avatar-{{ $board->usr_id }}"
                                    name="avatar" accept=".jpg,.jpeg,.png,.webp">
                            </div>

                            <div class="mb-3">
                                <label for="position-{{ $board->usr_id }}">Position <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="position-{{ $board->usr_id }}" name="usr_position"
                                    required>
                                    <option value="">-- SELECT POSITION --</option>
                                    @foreach ($positions as $pos)
                                        <option value="{{ $pos->pos_id }}"
                                            {{ $board->usr_position == $pos->pos_id ? 'selected' : '' }}>
                                            {{ $pos->pos_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="tin-{{ $board->usr_id }}">TIN</label>
                                <input type="text" class="form-control" id="tin-{{ $board->usr_id }}"
                                    name="usr_tin" value="{{ $board->usr_tin }}" maxlength="50">
                            </div>

                            <div class="mb-3">
                                <label for="birth-{{ $board->usr_id }}">Birthdate <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="birth-{{ $board->usr_id }}"
                                    name="usr_birth_date" value="{{ $birth ? $birth->format('Y-m-d') : '' }}"
                                    max="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="mb-3">
                                <label for="civil-{{ $board->usr_id }}">Civil Status <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="civil-{{ $board->usr_id }}"
                                    name="usr_civil_status" required>
                                    <option value="">-- SELECT CIVIL STATUS --</option>
                                    @foreach (['Single', 'Married', 'Divorced', 'Separated', 'Widowed'] as $status)
                                        <option value="{{ $status }}"
                                            {{ $board->usr_civil_status == $status ? 'selected' : '' }}>
                                            {{ $status }}
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
        <div class="modal fade" id="resetModal-{{ $board->usr_id }}" tabindex="-1"
            aria-labelledby="resetModalLabel-{{ $board->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@boards_reset_password', [$board->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-info">
                            <h5 class="modal-title" id="resetModalLabel-{{ $board->usr_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to RESET this member's password to
                                <strong>123456</strong>?
                            </p>
                            <small>{{ $board->usr_first_name }}
                                {{ $board->usr_last_name }}<br>{{ $board->usr_email }}</small>
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
        <div class="modal fade" id="deleteModal-{{ $board->usr_id }}" tabindex="-1"
            aria-labelledby="deleteModalLabel-{{ $board->usr_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST"
                        action="{{ action('App\Http\Controllers\ProfilingController@boards_delete', [$board->usr_id]) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="deleteModalLabel-{{ $board->usr_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>DELETE</strong> member
                                <strong>{{ $board->usr_first_name }}
                                    {{ $board->usr_last_name }}</strong>?
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

    {{-- Location dropdowns (Add Member) --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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

            addRegionSel.addEventListener('change', function() {
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

            addProvSel.addEventListener('change', function() {
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

            addMuniSel.addEventListener('change', function() {
                const muniId = this.value;
                addHiddenMuni.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';

                addResetSelect(addBrgySel, '-- SELECT BARANGAY --');
                addHiddenBrgy.value = '';

                if (muniId) {
                    addLoadOptions(addBrgySel, `/locations/${muniId}/barangays`, 'brg_name', 'brg_id',
                        '-- SELECT BARANGAY --');
                }
            });

            addBrgySel.addEventListener('change', function() {
                addHiddenBrgy.value = this.options[this.selectedIndex]?.getAttribute('data-name') || '';
            });

            // Reset modal fields on close
            document.getElementById('addBoardModal').addEventListener('hidden.bs.modal', function() {
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