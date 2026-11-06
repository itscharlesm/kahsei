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
                        <a href="{{ url('admin/home') }}" class="nav-link {{ request()->is('admin/home') ? 'active bg-success' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>HOME</p>
                        </a>
                    </li>

                    {{-- Messages --}}
                    <li class="nav-item">
                        <a href="{{ url('messages') }}"
                            class="nav-link {{ request()->is('messages*') ? 'active bg-success' : '' }}">
                            <i class="nav-icon fas fa-comments"></i>
                            <p>MESSAGES</p>
                        </a>
                    </li>

                    {{-- Account --}}
                    <li class="nav-item">
                        <a href="{{ url('admin/account') }}"
                            class="nav-link {{ request()->is('admin/account') ? 'active bg-success' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>ACCOUNT</p>
                        </a>
                    </li>

                    {{-- HOMEPAGE --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                        <li class="nav-header">HOMEPAGE</li>

                        <li class="nav-item {{ request()->is('admin/homepage/*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('admin/homepage/*') ? 'active bg-success' : '' }}">
                                <i class="nav-icon fas fa-home"></i>
                                <p>
                                    PAGES
                                    <i class="nav-arrow fas fa-angle-right"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">
                                {{-- About --}}
                                <li class="nav-item">
                                    <a href="{{ url('admin/homepage/about') }}"
                                        class="nav-link {{ request()->is('admin/homepage/about*') ? 'active bg-success' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>ABOUT</p>
                                    </a>
                                </li>

                                {{-- Vision --}}
                                <li class="nav-item">
                                    <a href="{{ url('admin/homepage/vision') }}"
                                        class="nav-link {{ request()->is('admin/homepage/vision*') ? 'active bg-success' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>VISION</p>
                                    </a>
                                </li>

                                {{-- Mission --}}
                                <li class="nav-item">
                                    <a href="{{ url('admin/homepage/mission') }}"
                                        class="nav-link {{ request()->is('admin/homepage/mission*') ? 'active bg-success' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>MISSION</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- MANAGEMENT --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                        <li class="nav-header">MANAGEMENT</li>

                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1')
                            {{-- Addresses --}}
                            <li class="nav-item">
                                <a href="{{ url('admin/management/addresses/active') }}"
                                    class="nav-link {{ request()->is('admin/management/addresses*') ? 'active bg-success' : '' }}">
                                    <i class="nav-icon fas fa-map-marker-alt"></i>
                                    <p>ADDRESSES</p>
                                </a>
                            </li>
                            {{-- Positions --}}
                            <li class="nav-item">
                                <a href="{{ url('admin/management/positions/active') }}"
                                    class="nav-link {{ request()->is('admin/management/positions*') ? 'active bg-success' : '' }}">
                                    <i class="nav-icon fas fa-user-alt"></i>
                                    <p>POSITIONS</p>
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
                                        <a href="{{ url('admin/histories/logins') }}"
                                            class="nav-link {{ request()->is('admin/histories/logins*') ? 'active bg-success' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>LOGINS</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ url('admin/histories/users') }}"
                                            class="nav-link {{ request()->is('admin/histories/users*') ? 'active bg-success' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>USERS</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif

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
                                    <a href="{{ url('profiling/board-of-members/active') }}"
                                        class="nav-link {{ request()->is('profiling/board-of-members*') ? 'active bg-success' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>BOARD OF MEMBERS</p>
                                    </a>
                                </li>
                            </ul>

                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ url('profiling/users/active') }}"
                                        class="nav-link {{ request()->is('profiling/users*') ? 'active bg-success' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>USERS</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endif

                {{-- Signout --}}
                <li class="nav-item">
                    <a href="{{ url('logout') }}" class="nav-link">
                        <i class="nav-icon fas fa-sign-out"></i>
                        <p>SIGN OUT</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>