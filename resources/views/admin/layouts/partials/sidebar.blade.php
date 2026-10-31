<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="#" class="brand-link">
            <img src="{{ asset('images/logos/kahsei.png') }}" alt="KAHSEI logo" class="brand-image"
                style="width:35px; height:35px; object-fit:cover;">
            <span class="brand-text fw-light">KAHSEI</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        {{-- User panel --}}
        <div class="d-flex align-items-center px-3 py-2">
            <img src="{{ asset(getAvatar(session('usr_id'))) }}" class="rounded-circle shadow me-2" alt="User Image"
                style="width:40px; height:40px; object-fit:cover;">
            <div>
                <a href="javascript:void(0)" class="d-block fw-semibold text-white text-decoration-none">
                    {{ session('usr_first_name') }}
                </a>
                @if (session('utyp_id') == 1)
                    <small class="text-secondary d-block" style="line-height:1;">{{ session('role_name') }}</small>
                @endif
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                @if (session('utyp_id') == 1)
                    {{-- Home --}}
                    <li class="nav-item">
                        <a href="{{ url('home') }}" class="nav-link {{ request()->is('home') ? 'active bg-success' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>HOME</p>
                        </a>
                    </li>

                    {{-- Messages --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\MessageController@main') }}"
                            class="nav-link {{ request()->is('messages*') ? 'active bg-success' : '' }}">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>MESSAGES</p>
                        </a>
                    </li>

                    {{-- Account --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\UserController@account') }}"
                            class="nav-link {{ request()->is('account') ? 'active bg-success' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>ACCOUNT</p>
                        </a>
                    </li>

                    {{-- PROFILING --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                        <li class="nav-header">PROFILING</li>

                        <li class="nav-item {{ request()->is('profiling*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('profiling*') ? 'active bg-success' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    MANAGE
                                    <i class="nav-arrow fas fa-angle-right"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ action('App\Http\Controllers\ProfilingController@users_active') }}"
                                        class="nav-link {{ request()->is('profiling/users*') ? 'active bg-success' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>USERS</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- MANAGEMENT --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('DATA OFFICER') == '1' || session('ACCOUNTING OFFICER') == '1')
                        <li class="nav-header">MANAGEMENT</li>

                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('DATA OFFICER') == '1')
                            {{-- Addresses --}}
                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ManagementController@addresses_active') }}"
                                    class="nav-link {{ request()->is('management/addresses*') ? 'active bg-success' : '' }}">
                                    <i class="nav-icon fas fa-map-marker-alt"></i>
                                    <p>ADDRESSES</p>
                                </a>
                            </li>
                        @endif

                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                            {{-- Logs --}}
                            <li class="nav-item {{ request()->is('admin/histories/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->is('admin/histories/*') ? 'active bg-success' : '' }}">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>
                                        LOGS
                                        <i class="nav-arrow fas fa-angle-right"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ManagementController@login_histories') }}"
                                            class="nav-link {{ request()->is('admin/histories/logins*') ? 'active bg-success' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>LOGINS</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ManagementController@user_histories') }}"
                                            class="nav-link {{ request()->is('admin/histories/users*') ? 'active bg-success' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>USERS</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif
                @endif

                {{-- Signout --}}
                <li class="nav-item">
                    <a href="{{ action('App\Http\Controllers\LoginController@logout') }}" class="nav-link">
                        <i class="nav-icon fas fa-sign-out"></i>
                        <p>SIGN OUT</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>