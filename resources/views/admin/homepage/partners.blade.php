@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Partners</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Homepage</li>
                        <li class="breadcrumb-item active">Partners</li>
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
                <div class="card-body">
                    <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal"
                        data-bs-target="#addPartnerModal">
                        <span class="fa fa-plus"></span> Add Partner
                    </button>

                    {{-- 1 = Active (top), 0 = Inactive (last) --}}
                    @foreach ([1 => 'Partners', 0 => 'Inactive Partners'] as $status => $label)
                        @php
                            $group = $partners->where('prt_active', $status);
                        @endphp

                        @if ($status == 1 || $group->count() > 0)
                            @if ($status == 0)
                                <hr>
                            @endif

                            <h5 class="mb-3 {{ $status == 1 ? '' : 'text-muted' }}">{{ $label }}</h5>
                            <div class="row">
                                @forelse ($group as $partner)
                                    <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-4">
                                        <div class="card h-100 text-center {{ $partner->prt_active == 1 ? '' : 'bg-light' }}">
                                            <div class="card-body p-2 d-flex flex-column align-items-center">
                                                <div class="d-flex align-items-center justify-content-center border rounded bg-white mb-2"
                                                    style="width:100%; height:120px;">
                                                    <img src="{{ asset('images/partners/' . $partner->prt_image) }}"
                                                        alt="{{ $partner->prt_title }}" loading="lazy"
                                                        style="width:100%; height:120px; object-fit:contain; {{ $partner->prt_active == 1 ? '' : 'opacity:.4; filter:grayscale(1);' }}">
                                                </div>

                                                <small class="fw-bold mb-2">{{ $partner->prt_title }}</small>

                                                <div class="d-flex align-items-center justify-content-center gap-3 mt-auto">
                                                    <a class="btn btn-warning btn-sm" href="javascript:void(0)"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#updatePartnerModal-{{ $partner->prt_id }}">
                                                        <span class="fa fa-edit"></span>
                                                    </a>

                                                    <form method="POST"
                                                        action="{{ url('admin/homepage/partners/delete/' . $partner->prt_id) }}"
                                                        class="m-0">
                                                        @csrf
                                                        <div class="form-check form-switch m-0">
                                                            <input class="form-check-input" type="checkbox" role="switch"
                                                                style="cursor:pointer;"
                                                                {{ $partner->prt_active == 1 ? 'checked' : '' }}
                                                                onchange="this.form.submit()">
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted">No partners.</p>
                                    </div>
                                @endforelse
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Add Partner Modal --}}
    <div class="modal fade" id="addPartnerModal" tabindex="-1" aria-labelledby="addPartnerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ url('admin/homepage/partners/add') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="addPartnerModalLabel">
                            <span class="fa fa-plus text-white"></span> Add Partner
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="add_prt_title">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="add_prt_title" name="prt_title"
                                placeholder="Partner Title" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="add_prt_image">Image (PNG only) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="add_prt_image" name="prt_image"
                                accept=".png,image/png" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                        <button type="submit" class="btn btn-success">
                            <span class="fa fa-save"></span> Save Partner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Modals --}}
    @foreach ($partners as $partner)
        <div class="modal fade" id="updatePartnerModal-{{ $partner->prt_id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ url('admin/homepage/partners/update/' . $partner->prt_id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title">Update Partner</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3 text-center">
                                <img src="{{ asset('images/partners/' . $partner->prt_image) }}"
                                    alt="{{ $partner->prt_title }}"
                                    style="width:200px; height:120px; object-fit:contain;">
                            </div>
                            <div class="mb-3">
                                <label for="prt_title-{{ $partner->prt_id }}">Title <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="prt_title-{{ $partner->prt_id }}"
                                    name="prt_title" value="{{ $partner->prt_title }}" maxlength="255" required>
                            </div>
                            <div class="mb-3">
                                <label for="prt_image-{{ $partner->prt_id }}">
                                    Replace Image <small class="text-muted">(PNG only, optional)</small>
                                </label>
                                <input type="file" class="form-control" id="prt_image-{{ $partner->prt_id }}"
                                    name="prt_image" accept=".png,image/png">
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
    @endforeach
@endsection