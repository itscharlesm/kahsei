{{-- resources/views/homepage/partials/preloader.blade.php --}}
<style>
    :root {
        /* Same background as the home header (.site-header) */
        --kx-preloader-bg:
            radial-gradient(circle at 15% 20%, rgba(20, 184, 166, .45), transparent 45%),
            radial-gradient(circle at 85% 10%, rgba(163, 230, 53, .22), transparent 40%),
            linear-gradient(160deg, #062f2c 0%, #0b4a45 55%, #0f766e 100%);
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
        object-fit: cover;
        display: block;
        padding: 5px;
        border-radius: 50%;
        background: #fff content-box; /* white face inside the lime-teal ring, like the header logo */
        border: 0;
        box-shadow: inset 0 0 0 5px transparent, 0 0 0 8px rgba(255, 255, 255, .1), 0 14px 30px rgba(0, 0, 0, .35);
        background-image: linear-gradient(#fff, #fff), linear-gradient(135deg, #a3e635, #14b8a6);
        background-clip: content-box, border-box;
        background-origin: content-box, border-box;
        animation: kx-coin-spin 2.6s linear infinite; /* bigger number = slower spin */
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