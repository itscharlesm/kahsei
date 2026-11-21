@extends('homepage.layouts.master')

@section('title', 'Announcements')
@section('crumb', 'Announcements')
@section('page_title', 'Announcements')
@section('page_sub', 'Latest updates for our members')

@section('content')
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 col-xl-8">

                    <p class="text-muted small">{{ $announcements->count() }} announcement(s)</p>

                    @forelse ($announcements as $a)
                        @php
                            $poster = trim($a->usr_first_name . ' ' . $a->usr_last_name);
                            $hasImage = !empty($a->ann_image);
                        @endphp

                        <article class="ann-card reveal">
                            @if ($hasImage)
                                <div class="ann-image">
                                    <img src="{{ asset('storage/images/announcements/' . $a->ann_image) }}"
                                        alt="{{ $a->ann_title }}" loading="lazy">
                                </div>
                            @endif

                            <div class="ann-body">
                                <small class="text-muted d-flex flex-wrap gap-3 mb-2">
                                    <span><i class="bi bi-calendar3"></i>
                                        {{ \Carbon\Carbon::parse($a->ann_date_created)->format('F j, Y - g:i A') }}</span>
                                    @if ($poster !== '')
                                        <span><i class="bi bi-person-fill"></i> {{ $poster }}</span>
                                    @endif
                                </small>

                                <h3 class="ann-title">{{ $a->ann_title }}</h3>

                                <div class="ann-content">
                                    {!! strip_tags($a->ann_content, '<p><br><b><strong><i><em><u><ul><ol><li><a>') !!}
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="qa-empty text-center text-muted">
                            <i class="bi bi-megaphone"></i>
                            <p class="mb-0">No announcements yet.</p>
                        </div>
                    @endforelse

                </div>
            </div>
        </div>
    </section>

    @include('homepage.partials.announcement_styles')
@endsection