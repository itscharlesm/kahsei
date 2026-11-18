{{-- Goals & Objectives --}}
<section class="section section-alt" id="goals-objectives">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6 reveal">
                <article class="go-card h-100">
                    <header class="go-head">
                        <span class="go-icon"><i class="bi bi-flag-fill"></i></span>
                        <div>
                            <span class="eyebrow">The big picture</span>
                            <h2>Our Goals</h2>
                        </div>
                    </header>
                    @if (!empty($goals) && trim(strip_tags((string) ($goals->goals_details ?? ''))) !== '')
                        <div class="rich-text">{!! $goals->goals_details !!}</div>
                    @else
                        <p class="text-muted fst-italic mb-0">Content coming soon.</p>
                    @endif
                </article>
            </div>

            <div class="col-lg-6 reveal">
                <article class="go-card go-alt h-100">
                    <header class="go-head">
                        <span class="go-icon"><i class="bi bi-list-check"></i></span>
                        <div>
                            <span class="eyebrow">How we get there</span>
                            <h2>Our Objectives</h2>
                        </div>
                    </header>
                    @if (!empty($objectives) && trim(strip_tags((string) ($objectives->obj_details ?? ''))) !== '')
                        <div class="rich-text">{!! $objectives->obj_details !!}</div>
                    @else
                        <p class="text-muted fst-italic mb-0">Content coming soon.</p>
                    @endif
                </article>
            </div>
        </div>
    </div>
</section>