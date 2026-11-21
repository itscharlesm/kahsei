{{-- Board of Members --}}
<section class="section" id="board">
    @include('homepage.partials.preloader')
    <div class="container">
        @if ($showHeading ?? true)
            <div class="section-head reveal">
                <span class="eyebrow">The people behind KAHSEI</span>
                <h2>Board of Members</h2>
            </div>
        @endif

        <div class="org-chart">
            @forelse ($levels as $posId => $members)
                <div class="org-level reveal">
                    <span class="org-node">{{ $members->first()->pos_name }}</span>

                    <div class="org-row">
                        @foreach ($members as $m)
                            @php
                                $fullName = trim(
                                    $m->usr_first_name .
                                    ' ' .
                                    (!empty($m->usr_middle_name) ? mb_substr($m->usr_middle_name, 0, 1) . '. ' : '') .
                                    $m->usr_last_name,
                                );
                            @endphp
                            <article class="org-card">
                                <div class="org-photo">
                                    <img src="{{ asset(getAvatar($m->usr_id)) }}" alt="{{ $fullName }}" loading="lazy">
                                </div>
                                <h3>{{ $fullName }}</h3>
                                <p>{{ $m->pos_name }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-center text-muted">No board members to display yet.</p>
            @endforelse
        </div>
    </div>
</section>