@extends('homepage.layouts.master')

@section('title', 'Ask Questions')
@section('crumb', 'Itanong mo kay Attorney')
@section('page_title', 'Ask Questions')
@section('page_sub', 'Itanong mo kay Attorney')

@section('content')
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7 reveal">
                    <div class="ask-card">
                        <span class="ask-icon"><i class="bi bi-chat-square-text-fill"></i></span>
                        <h2>Have a question?</h2>
                        <p class="text-muted">Type your question below and send it to our attorney.</p>

                        {{-- No function yet: submitting only shows a notice (see homepage.js) --}}
                        <form id="askForm" novalidate>
                            <div class="mb-3">
                                <label for="ask_question" class="form-label fw-semibold">Your question</label>
                                <textarea class="form-control" id="ask_question" name="question" rows="6" maxlength="2000"
                                    placeholder="Write your question here..." required></textarea>
                                <div class="form-text text-end"><span id="askCount">0</span> / 2000</div>
                            </div>
                            <div id="askNotice"></div>
                            <button type="submit" class="btn btn-kx w-100">
                                <i class="bi bi-send-fill me-1"></i> Submit Question
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection