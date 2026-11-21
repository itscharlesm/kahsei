@once
    <style>
        /* Attorney card */
        .atty-sticky {
            position: relative;
        }

        .atty-card {
            width: 100%;
            max-width: 280px;
            margin-inline: auto;
            padding: 14px;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .06);
            text-align: center;
        }

        /* Fixed-size portrait frame: the full-body image is never stretched or cropped */
        .atty-photo {
            height: 420px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
            border-radius: 12px;
            background: linear-gradient(180deg, #f4f7fb 0%, #e3e9f2 100%);
        }

        .atty-photo img {
            display: block;
            height: 100%;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            object-position: bottom center;
        }

        .atty-info {
            padding: 14px 6px 4px;
        }

        .atty-name {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .atty-role {
            display: block;
            margin-top: 4px;
            font-size: .8rem;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--kx-primary, #1f3a5f);
        }

        /* Desktop: stays visible while scrolling long lists */
        @media (min-width: 992px) {
            .atty-sticky {
                position: sticky;
                top: 96px;
            }
        }

        /* Tablet/mobile: compact horizontal card shown above the content */
        @media (max-width: 991.98px) {
            .atty-card {
                max-width: none;
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 10px;
                text-align: left;
            }

            .atty-photo {
                flex: 0 0 96px;
                height: 130px;
            }

            .atty-photo img {
                width: 100%;
                object-fit: cover;
                object-position: top center;
                /* show face/upper body in the small frame */
            }

            .atty-info {
                padding: 0;
            }
        }
    </style>
@endonce

<div class="atty-sticky">
    <div class="atty-card">
        <div class="atty-photo">
            <img src="{{ asset('images/users/attorney_picture.png') }}" alt="Atty. Bernie Arevalo Calceta"
                loading="lazy">
        </div>
        <div class="atty-info">
            <h3 class="atty-name">Atty. Bernie Arevalo Calceta</h3>
            <span class="atty-role">Itanong mo kay Attorney</span>
        </div>
    </div>
</div>