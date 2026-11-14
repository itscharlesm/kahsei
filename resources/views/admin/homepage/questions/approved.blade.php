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
                    <h3 class="mb-0">Answered Questions</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ url('admin/home') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item">Homepage</li>
                        <li class="breadcrumb-item active">Answered Questions</li>
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
                            <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search questions...">

                            <table id="questionsTable" class="table table-hover table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th class="align-middle text-center" width="60px">No</th>
                                        <th class="align-middle text-center">Date Submitted</th>
                                        <th class="align-middle text-center">Date Answered</th>
                                        <th class="align-middle text-center">Inquirer</th>
                                        <th class="align-middle text-center">Question</th>
                                        <th class="align-middle text-center" width="130px">Action</th>
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
                                                {{ $question->que_date_answered ? \Carbon\Carbon::parse($question->que_date_answered)->format('m/d/Y | h:i A') : '—' }}
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
                                                <a class="btn btn-warning btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal" data-bs-target="#editModal-{{ $question->que_id }}">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                <a class="btn btn-danger btn-sm mb-1" href="javascript:void(0)"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#archiveModal-{{ $question->que_id }}">
                                                    <span class="fa fa-archive"></span>
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
        @php
            $videoId = null;
            if (!empty($question->que_video_link) && preg_match(
                '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~',
                $question->que_video_link,
                $m
            )) {
                $videoId = $m[1];
            }
        @endphp

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
                                <div><span class="badge bg-success">ANSWERED</span></div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date Submitted</label>
                                <div class="form-control-plaintext">
                                    {{ \Carbon\Carbon::parse($question->que_date_created)->format('m/d/Y | h:i A') }}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Date Answered</label>
                                <div class="form-control-plaintext">
                                    {{ $question->que_date_answered ? \Carbon\Carbon::parse($question->que_date_answered)->format('m/d/Y | h:i A') : '—' }}
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Answered By</label>
                                <div class="form-control-plaintext">
                                    @if ($question->answerer_first_name || $question->answerer_last_name)
                                        {{ $question->answerer_first_name }} {{ $question->answerer_last_name }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            @if (!empty($question->que_date_modified))
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Last Modified</label>
                                    <div class="form-control-plaintext">
                                        {{ \Carbon\Carbon::parse($question->que_date_modified)->format('m/d/Y | h:i A') }}
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Modified By</label>
                                    <div class="form-control-plaintext">
                                        @if ($question->modifier_first_name || $question->modifier_last_name)
                                            {{ $question->modifier_first_name }} {{ $question->modifier_last_name }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Question</label>
                                <div class="form-control-plaintext text-break">
                                    {{ $question->que_question }}</div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Answer</label>
                                <div class="form-control-plaintext text-break">
                                    @if (trim((string) $question->que_answer) !== '')
                                        {!! nl2br(e($question->que_answer)) !!}
                                    @else
                                        <span class="text-muted fst-italic">No written answer provided.</span>
                                    @endif
                                </div>
                            </div>

                            @if (!empty($question->que_video_link))
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Video</label>
                                    @if ($videoId)
                                        <div class="ratio ratio-16x9">
                                            <iframe class="answer-video"
                                                src="https://www.youtube.com/embed/{{ $videoId }}"
                                                loading="lazy"
                                                title="Answer video" frameborder="0" allowfullscreen
                                                referrerpolicy="strict-origin-when-cross-origin"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe>
                                        </div>
                                    @else
                                        <div class="form-control-plaintext">
                                            <a href="{{ $question->que_video_link }}" target="_blank" rel="noopener">{{ $question->que_video_link }}</a>
                                        </div>
                                    @endif
                                </div>
                            @endif
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

        {{-- Edit Modal --}}
        <div class="modal fade" id="editModal-{{ $question->que_id }}" tabindex="-1"
            aria-labelledby="editModalLabel-{{ $question->que_id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ url('admin/homepage/questions/approved/edit/' . $question->que_id) }}"
                        method="POST">
                        @csrf
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="editModalLabel-{{ $question->que_id }}">
                                <span class="fa fa-edit"></span> Edit Answer
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Question</label>
                                    <div class="form-control-plaintext text-break">
                                        {{ trim($question->que_question) }}</div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <div class="alert alert-info py-2 mb-0">
                                        Provide an <strong>answer</strong>, a <strong>YouTube video</strong>, or
                                        <strong>both</strong>.
                                    </div>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="edit_que_answer-{{ $question->que_id }}"
                                        class="form-label">Answer</label>
                                    <textarea class="form-control" id="edit_que_answer-{{ $question->que_id }}"
                                        name="que_answer" rows="4"
                                        placeholder="Type the answer here...">{{ $question->que_answer }}</textarea>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="edit_que_video_link-{{ $question->que_id }}"
                                        class="form-label">Youtube Video</label>
                                    <input class="form-control" type="text"
                                        id="edit_que_video_link-{{ $question->que_id }}" name="que_video_link"
                                        value="{{ $question->que_video_link }}"
                                        placeholder="Paste the YouTube embed code here">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-warning">
                                <span class="fa fa-save"></span> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Archive Modal --}}
        <div class="modal fade" id="archiveModal-{{ $question->que_id }}" tabindex="-1"
            aria-labelledby="archiveModalLabel-{{ $question->que_id }}" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ url('admin/homepage/questions/archive/' . $question->que_id) }}">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title text-white" id="archiveModalLabel-{{ $question->que_id }}">
                                Please Confirm
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to <strong>ARCHIVE</strong> this question?</p>
                            <p class="mb-0 text-muted text-break">
                                {{ trim($question->que_question) }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <span class="fa fa-close"></span> Close
                            </button>
                            <button type="submit" class="btn btn-danger">
                                <span class="fa fa-archive"></span> Confirm Archive
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