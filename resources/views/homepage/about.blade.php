@extends('homepage.layouts.master')

@section('title', 'About')
@section('crumb', 'About Us')
@section('page_title', 'About')
@section('page_sub', 'Kagalingan Advocates for Health, Sanitation, and Environment Inc.')

@section('content')
    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9 reveal">
                    <article class="about-card">
                        <span class="about-icon"><i class="bi bi-people-fill"></i></span>
                        @if (!empty($about) && trim(strip_tags((string) ($about->about_details ?? ''))) !== '')
                            <div class="rich-text">{!! $about->about_details !!}</div>
                        @else
                            <p class="text-muted fst-italic mb-0">Content coming soon.</p>
                        @endif
                    </article>
                </div>
            </div>
        </div>
    </section>
@endsection