<nav class="app-header navbar navbar-expand bg-success" data-bs-theme="dark">
    <div class="container-fluid">
        {{-- Left navbar links --}}
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        {{-- Right navbar links --}}
        <ul class="navbar-nav ms-auto">
            {{-- Fullscreen --}}
            <li class="nav-item">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                    <i data-lte-icon="maximize" class="fas fa-expand-arrows-alt"></i>
                    <i data-lte-icon="minimize" class="fas fa-compress-arrows-alt" style="display:none;"></i>
                </a>
            </li>

            {{-- User dropdown --}}
            <li class="nav-item dropdown">
                <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                    <i class="far fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end" data-bs-theme="light">
                    <span class="dropdown-item dropdown-header">User Options</span>
                    <div class="dropdown-divider"></div>

                    @if (session('utyp_id') == 1)
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#userPasswordModal"
                            class="dropdown-item">
                            <i class="fas fa-key me-2"></i> Change Password
                        </a>
                    @endif

                    @if (session('utyp_id') == 3)
                        <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#userClientPasswordModal"
                            class="dropdown-item">
                            <i class="fas fa-key me-2"></i> Change Password
                        </a>
                    @endif

                    <div class="dropdown-divider"></div>
                    <a href="{{ action('App\Http\Controllers\LoginController@logout') }}" class="dropdown-item">
                        <i class="fas fa-sign-out me-2"></i> Sign out
                    </a>
                </div>
            </li>
        </ul>
    </div>
</nav>