{{-- Partners --}}
<section class="section section-alt" id="partners">
    @include('homepage.partials.preloader')
    <div class="container">
        @if ($showHeading ?? true)
            <div class="section-head reveal">
                <span class="eyebrow">Working together</span>
                <h2>Our Partners</h2>
            </div>
        @endif

        <div class="partner-grid">
            @forelse ($partners as $partner)
                <div class="partner-tile reveal" title="{{ $partner->prt_title }}">
                    <img src="{{ asset('images/partners/' . $partner->prt_image) }}" alt="{{ $partner->prt_title }}"
                        loading="lazy">
                </div>
            @empty
                <p class="text-center text-muted w-100">No partners to display yet.</p>
            @endforelse
        </div>
    </div>
</section>