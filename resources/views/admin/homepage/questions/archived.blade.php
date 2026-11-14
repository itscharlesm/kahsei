@extends('admin.layouts.themes.main')

@section('content')
    <style>
        .question-truncate {
            max-width: 450px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>

    {{-- Content Header --}}
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Rejected Questions</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Homepage</li>
                        <li class="breadcrumb-item active">Rejected Questions</li>
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
                            <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search questions...">

                            <table id="questionsTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center" width="60px">No</th>
                                        <th class="align-middle text-center">Date Submitted</th>
                                        <th class="align-middle text-center">Date Rejected</th>
                                        <th class="align-middle text-center">Inquirer</th>
                                        <th class="align-middle text-center">Question</th>
                                        <th class="align-middle text-center" width="80px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($questions as $question)
                                        <tr>
                                            <td class="align-middle text-center">{{ $loop->index + 1 }}</td>
                                            <td class="align-middle text-center">
                                                {{ \Carbon\Carbon::parse($question->que_date_created)->format('m/d/Y | h:i A') }}
                                            </td>
                                            <td class="align-middle text-center">
                                                {{ $question->que_date_archived ? \Carbon\Carbon::parse($question->que_date_archived)->format('m/d/Y | h:i A') : '—' }}
                                            </td>
                                            <td class="align-middle text-center">
                                                @if ($question->is_anonymous == 1)
                                                    Anonymous
                                                @else
                                                    {{ $question->usr_first_name }} {{ $question->usr_last_name }}
                                                @endif
                                            </td>
                                            <td class="align-middle text-start">
                                                <div class="question-truncate" title="{{ $question->que_question }}">
                                                    {{ $question->que_question }}
                                                </div>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a class="btn btn-primary btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal" data-bs-target="#viewModal-{{ $question->que_id }}">
                                                    <span class="fa fa-eye"></span>
                                                </a>
                                                <a class="btn btn-success btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#activateModal-{{ $question->que_id }}">
                                                    <span class="fa fa-check"></span>
                                                </a>
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

    @foreach ($questions as $question)
        {{-- View Modal --}}
        <div class="modal fade" id="viewModal-{{ $question->que_id }}" tabindex="-1"
            aria-labelledby="viewModalLabel-{{ $question->que_id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="viewModalLabel-{{ $question->que_id }}">
                            <span class="fa fa-eye text-white"></span> Question Details
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Inquirer</label>
                                <div class="form-control-plaintext fw-bold">
                                    @if ($question->is_anonymous == 1)
                                        Anonymous
                                    @else
                                        {{ $question->usr_first_name }} {{ $question->usr_last_name }}
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <div><span class="badge bg-danger">REJECTED</span></div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date Submitted</label>
                                <div class="form-control-plaintext">
                                    {{ \Carbon\Carbon::parse($question->que_date_created)->format('m/d/Y | h:i A') }}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date Archived</label>
                                <div class="form-control-plaintext">
                                    {{ $question->que_date_archived ? \Carbon\Carbon::parse($question->que_date_archived)->format('m/d/Y | h:i A') : '—' }}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Archived By</label>
                                <div class="form-control-plaintext">
                                    @if ($question->archiver_first_name || $question->archiver_last_name)
                                        {{ $question->archiver_first_name }} {{ $question->archiver_last_name }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Question</label>
                                <div class="form-control-plaintext text-break">
                                    {{ $question->que_question }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <span class="fa fa-close"></span> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activate Modal --}}
        <div class="modal fade" id="activateModal-{{ $question->que_id }}" tabindex="-1"
            aria-labelledby="activateModalLabel-{{ $question->que_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ url('admin/homepage/questions/activate/' . $question->que_id) }}">
                        @csrf
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title text-white" id="activateModalLabel-{{ $question->que_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>ACTIVATE</strong> this question?</p>
                            <p class="mb-0 text-muted text-break">
                                {{ trim($question->que_question) }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-success">
                                <span class="fa fa-times"></span> Confirm Activate
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
            let rows = document.querySelectorAll('#questionsTable tbody tr');

            rows.forEach(function (row) {
                row.style.display = row.innerText.toLowerCase().includes(value) ? "" : "none";
            });
        });
    </script>
@endsection