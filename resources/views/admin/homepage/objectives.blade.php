@extends('admin.layouts.themes.main')

@section('content')
    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Objectives</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Homepage</li>
                        <li class="breadcrumb-item active">Objectives</li>
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

            @if ($objectives)
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="bg-primary-subtle text-primary rounded px-3 py-2">
                                        <i class="fas fa-circle-info"></i>
                                    </span>
                                    <div>
                                        <h5 class="mb-0">Goals</h5>
                                        <small class="text-muted">This text is shown on the homepage Goals section.</small>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="{{ url('admin/homepage/objectives/update/' . $objectives->obj_id) }}"
                                id="objectivesForm">
                                @csrf
                                <div class="card-body">
                                    <label for="obj_details" class="form-label fw-semibold">Details</label>
                                    <textarea id="obj_details" name="obj_details">{{ old('obj_details', $objectives->obj_details) }}</textarea>
                                </div>

                                <div class="card-footer py-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            @if ($objectives->obj_date_modified)
                                                Last updated
                                                {{ \Carbon\Carbon::parse($objectives->obj_date_modified)->format('M d, Y h:i A') }}
                                            @else
                                                Not yet updated
                                            @endif
                                        </small>
                                        <button type="submit" class="btn btn-primary px-4" id="btnSaveObjectives">
                                            <i class="fas fa-save me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="alert alert-warning">No objectives record found.</div>
            @endif

        </div>
    </div>
@endsection

@section('page_scripts')
    <script>
        $(function () {
            $('#obj_details').summernote({
                height: 350,
                placeholder: 'Write organization\'s objectives...',
                disableDragAndDrop: true,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['history', ['undo', 'redo']]
                ],
                callbacks: {
                    // Paste as plain text so no images or outside styling get in
                    onPaste: function (e) {
                        e.preventDefault();
                        const text = (e.originalEvent || e).clipboardData.getData('text/plain');
                        document.execCommand('insertText', false, text);
                    }
                }
            });

            // Prevent double submit
            $('#objectivesForm').on('submit', function () {
                $('#btnSaveGoals').prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
            });
        });
    </script>
@endsection