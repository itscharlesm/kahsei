{{-- resources/views/homepage/partials/preloader.blade.php --}}
<style>
    :root {
        /* Set this to the exact color behind your home logo (e.g. #0b3d2e) */
        --kx-preloader-bg: #ffffff;
    }

    #kx-preloader {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--kx-preloader-bg);
        transition: opacity .45s ease, visibility .45s ease;
    }

    #kx-preloader.is-hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .kx-coin-wrap {
        perspective: 800px;
    }

    .kx-coin {
        width: 110px;
        height: 110px;
        object-fit: contain;
        display: block;
        animation: kx-coin-spin 2.6s linear infinite; /* bigger number = slower spin */
        filter: drop-shadow(0 6px 10px rgba(0, 0, 0, .15));
    }

    @keyframes kx-coin-spin {
        from { transform: rotateY(0deg); }
        to   { transform: rotateY(360deg); }
    }

    @media (prefers-reduced-motion: reduce) {
        .kx-coin { animation-duration: 6s; }
    }

    body.kx-loading {
        overflow: hidden;
    }
</style>

<noscript><style>#kx-preloader { display: none !important; }</style></noscript>

<div id="kx-preloader" role="status" aria-label="Loading">
    <div class="kx-coin-wrap">
        <img class="kx-coin" src="{{ asset('images/logos/kahsei.png') }}" alt="KAHSEI">
    </div>
</div>

<script>
    (function () {
        var loader = document.getElementById('kx-preloader');
        if (!loader) return;

        document.body.classList.add('kx-loading');

        var done = false;
        function hide() {
            if (done) return;
            done = true;
            loader.classList.add('is-hidden');
            document.body.classList.remove('kx-loading');
            setTimeout(function () {
                if (loader.parentNode) loader.parentNode.removeChild(loader);
            }, 600);
        }

        // Remove once the whole page (images, styles, scripts) has loaded
        if (document.readyState === 'complete') {
            hide();
        } else {
            window.addEventListener('load', hide);
        }

        // Safety net: never leave visitors stuck on a slow/blocked resource
        setTimeout(hide, 10000);
    })();
</script>