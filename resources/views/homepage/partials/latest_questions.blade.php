{{-- Latest 2 approved questions, shown on Home above the Board of Members --}}
@if ($latestQuestions->count())
    <section class="section section-divided" id="latest-questions">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Itanong mo kay Attorney</span>
                <h2>Latest Answered Questions</h2>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach ($latestQuestions as $q)
                    @php
                        $inquirer = $q->is_anonymous == 1 ? 'Anonymous' : trim($q->usr_first_name . ' ' . $q->usr_last_name);
                        if ($inquirer === '') {
                            $inquirer = 'Anonymous';
                        }
                        $hasAnswer = trim((string) $q->que_answer) !== '';
                        $hasVideo = trim((string) $q->que_video_link) !== '';
                    @endphp

                    <div class="col-lg-6 reveal">
                        <article class="qa-card is-approved h-100 mb-0">
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
                                <div class="qa-text">{{ \Illuminate\Support\Str::limit($q->que_question, 160) }}</div>
                            </div>

                            @if ($hasAnswer)
                                <div class="qa-row qa-answer">
                                    <span class="qa-badge a">A</span>
                                    <div class="qa-text">{{ \Illuminate\Support\Str::limit($q->que_answer, 220) }}</div>
                                </div>
                            @endif

                            @if ($hasVideo)
                                <a href="{{ url('itanong-mo-kay-attorney/answers') }}"
                                    class="small fw-semibold text-decoration-none">
                                    <i class="bi bi-play-circle-fill"></i> Video answer included
                                </a>
                            @endif
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-4 reveal">
                <a href="{{ url('itanong-mo-kay-attorney/answers') }}"
                    class="btn btn-kx px-4 d-inline-flex align-items-center">
                    View All Answered Questions <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>
@endif