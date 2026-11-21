@extends('homepage.layouts.master')

@section('title', 'Answered Questions')
@section('crumb', 'Itanong mo kay Attorney')
@section('page_title', 'Answered Questions')
@section('page_sub', 'Itanong mo kay Attorney')

@section('content')
    <section class="section">
        @include('homepage.partials.preloader')
        <div class="container">
            <div class="row g-4 justify-content-center">

                {{-- Main content --}}
                <div class="col-lg-8 order-2 order-lg-1">

                    {{-- Search --}}
                    <div class="qa-search reveal">
                        <i class="bi bi-search"></i>
                        <input type="search" id="qaSearch" class="form-control"
                            placeholder="Search questions and answers..." autocomplete="off"
                            aria-label="Search questions and answers">
                    </div>
                    <p class="qa-count text-muted small" id="qaCount">
                        <span id="qaShown">{{ $questions->count() }}</span> of {{ $questions->count() }} question(s)
                    </p>

                    {{-- Latest to oldest --}}
                    <div id="qaList">
                        @foreach ($questions as $q)
                            @php
                                $inquirer = $q->is_anonymous == 1 ? 'Anonymous' : trim($q->usr_first_name . ' ' . $q->usr_last_name);
                                if ($inquirer === '') {
                                    $inquirer = 'Anonymous';
                                }

                                $videoId = null;
                                if (
                                    !empty($q->que_video_link) &&
                                    preg_match(
                                        '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~',
                                        $q->que_video_link,
                                        $m,
                                    )
                                ) {
                                    $videoId = $m[1];
                                }

                                $hasAnswer = trim((string) $q->que_answer) !== '';
                            @endphp

                            <article class="qa-card reveal" data-qa-item
                                data-search="{{ mb_strtolower($inquirer . ' ' . $q->que_question . ' ' . $q->que_answer) }}">
                                <header class="qa-meta">
                                    <span class="qa-avatar">
                                        <i class="bi {{ $inquirer === 'Anonymous' ? 'bi-incognito' : 'bi-person-fill' }}"></i>
                                    </span>
                                    <div>
                                        <strong>{{ $inquirer }}</strong>
                                        @if ($q->que_date_answered)
                                            <small class="text-muted d-block">
                                                Answered {{ \Carbon\Carbon::parse($q->que_date_answered)->format('F j, Y') }}
                                            </small>
                                        @endif
                                    </div>
                                </header>

                                <div class="qa-row">
                                    <span class="qa-badge q">Q</span>
                                    <div class="qa-text">{!! nl2br(e($q->que_question)) !!}</div>
                                </div>

                                @if ($hasAnswer)
                                    <div class="qa-row qa-answer">
                                        <span class="qa-badge a">A</span>
                                        <div class="qa-text">{!! nl2br(e($q->que_answer)) !!}</div>
                                    </div>
                                @endif

                                @if (!empty($q->que_video_link))
                                    <div class="qa-video">
                                        @if ($videoId)
                                            <div class="ratio ratio-16x9">
                                                <iframe src="https://www.youtube.com/embed/{{ $videoId }}" title="Answer video"
                                                    loading="lazy" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"
                                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe>
                                            </div>
                                        @else
                                            <a href="{{ $q->que_video_link }}" target="_blank" rel="noopener" class="text-break"><i
                                                    class="bi bi-play-circle"></i>
                                                {{ $q->que_video_link }}</a>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="qa-empty text-center text-muted {{ $questions->count() ? 'd-none' : '' }}" id="qaEmpty">
                        <i class="bi bi-chat-square-dots"></i>
                        <p class="mb-0">No questions found.</p>
                    </div>
                </div>

                {{-- Attorney --}}
                <aside class="col-lg-4 col-xl-3 order-1 order-lg-2">
                    @include('homepage.partials.attorney_card')
                </aside>

            </div>
        </div>
    </section>
@endsection