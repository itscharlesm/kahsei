<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home') | KAHSEI</title>
    <meta name="description" content="Kagalingan Advocates for Health, Sanitation, and Environment Inc. (KAHSEI)">
    <link rel="shortcut icon" href="{{ asset('images/logos/kahsei.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('homepage/css/homepage.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>
    @include('admin.vendor.sweetalert.alert')

    {{-- ============ Logo / name (top center) ============ --}}
    <header class="site-header">
        <span class="hdr-orb hdr-orb-1"></span>
        <span class="hdr-orb hdr-orb-2"></span>
        <span class="hdr-orb hdr-orb-3"></span>
        <div class="container text-center position-relative">
            <a href="{{ url('/') }}" class="brand">
                <span class="brand-logo">
                    <img src="{{ asset('images/logos/kahsei.png') }}" alt="KAHSEI logo">
                </span>
                <span class="brand-abbr">KAHSEI</span>
                <span class="brand-name">Kagalingan Advocates for Health, Sanitation, and Environment Inc.</span>
            </a>
            <ul class="brand-pillars list-unstyled">
                <li><i class="bi bi-heart-pulse-fill"></i> Health</li>
                <li><i class="bi bi-droplet-fill"></i> Sanitation</li>
                <li><i class="bi bi-tree-fill"></i> Environment</li>
            </ul>
        </div>
        <svg class="hdr-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,30 C240,70 480,0 720,28 C960,56 1200,70 1440,22 L1440,60 L0,60 Z"></path>
        </svg>
    </header>

    {{-- ============ Navigation ============ --}}
    @php
        $isHome = request()->is('/');
        $isAbout = request()->is('about') || request()->is('about/*');
        $isAttorney = request()->is('itanong-mo-kay-attorney/*');
    @endphp
    <nav class="navbar navbar-expand-lg site-nav sticky-top">
        <div class="container">
            <a class="navbar-brand d-lg-none" href="{{ url('/') }}">
                <img src="{{ asset('images/logos/kahsei.png') }}" alt="" width="34" height="34">
                <span>KAHSEI</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="mainNav">
                <ul class="navbar-nav align-items-lg-center gap-lg-1">
                    <li class="nav-item">
                        <a class="nav-link {{ $isHome ? 'active' : '' }}" href="{{ url('/') }}">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $isAbout ? 'active' : '' }}" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-info-circle"></i> About Us
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item {{ request()->is('about') ? 'active' : '' }}"
                                    href="{{ url('about') }}">About</a></li>
                            <li><a class="dropdown-item {{ request()->is('about/vision-mission') ? 'active' : '' }}"
                                    href="{{ url('about/vision-mission') }}">Vision / Mission</a></li>
                            <li><a class="dropdown-item {{ request()->is('about/goals-objectives') ? 'active' : '' }}"
                                    href="{{ url('about/goals-objectives') }}">Goals / Objectives</a></li>
                            <li><a class="dropdown-item {{ request()->is('about/board-of-members') ? 'active' : '' }}"
                                    href="{{ url('about/board-of-members') }}">Board of Members</a></li>
                            <li><a class="dropdown-item {{ request()->is('about/partners') ? 'active' : '' }}"
                                    href="{{ url('about/partners') }}">Partners</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ $isAttorney ? 'active' : '' }}" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-chat-square-quote"></i> Itanong mo kay Attorney
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item {{ request()->is('itanong-mo-kay-attorney/ask-question') ? 'active' : '' }}"
                                    href="{{ url('itanong-mo-kay-attorney/ask-question') }}">Ask Questions</a></li>
                            <li><a class="dropdown-item {{ request()->is('itanong-mo-kay-attorney/answers') ? 'active' : '' }}"
                                    href="{{ url('itanong-mo-kay-attorney/answers') }}">Answered Questions</a></li>
                        </ul>
                    </li>

                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <button type="button" class="btn btn-login" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    {{-- ============ Page banner (inner pages only) ============ --}}
    @hasSection('page_title')
        <section class="page-hero">
            <div class="container">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-2">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        @hasSection('crumb')
                            <li class="breadcrumb-item">@yield('crumb')</li>
                        @endif
                        <li class="breadcrumb-item active" aria-current="page">@yield('page_title')</li>
                    </ol>
                </nav>
                <h1>@yield('page_title')</h1>
                @hasSection('page_sub')
                    <p class="page-hero-sub">@yield('page_sub')</p>
                @endif
            </div>
        </section>
    @endif

    <main>
        @yield('content')
    </main>

    {{-- ============ Footer ============ --}}
    <footer class="site-footer">
        <div class="container text-center">
            <img class="footer-logo" src="{{ asset('images/logos/kahsei.png') }}" alt="KAHSEI">
            <p class="footer-name">Kagalingan Advocates for Health, Sanitation, and Environment Inc.</p>

            <ul class="social list-unstyled">
                <li><a href="https://www.facebook.com/profile.php?id=61579426943592" target="_blank" rel="noopener"
                        aria-label="Facebook"><i class="bi bi-facebook"></i></a></li>
                <li><a href="#" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                </li>
                <li><a href="#" target="_blank" rel="noopener" aria-label="Instagram"><i
                            class="bi bi-instagram"></i></a></li>
                <li><a href="#" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </li>
            </ul>

            <div class="footer-bottom">
                <p class="mb-1">
                    Copyright &copy; 2026{{ date('Y') > 2026 ? ' - ' . date('Y') : '' }}
                    <a href="https://www.facebook.com/profile.php?id=61579426943592" target="_blank"
                        rel="noopener">KAHSEI System</a>. All rights reserved.
                </p>
                <p class="mb-0">
                    Developed by
                    <a href="https://www.infinitwebsolutions.com/" target="_blank" rel="noopener">Advanced Infinit
                        Technology Solutions Inc.</a>
                </p>
            </div>
        </div>
    </footer>

    <button type="button" class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    {{-- ============ Login modal ============ --}}
    <div class="modal fade kx-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="login-top">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <span class="login-logo"><img src="{{ asset('images/logos/kahsei.png') }}" alt="KAHSEI"></span>
                    <h5 class="modal-title" id="loginModalLabel">Welcome back</h5>
                    <p>Sign in to continue to KAHSEI</p>
                </div>

                <form method="POST" action="{{ url('validate') }}">
                    {{ csrf_field() }}
                    <div class="modal-body">
                        @if (session('errorMessage'))
                            <div class="alert alert-danger d-flex align-items-center gap-2 py-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <div>{{ session('errorMessage') }}</div>
                            </div>
                        @endif

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="login_email" name="email" placeholder="E-Mail"
                                autocomplete="username" required>
                            <label for="login_email"><i class="bi bi-envelope me-1"></i> E-Mail</label>
                        </div>

                        <div class="input-group mb-2 kx-pass">
                            <div class="form-floating flex-grow-1">
                                <input type="password" class="form-control" id="login_password" name="password"
                                    placeholder="Password" autocomplete="current-password" required>
                                <label for="login_password"><i class="bi bi-lock me-1"></i> Password</label>
                            </div>
                            <button class="btn btn-outline-secondary" type="button"
                                data-toggle-password="#login_password" aria-label="Show password" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>

                        <div class="text-end mb-3">
                            <a href="#" class="small" data-bs-toggle="modal"
                                data-bs-target="#forgotPasswordModal">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-kx w-100">
                            LOGIN <i class="bi bi-arrow-right ms-1"></i>
                        </button>

                        <p class="text-center small text-muted mt-3 mb-1">
                            Don't have an account?
                            <a href="{{ url('client/registration') }}" class="fw-semibold">Sign Up</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============ Forgot password modal ============ --}}
    <div class="modal fade kx-modal" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="login-top">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <span class="login-logo icon"><i class="bi bi-key-fill"></i></span>
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password</h5>
                    <p>We'll reset it to a temporary password</p>
                </div>
                <form method="POST" action="{{ url('forgot/password') }}">
                    {{ csrf_field() }}
                    <div class="modal-body">
                        <p class="small text-muted text-center">
                            Enter your registered email address or mobile number.
                        </p>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="identifier" name="identifier"
                                placeholder="Email or Mobile Number" required>
                            <label for="identifier"><i class="bi bi-person me-1"></i> Email or Mobile Number</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light flex-fill" data-bs-toggle="modal"
                                data-bs-target="#loginModal">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="submit" class="btn btn-kx flex-fill">
                                <i class="bi bi-arrow-repeat"></i> Reset Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('homepage/js/homepage.js') }}"></script>
    @if (session('errorMessage'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('loginModal')).show();
            });
        </script>
    @endif
    @stack('scripts')
</body>

</html>