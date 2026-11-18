@extends('homepage.layouts.master')

@section('title', 'My Questions')
@section('crumb', 'Itanong mo kay Attorney')
@section('page_title', 'My Questions')
@section('page_sub', 'Itanong mo kay Attorney')

@section('content')
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">

                    <p class="text-muted small">{{ $questions->count() }} question(s)</p>

                    @forelse ($questions as $q)
                        @php
                            $status = strtoupper(trim((string) $q->que_status));

                            // APPROVED = green, ARCHIVED = red, everything else (FOR APPROVAL, REJECTED) = yellow "waiting"
                            if ($status === 'APPROVED') {
                                $state = 'approved';
                            } elseif ($status === 'ARCHIVED') {
                                $state = 'archived';
                            } else {
                                $state = 'waiting';
                            }

                            $hasAnswer = trim((string) $q->que_answer) !== '';
                            $hasVideo = trim((string) $q->que_video_link) !== '';

                            $videoId = null;
                            if (
                                $hasVideo &&
                                preg_match(
                                    '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~',
                                    $q->que_video_link,
                                    $m,
                                )
                            ) {
                                $videoId = $m[1];
                            }
                        @endphp

                        <article class="qa-card is-{{ $state }} reveal">
                            <header class="qa-meta justify-content-between flex-wrap">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3"></i>
                                    {{ \Carbon\Carbon::parse($q->que_date_created)->format('F j, Y g:i A') }}
                                </small>

                                @if ($state === 'approved')
                                    <span class="badge text-bg-success"><i class="bi bi-check-circle"></i> Answered</span>
                                @elseif ($state === 'archived')
                                    <span class="badge text-bg-danger"><i class="bi bi-archive"></i> Archived</span>
                                @else
                                    <span class="badge text-bg-warning"><i class="bi bi-hourglass-split"></i> Waiting for
                                        answer</span>
                                @endif
                            </header>

                            {{-- Question (always shown) --}}
                            <div class="qa-row">
                                <span class="qa-badge q">Q</span>
                                <div class="qa-text">{!! nl2br(e($q->que_question)) !!}</div>
                            </div>

                            {{-- APPROVED: answer and/or video --}}
                            @if ($state === 'approved')
                                @if ($hasAnswer)
                                    <div class="qa-row qa-answer">
                                        <span class="qa-badge a">A</span>
                                        <div class="qa-text">{!! nl2br(e($q->que_answer)) !!}</div>
                                    </div>
                                @endif
                            @endif

                            {{-- WAITING: FOR APPROVAL and REJECTED look the same --}}
                            @if ($state === 'waiting')
                                <div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-0" role="alert">
                                    <i class="bi bi-hourglass-split"></i>
                                    <div>Waiting for answer.</div>
                                </div>
                            @endif

                            {{-- ARCHIVED: notice, plus answer/video only if they exist --}}
                            @if ($state === 'archived')
                                <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                                    <i class="bi bi-archive-fill"></i>
                                    <div>Admin has archived your question.</div>
                                </div>

                                @if ($hasAnswer)
                                    <div class="qa-row qa-answer">
                                        <span class="qa-badge a">A</span>
                                        <div class="qa-text">{!! nl2br(e($q->que_answer)) !!}</div>
                                    </div>
                                @endif
                            @endif

                            {{-- Video (approved or archived), only when there is a link --}}
                            @if ($state !== 'waiting' && $hasVideo)
                                <div class="qa-video">
                                    @if ($videoId)
                                        <div class="ratio ratio-16x9">
                                            <iframe src="https://www.youtube.com/embed/{{ $videoId }}"
                                                title="Answer video" loading="lazy" allowfullscreen
                                                referrerpolicy="strict-origin-when-cross-origin"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe>
                                        </div>
                                    @else
                                        <a href="{{ $q->que_video_link }}" target="_blank" rel="noopener"
                                            class="text-break"><i class="bi bi-play-circle"></i>
                                            {{ $q->que_video_link }}</a>
                                    @endif
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="qa-empty text-center text-muted">
                            <i class="bi bi-chat-square-dots"></i>
                            <p class="mb-3">You haven't asked any questions yet.</p>
                            <a href="{{ url('itanong-mo-kay-attorney/ask-question') }}" class="btn btn-kx px-4 d-inline-flex align-items-center">
                                Ask a Question
                            </a>
                        </div>
                    @endforelse

                </div>
            </div>
        </div>
    </section>
@endsection