@include('layouts.partials.auth')
<!DOCTYPE html>
<html lang="en">

<head>
    @include('layouts.partials.head')
    @yield('page_styles')
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        @include('sweetalert::alert')
        {{-- @include('layouts.partials.preloader') --}}
        @include('layouts.partials.navbar')
        @include('layouts.partials.sidebar')
        @include('layouts.partials.modals')
        <div class="content-wrapper">
            @yield('content')
            <a class="btn btn-primary back-to-top no-print" id="back-to-top" role="button" aria-label="Scroll to top"
                href="#">
                <i class="fas fa-chevron-up"></i>
            </a>
        </div>
        @include('layouts.partials.footer')
        @include('layouts.partials.controlsidebar')
    </div>
    @include('layouts.partials.scripts')
    @yield('page_scripts')
</body>

</html>
