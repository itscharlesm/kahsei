<div id="top-bar" class="top-bar">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-4">
                <ul class="top-info text-center text-md-left">
                    <li>
                        <a title="Infinit SMS Facebook account" href="https://www.facebook.com/infinitwebsolutions">
                            <span class="social-icon"><i class="fab fa-facebook"></i></span>
                        </a>
                        <a title="Infinit SMS Twitter Account" href="#">
                            <span class="social-icon"><i class="fab fa-twitter"></i></span>
                        </a>
                        <a title="Infinit SMS Youtube Account" href="#">
                            <span class="social-icon"><i class="fab fa-youtube"></i></span>
                        </a>
                        <a title="Infinit SMS Instagram Account" href="#">
                            <span class="social-icon"><i class="fab fa-instagram"></i></span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="col-lg-8 col-md-8 top-social text-center text-md-right">
                {{-- <ul class="list-unstyled">
                    <li>
                        @if(!session('usr_id'))
                            <a title="Employee Login" href="javascript:void(0)" data-toggle="modal" data-target="#employeeLoginModal" target="_BLANK" rel="noopener noreferrer">
                                Login
                            </a>
                        @else
                            <a title="Employee Logout" href="{{ action('App\Http\Controllers\LoginController@logout') }}">
                                Logout
                            </a>
                        @endif
                    </li>
                </ul> --}}
            </div>
        </div>
    </div>
</div>

<header id="header" class="header-one">
    <div class="bg-white">
        <div class="container">
            <div class="logo-area">
                <div class="row align-items-center">
                    <div class="logo col-lg-3 text-center text-lg-left mb-3 mb-md-5 mb-lg-0">
                        <a class="d-block" href="#" target="_BLANK" rel="noreferrer">
                            <img loading="lazy" src="{{ asset('images/logo.jpg') }}" alt="Infinit SMS logo">
                        </a>
                    </div>

                    <div class="col-lg-9 header-right">
                        <ul class="top-info-box">
                            <li>
                                <div class="info-box">
                                    <div class="info-box-content">
                                        <p class="info-box-title">Call Us</p>
                                        <a title="Infinit contact number" class="info-box-subtitle" href="callto:+639109005555">+63 9109005555</a>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <div class="info-box">
                                    <div class="info-box-content">
                                        <p class="info-box-title">Email Us</p>
                                        <a title="email us" class="info-box-subtitle" href="mailto:support@infinitwebsolutions.com" rel="noreferrer">support@infinitwebsolutions.com</a>
                                    </div>
                                </div>
                            </li>
                            <li class="header-get-a-quote">
                            @if(!session('usr_id'))
                                <a class="btn btn-primary" title="Employee Login" href="javascript:void(0)" data-toggle="modal" data-target="#employeeLoginModal" target="_BLANK" rel="noopener noreferrer">
                                    Login
                                </a>
                            @else
                                <a class="btn btn-primary" title="Employee Logout" href="{{ action('App\Http\Controllers\LoginController@logout') }}">
                                    Logout
                                </a>
                            @endif
                                {{-- <a title="Pricing" class="btn btn-primary" href="javascript:void(0)" data-toggle="modal" data-target="#" target="_BLANK" rel="noopener noreferrer">Login</a> --}}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="site-navigation">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <nav class="navbar navbar-expand-lg navbar-dark p-0">
                        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target=".navbar-collapse" aria-controls="navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>

                        <div id="navbar-collapse" class="collapse navbar-collapse">
                            <ul class="nav navbar-nav mr-auto">
                                <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                                    <a class="nav-link" href="#">Home</a>
                                </li>

                                <li class="nav-item {{ request()->is('features/*') ? 'active' : '' }}">
                                    <a class="nav-link" href="#">Features</a>
                                </li>

                                <li class="nav-item {{ request()->is('pricing/*') ? 'active' : '' }}">
                                    <a class="nav-link" href="#">Pricing</a>
                                </li>

                                <li class="nav-item {{ request()->is('subscribe/*') ? 'active' : '' }}">
                                    <a class="nav-link" href="#">Subscribe</a>
                                </li>

                                <li class="nav-item {{ request()->is('contact/*') ? 'active' : '' }}">
                                    <a class="nav-link" href="#">Contact Us</a>
                                </li>
                            </ul>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</header>
