@once
    <style>
        .ann-card {
            overflow: hidden;
            margin-bottom: 1.25rem;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            border-left: 4px solid var(--kx-primary, #1f3a5f);
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, .05);
        }

        .ann-image {
            aspect-ratio: 16 / 9;
            background: #eef1f6;
        }

        .ann-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .ann-body {
            padding: 1.1rem 1.25rem 1.25rem;
        }

        .ann-title {
            margin: 0 0 .5rem;
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .ann-content {
            overflow-wrap: anywhere;
        }

        .ann-content p:last-child {
            margin-bottom: 0;
        }
    </style>
@endonce