<aside class="main-sidebar sidebar-dark-red elevation-4">
    {{-- Brand Logo --}}
    <a href="#" class="brand-link d-flex align-items-center">
        <img src="{{ asset('images/logos/kahsei.png') }}" alt="Mendoza Cafe logo" class="brand-image"
            style="width:35px; height:35px; object-fit:cover; margin-right:10px;">

        <span class="brand-text font-weight-light" style="line-height:1.2;">
            KAHSEI
        </span>
    </a>

    {{-- Sidebar --}}
    <div class="sidebar">
        {{-- Sidebar user panel --}}
        <div class="user-panel d-flex align-items-center py-2">
            <div class="image me-2">
                <img src="{{ asset(getAvatar(session('usr_id'))) }}" class="img-circle elevation-2" alt="User Image"
                    style="width:40px; height:40px;">
            </div>
            <div class="info p-2">
                <a href="javascript:void(0)" class="d-block mb-0 fw-semibold">
                    {{ session('usr_first_name') }}
                </a>
                @if (session('utyp_id') == 1)
                    <small class="text-muted d-block" style="line-height:1;">
                        {{ session('role_name') }}
                    </small>
                @endif
            </div>
        </div>

        {{-- Sidebar Menu --}}
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                @if (session('utyp_id') == 1)
                    {{-- Home --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\AdminController@home') }}"
                            class="nav-link {{ request()->is('home') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>HOME</p>
                        </a>
                    </li>

                    {{-- Messages --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\MessageController@main') }}"
                            class="nav-link {{ request()->is('messages*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>MESSAGES</p>
                        </a>
                    </li>

                    {{-- Account --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\UserController@account') }}"
                            class="nav-link {{ request()->is('account') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>ACCOUNT</p>
                        </a>
                    </li>

                    {{-- PROFILING --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('PROFILING OFFICER') == '1')
                        <li class="nav-header">PROFILING</li>

                        <li class="nav-item {{ request()->is('profiling*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('profiling*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>
                                    MANAGE
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">

                                <li class="nav-item">
                                    <a href="{{ action('App\Http\Controllers\ProfilingController@users_active') }}"
                                        class="nav-link {{ request()->is('profiling/users*') ? 'active' : '' }}">
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
                                    class="nav-link {{ request()->is('management/addresses*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-map-marker-alt"></i>
                                    <p>ADDRESSES</p>
                                </a>
                            </li>
                        @endif

                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                            <li class="nav-item {{ request()->is('histories/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->is('histories/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-history"></i>
                                    <p>
                                        LOGS
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ManagementController@login_histories') }}"
                                            class="nav-link {{ request()->is('histories/logins*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>LOGINS</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ManagementController@user_histories') }}"
                                            class="nav-link {{ request()->is('histories/users*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>USERS</p>
                                        </a>
                                    </li>

                                </ul>
                            </li>
                        @endif
                    @endif
                @endif

                {{-- ! Signout --}}
                <li class="nav-item">
                    <a href="{{ action('App\Http\Controllers\LoginController@logout') }}" class="nav-link">
                        <i class="nav-icon fas fa-sign-out"></i>
                        <p>
                            SIGN OUT
                        </p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>