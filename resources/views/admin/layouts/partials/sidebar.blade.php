<aside class="main-sidebar sidebar-dark-red elevation-4">
    {{-- Brand Logo --}}
    <a href="#" class="brand-link d-flex align-items-center">
        <img src="{{ asset('images/logos/logo_red.png') }}" alt="Mendoza Cafe logo" class="brand-image"
            style="width:35px; height:35px; object-fit:cover; margin-right:10px;">

        <span class="brand-text font-weight-light" style="line-height:1.2;">
            GO FORWARD <br>
            <small style="font-size: 10px;">PEST CONTROL - </small>
            <small style="font-size: 10px;">{{ session('branch_name') }} BRANCH</small>
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
                                    <a href="{{ action('App\Http\Controllers\ProfilingController@clients_active') }}"
                                        class="nav-link {{ request()->is('profiling/clients*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>CLIENTS</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="{{ action('App\Http\Controllers\ProfilingController@technicians_active') }}"
                                        class="nav-link {{ request()->is('profiling/technicians*') ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>TECHNICIANS</p>
                                    </a>
                                </li>

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

                    {{-- SERVICE ORDER --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('ACCOUNTING OFFICER') == '1' || session('BILLING OFFICER') == '1' || session('APPOINTMENT CLERK') == '1' || session('BOOKING OFFICER') == '1' || session('ASSESSMENT OFFICER') == '1' || session('SCHEDULING OFFICER') == '1' || session('SALES OFFICER') == '1' || session('QUALITY OFFICER') == '1')
                        <li class="nav-header">SERVICE ORDER</li>

                        {{-- APPOINTMENTS --}}
                        <li class="nav-item {{ request()->is('service/order/appointments/*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('service/order/appointments/*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calendar-alt"></i>
                                <p>
                                    APPOINTMENTS
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">

                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('BOOKING OFFICER') == '1' || session('SALES OFFICER') == '1')
                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@clients') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/clients*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>BOOK</p>
                                        </a>
                                    </li>
                                @endif

                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('ACCOUNTING OFFICER') == '1' || session('BILLING OFFICER') == '1' || session('APPOINTMENT CLERK') == '1' || session('ASSESSMENT OFFICER') == '1' || session('SCHEDULING OFFICER') == '1' || session('SALES OFFICER') == '1' || session('QUALITY OFFICER') == '1')
                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@requested_appointments') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/requested*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>REQUESTED</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@scheduling_appointments') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/scheduling*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>SCHEDULING</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@ongoing_appointments') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/ongoing*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>ONGOING</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@finished_appointments') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/finished*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>FINISHED SERVICES</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@completed_appointments') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/completed*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>COMPLETED</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AppointmentController@deleted_appointments') }}"
                                            class="nav-link {{ request()->is('service/order/appointments/deleted*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>DELETED</p>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>

                        {{-- ACCOUNTING --}}
                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('ACCOUNTING OFFICER') == '1' || session('BILLING OFFICER') == '1')
                            <li class="nav-item {{ request()->is('accounting/*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->is('accounting/*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-money-bill"></i>
                                    <p>
                                        ACCOUNTING
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AccountingController@accounting_payments') }}"
                                            class="nav-link {{ request()->is('accounting/payments*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>PAYMENTS</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AccountingController@accounting_history') }}"
                                            class="nav-link {{ request()->is('accounting/history/payments*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>HISTORY</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\AccountingController@accounting_voided') }}"
                                            class="nav-link {{ request()->is('accounting/history/voided*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>VOIDED</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    @endif

                    {{-- REPORTS --}}
                    @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('ACCOUNTING OFFICER') == '1')
                        <li class="nav-header">STATISTICS</li>

                        {{-- REPORTS --}}
                        <li class="nav-item {{ request()->is('reports/*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->is('reports/*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-pie-chart"></i>
                                <p>
                                    REPORTS
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">

                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('ACCOUNTING OFFICER') == '1')
                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@balance_report') }}"
                                            class="nav-link {{ request()->is('reports/balance*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>BALANCE</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@income_report') }}"
                                            class="nav-link {{ request()->is('reports/income*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>INCOME</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@void_report') }}"
                                            class="nav-link {{ request()->is('reports/void*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>VOID</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@client_report') }}"
                                            class="nav-link {{ request()->is('reports/client*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>CLIENT</p>
                                        </a>
                                    </li>
                                @endif

                                @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('QUALITY OFFICER') == '1')
                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@pest_captured_report') }}"
                                            class="nav-link {{ request()->is('reports/pest/captured*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>PEST</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@service_package_report') }}"
                                            class="nav-link {{ request()->is('reports/service/package*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>PACKAGE</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@chemical_report') }}"
                                            class="nav-link {{ request()->is('reports/chemical*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>CHEMICAL</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@technician_report') }}"
                                            class="nav-link {{ request()->is('reports/technician*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>TECHNICIAN</p>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="{{ action('App\Http\Controllers\ReportController@turn_around_time_report') }}"
                                            class="nav-link {{ request()->is('reports/turn-around-time*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>TURN AROUND TIME</p>
                                        </a>
                                    </li>
                                @endif

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

                            {{-- Chemicals --}}
                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ManagementController@chemicals_active') }}"
                                    class="nav-link {{ request()->is('management/chemicals*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-flask"></i>
                                    <p>CHEMICALS</p>
                                </a>
                            </li>

                            {{-- Branches --}}
                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ManagementController@branches_active') }}"
                                    class="nav-link {{ request()->is('management/branches*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-location"></i>
                                    <p>BRANCHES</p>
                                </a>
                            </li>
                        @endif

                        @if (session('SUPERADMIN') == '1' || session('ADMIN') == '1' || session('MANAGER') == '1' || session('ACCOUNTING OFFICER') == '1')
                            {{-- Services --}}
                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ManagementController@services_active') }}"
                                    class="nav-link {{ request()->is('management/services*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-list"></i>
                                    <p>SERVICES</p>
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
                                        <a href="{{ action('App\Http\Controllers\ManagementController@price_histories') }}"
                                            class="nav-link {{ request()->is('histories/prices*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>PRICES</p>
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

                @if (session('utyp_id') == 3)
                    {{-- Home --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\ClientController@home') }}"
                            class="nav-link {{ request()->is('client/home') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>HOME</p>
                        </a>
                    </li>

                    {{-- Account --}}
                    <li class="nav-item">
                        <a href="{{ action('App\Http\Controllers\ClientController@account') }}"
                            class="nav-link {{ request()->is('client/account') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>ACCOUNT</p>
                        </a>
                    </li>

                    {{-- SERVICE ORDER --}}
                    <li class="nav-header">SERVICE ORDER</li>

                    {{-- APPOINTMENTS --}}
                    <li class="nav-item {{ request()->is('client/service/order*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('client/service/order*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>
                                APPOINTMENTS
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@appointment_book') }}"
                                    class="nav-link {{ request()->is('client/service/order/book*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>BOOK APPOINTMENT</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@requested_appointments') }}"
                                    class="nav-link {{ request()->is('client/service/order/appointments/requested*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>REQUESTED</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@assessed_appointments') }}"
                                    class="nav-link {{ request()->is('client/service/order/appointments/assessed*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>ASSESSED</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@scheduled_appointments') }}"
                                    class="nav-link {{ request()->is('client/service/order/appointments/scheduled*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>SCHEDULED</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@ongoing_appointments') }}"
                                    class="nav-link {{ request()->is('client/service/order/appointments/ongoing*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>ONGOING</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@finished_appointments') }}"
                                    class="nav-link {{ request()->is('client/service/order/appointments/finished*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>FINISHED SERVICES</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@completed_appointments') }}"
                                    class="nav-link {{ request()->is('client/service/order/appointments/completed*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>COMPLETED</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- REPORTS --}}
                    <li class="nav-header">STATISTICS</li>
                    <li class="nav-item {{ request()->is('client/reports/*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->is('client/reports/*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-pie-chart"></i>
                            <p>
                                REPORTS
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview">

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@client_report') }}"
                                    class="nav-link {{ request()->is('client/reports/client*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>MY SERVICES</p>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ action('App\Http\Controllers\ClientController@balance_report') }}"
                                    class="nav-link {{ request()->is('client/reports/balance*') ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>MY BALANCES</p>
                                </a>
                            </li>

                        </ul>
                    </li>

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