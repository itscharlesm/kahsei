@include('admin.layouts.partials.auth')
<!DOCTYPE html>
<html lang="en">

<head>
    @include('admin.layouts.partials.head')
    @yield('page_styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        @include('sweetalert::alert')
        @include('admin.layouts.partials.navbar')
        @include('admin.layouts.partials.sidebar')
        @include('admin.layouts.partials.modals')

        <main class="app-main">
            @yield('content')
            <a class="btn btn-danger position-fixed bottom-0 end-0 m-3 z-3 d-none no-print" id="back-to-top"
                role="button" aria-label="Scroll to top" href="#">
                <i class="fas fa-chevron-up"></i>
            </a>
        </main>

        @include('admin.layouts.partials.footer')
    </div>
    @include('admin.layouts.partials.scripts')
    @yield('page_scripts')
</body>

</html>