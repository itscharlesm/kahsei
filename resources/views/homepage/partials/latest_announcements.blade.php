{{-- resources/views/homepage/partials/latest-announcements.blade.php --}}
{{-- Latest 2 active announcements, logged-in users only --}}
@if (session('usr_id') && isset($latestAnnouncements) && $latestAnnouncements->count())
    <section class="section" id="latest-announcements">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Announcements</span>
                <h2>Latest Announcements</h2>
            </div>

            <div class="row g-4 justify-content-center">
                @foreach ($latestAnnouncements as $a)
                    @php
                        $poster = trim($a->usr_first_name . ' ' . $a->usr_last_name);
                        $hasImage = !empty($a->ann_image);
                    @endphp

                    <div class="col-md-6 reveal">
                        <article class="ann-card h-100 mb-0">
                            @if ($hasImage)
                                <div class="ann-image">
                                    <img src="{{ asset('storage/images/announcements/' . $a->ann_image) }}"
                                        alt="{{ $a->ann_title }}" loading="lazy">
                                </div>
                            @endif

                            <div class="ann-body">
                                <small class="text-muted d-flex flex-wrap gap-3 mb-2">
                                    <span><i class="bi bi-calendar3"></i>
                                        {{ \Carbon\Carbon::parse($a->ann_date_created)->format('F j, Y') }}</span>
                                    @if ($poster !== '')
                                        <span><i class="bi bi-person-fill"></i> {{ $poster }}</span>
                                    @endif
                                </small>

                                <h3 class="ann-title">{{ $a->ann_title }}</h3>
                                <div class="ann-content">
                                    {{ \Illuminate\Support\Str::limit(trim(strip_tags($a->ann_content)), 180) }}
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-4 reveal">
                <a href="{{ url('announcements') }}" class="btn btn-kx px-4 d-inline-flex align-items-center">
                    View All Announcements <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    @include('homepage.partials.announcement-styles')
@endif