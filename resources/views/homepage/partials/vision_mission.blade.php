{{-- Vision & Mission --}}
<section class="section" id="vision-mission">
    @include('homepage.partials.preloader')
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6 reveal">
                <article class="vm-card vm-vision h-100">
                    <span class="vm-icon"><i class="bi bi-eye-fill"></i></span>
                    <span class="eyebrow">Where we are going</span>
                    <h2>Our Vision</h2>
                    @if (!empty($vision) && trim(strip_tags((string) ($vision->vis_details ?? ''))) !== '')
                        <div class="rich-text">{!! $vision->vis_details !!}</div>
                    @else
                        <p class="text-muted fst-italic mb-0">Content coming soon.</p>
                    @endif
                </article>
            </div>

            <div class="col-lg-6 reveal">
                <article class="vm-card vm-mission h-100">
                    <span class="vm-icon"><i class="bi bi-bullseye"></i></span>
                    <span class="eyebrow">What we do</span>
                    <h2>Our Mission</h2>
                    @if (!empty($mission) && trim(strip_tags((string) ($mission->mis_details ?? ''))) !== '')
                        <div class="rich-text">{!! $mission->mis_details !!}</div>
                    @else
                        <p class="text-muted fst-italic mb-0">Content coming soon.</p>
                    @endif
                </article>
            </div>
        </div>
    </div>
</section>