@include('admin.layouts.partials.auth')
<!DOCTYPE html>
<html lang="en">

<head>
    @include('admin.layouts.partials.head')
    @yield('page_styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        @include('sweetalert::alert')
        {{-- @include('admin.layouts.partials.preloader') --}}
        @include('admin.layouts.partials.navbar')
        @include('admin.layouts.partials.sidebar')
        @include('admin.layouts.partials.modals')
        <div class="content-wrapper">
            @yield('content')
            <a class="btn btn-primary back-to-top no-print" id="back-to-top" role="button" aria-label="Scroll to top"
                href="#">
                <i class="fas fa-chevron-up"></i>
            </a>
        </div>
        @include('admin.layouts.partials.footer')
        @include('admin.layouts.partials.controlsidebar')
    </div>
    @include('admin.layouts.partials.scripts')
    @yield('page_scripts')
</body>

</html>
