<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <title>Login | KAHSEI</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="KAHSEI">
    <meta name="author" content="Infinit Soloutions">
    <link rel="shortcut icon" href="{{ asset('images/logos/kahsei.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('login/css/style.css') }}">

    <style>
        /* Change these two to re-theme the whole page */
        :root {
            --accent-1: #14b8a6;
            --accent-2: #6366f1;
            --glass: rgba(255, 255, 255, .10);
            --glass-border: rgba(255, 255, 255, .22);
            --text: #f4f6fb;
            --text-dim: rgba(244, 246, 251, .68);
        }

        * {
            box-sizing: border-box;
        }

        body.kx-body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 24px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            background: #0b1020;
            position: relative;
            overflow-x: hidden;
        }

        /* Full-screen photo + dark tint */
        .kx-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            background:
                linear-gradient(135deg, rgba(11, 16, 32, .88), rgba(20, 30, 60, .78)),
                url({{ asset('images/logos/kahsei.jpg') }}) center / cover no-repeat;
            filter: saturate(1.1);
        }

        /* Floating colour blobs */
        .kx-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: .55;
            z-index: 0;
            animation: kxFloat 14s ease-in-out infinite alternate;
        }

        .kx-blob.b1 {
            width: 380px;
            height: 380px;
            background: var(--accent-1);
            top: -90px;
            left: -90px;
        }

        .kx-blob.b2 {
            width: 420px;
            height: 420px;
            background: var(--accent-2);
            bottom: -120px;
            right: -100px;
            animation-delay: -6s;
        }

        @keyframes kxFloat {
            from {
                transform: translate(0, 0) scale(1);
            }

            to {
                transform: translate(40px, 30px) scale(1.12);
            }
        }

        /* ---------- Glass card ---------- */
        .kx-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 410px;
            padding: 42px 36px 30px;
            background: var(--glass);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            backdrop-filter: blur(22px) saturate(160%);
            -webkit-backdrop-filter: blur(22px) saturate(160%);
            box-shadow: 0 30px 70px rgba(0, 0, 0, .45), inset 0 1px 0 rgba(255, 255, 255, .25);
            animation: kxIn .7s cubic-bezier(.2, .8, .2, 1) both;
        }

        @keyframes kxIn {
            from {
                opacity: 0;
                transform: translateY(26px) scale(.97);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .kx-logo-wrap {
            width: 96px;
            height: 96px;
            margin: -92px auto 16px;
            padding: 4px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            box-shadow: 0 14px 30px rgba(0, 0, 0, .4);
        }

        .kx-logo-wrap img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
            display: block;
        }

        .kx-card h1 {
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: 3px;
            margin: 0 0 4px;
            background: linear-gradient(90deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .kx-sub {
            text-align: center;
            font-size: .82rem;
            font-weight: 300;
            color: var(--text-dim);
            margin-bottom: 28px;
        }

        .kx-error {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px 14px;
            margin-bottom: 18px;
            font-size: .82rem;
            color: #ffd7dc;
            background: rgba(239, 68, 68, .22);
            border: 1px solid rgba(248, 113, 113, .5);
            border-radius: 14px;
        }

        /* ---------- Floating-label fields ---------- */
        .kx-field {
            position: relative;
            margin-bottom: 18px;
        }

        .kx-field .form-control {
            height: 54px;
            padding: 20px 46px 6px 48px;
            font-size: .95rem;
            color: var(--text);
            background: rgba(255, 255, 255, .08);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            transition: border-color .25s, background .25s, box-shadow .25s;
        }

        .kx-field .form-control:focus {
            outline: none;
            color: var(--text);
            background: rgba(255, 255, 255, .14);
            border-color: var(--accent-1);
            box-shadow: 0 0 0 4px rgba(20, 184, 166, .25);
        }

        .kx-field .form-control:-webkit-autofill {
            -webkit-text-fill-color: var(--text);
            transition: background-color 9999s ease-in-out 0s;
        }

        .kx-field label {
            position: absolute;
            left: 48px;
            top: 17px;
            margin: 0;
            font-size: .92rem;
            color: var(--text-dim);
            pointer-events: none;
            transition: all .2s ease;
        }

        .kx-field .form-control:focus~label,
        .kx-field .form-control:not(:placeholder-shown)~label {
            top: 7px;
            font-size: .68rem;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: var(--accent-1);
        }

        .kx-field .kx-ico {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.05rem;
            color: var(--text-dim);
            transition: color .25s;
        }

        .kx-field .form-control:focus~.kx-ico {
            color: var(--accent-1);
        }

        .kx-eye {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: var(--text-dim);
            cursor: pointer;
            transition: background .2s, color .2s;
        }

        .kx-eye:hover {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .kx-forgot {
            text-align: right;
            margin: -6px 2px 20px;
        }

        .kx-forgot a {
            font-size: .8rem;
            color: var(--text-dim);
            text-decoration: none;
            transition: color .2s;
        }

        .kx-forgot a:hover {
            color: #fff;
        }

        /* ---------- Buttons ---------- */
        .kx-btn {
            position: relative;
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 999px;
            font-weight: 600;
            font-size: .95rem;
            letter-spacing: 1.5px;
            color: #fff;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            background-size: 160% 100%;
            background-position: 0 0;
            box-shadow: 0 14px 28px rgba(99, 102, 241, .38);
            cursor: pointer;
            transition: background-position .5s, transform .2s, box-shadow .2s;
        }

        .kx-btn:hover {
            background-position: 100% 0;
            transform: translateY(-2px);
            box-shadow: 0 18px 34px rgba(20, 184, 166, .42);
            color: #fff;
        }

        .kx-btn:active {
            transform: translateY(0);
        }

        .kx-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0 16px;
            font-size: .72rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-dim);
        }

        .kx-divider::before,
        .kx-divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--glass-border);
        }

        .kx-signup {
            display: block;
            text-align: center;
            padding: 12px;
            font-size: .88rem;
            font-weight: 500;
            color: var(--text);
            border: 1px solid var(--glass-border);
            border-radius: 999px;
            text-decoration: none;
            transition: background .25s, border-color .25s;
        }

        .kx-signup:hover {
            color: #fff;
            text-decoration: none;
            background: rgba(255, 255, 255, .12);
            border-color: rgba(255, 255, 255, .5);
        }

        .kx-foot {
            margin: 22px 0 0;
            text-align: center;
            font-size: .7rem;
            color: var(--text-dim);
        }

        /* ---------- Forgot-password modal (dark glass) ---------- */
        .kx-modal .modal-content {
            color: var(--text);
            background: rgba(18, 24, 46, .82);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            box-shadow: 0 30px 80px rgba(0, 0, 0, .6);
            overflow: hidden;
        }

        .kx-modal .modal-header,
        .kx-modal .modal-footer {
            border: 0;
            padding: 22px 28px 8px;
        }

        .kx-modal .modal-footer {
            padding: 8px 28px 24px;
        }

        .kx-modal .modal-body {
            padding: 8px 28px 8px;
        }

        .kx-modal .modal-title {
            font-weight: 600;
            font-size: 1.05rem;
        }

        .kx-modal .close {
            color: #fff;
            text-shadow: none;
            opacity: .7;
        }

        .kx-modal .close:hover {
            opacity: 1;
        }

        .kx-fp-icon {
            width: 68px;
            height: 68px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-1), var(--accent-2));
            box-shadow: 0 10px 24px rgba(99, 102, 241, .4);
        }

        .kx-modal .modal-footer .btn {
            border-radius: 999px;
            padding: 9px 22px;
            font-weight: 500;
            border: 0;
        }

        .kx-modal .btn-cancel {
            background: rgba(255, 255, 255, .12);
            color: #fff;
        }

        .kx-modal .btn-cancel:hover {
            background: rgba(255, 255, 255, .2);
        }

        .kx-modal .btn-reset {
            color: #fff;
            background: linear-gradient(90deg, var(--accent-1), var(--accent-2));
            box-shadow: 0 8px 18px rgba(99, 102, 241, .35);
        }

        .modal-backdrop.show {
            opacity: .65;
        }

        @media (max-width: 480px) {
            .kx-card {
                padding: 38px 22px 24px;
                border-radius: 24px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .kx-card,
            .kx-blob {
                animation: none;
            }
        }
    </style>
</head>

<body class="kx-body">
    @include('admin.vendor.sweetalert.alert')
    @include('admin.layouts.partials.alerts')
    @include('admin.layouts.partials.onclick')

    <div class="kx-bg"></div>
    <div class="kx-blob b1"></div>
    <div class="kx-blob b2"></div>

    <main class="kx-card">
        <div class="kx-logo-wrap">
            <img src="{{ asset('images/logos/kahsei.jpg') }}" alt="KAHSEI logo">
        </div>
        <h1>KAHSEI</h1>
        <p class="kx-sub">Welcome back! Please sign in to continue.</p>

        @if(session('errorMessage'))
            <div class="kx-error" role="alert">
                <span class="fa fa-exclamation-triangle"></span>
                <span>{{ session('errorMessage') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ action('App\Http\Controllers\LoginController@validate_user') }}">
            {{ csrf_field() }}

            <div class="kx-field">
                <input class="form-control" type="email" id="email" name="email" placeholder=" " autocomplete="username"
                    autofocus required />
                <label for="email">E-Mail</label>
                <span class="fa fa-envelope-o kx-ico"></span>
            </div>

            <div class="kx-field">
                <input class="form-control" type="password" id="password" name="password" placeholder=" "
                    autocomplete="current-password" required />
                <label for="password">Password</label>
                <span class="fa fa-lock kx-ico"></span>
                <button type="button" class="kx-eye" id="togglePassword" aria-label="Show password" tabindex="-1">
                    <span class="fa fa-eye"></span>
                </button>
            </div>

            <div class="kx-forgot">
                <a href="#" data-toggle="modal" data-target="#forgotPasswordModal">Forgot Password?</a>
            </div>

            <button class="kx-btn" type="submit">
                LOGIN <span class="fa fa-arrow-right ml-1"></span>
            </button>
        </form>

        <div class="kx-divider">or</div>

        <a href="{{ url('client/registration') }}" class="kx-signup">
            Don't have an account? <strong>Sign Up</strong>
        </a>

        <p class="kx-foot">&copy; {{ date('Y') }} KAHSEI. All rights reserved.</p>
    </main>

    <!-- Forgot Password Modal -->
    <div class="modal fade kx-modal" id="forgotPasswordModal" tabindex="-1" role="dialog"
        aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form method="POST" action="{{ action('App\Http\Controllers\LoginController@forgot_password') }}">
                    {{ csrf_field() }}
                    <div class="modal-header">
                        <h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span class="fa fa-times" aria-hidden="true"></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="kx-fp-icon"><span class="fa fa-key"></span></div>
                        <p class="text-center mb-4" style="color:var(--text-dim);font-size:.85rem;">
                            Enter your registered email address or mobile number. We'll reset your password to a
                            temporary one.
                        </p>
                        <div class="kx-field mb-2">
                            <input type="text" class="form-control" id="identifier" name="identifier" placeholder=" "
                                required>
                            <label for="identifier">Email or Mobile Number</label>
                            <span class="fa fa-user-o kx-ico"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-reset">
                            <span class="fa fa-refresh mr-1"></span> Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('login/js/jquery.min.js') }}"></script>
    <script src="{{ asset('login/js/popper.js') }}"></script>
    <script src="{{ asset('login/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('login/js/main.js') }}"></script>
    <script>
        (function () {
            var btn = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            if (btn && input) {
                btn.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                    btn.querySelector('.fa').className = 'fa ' + (show ? 'fa-eye-slash' : 'fa-eye');
                });
            }

            $('#forgotPasswordModal').on('shown.bs.modal', function () {
                document.getElementById('identifier').focus();
            });
        })();
    </script>
</body>

</html>