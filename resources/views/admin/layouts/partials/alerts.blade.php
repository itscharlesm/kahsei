<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if (session('errorMessage'))
    <script>
        $(function() {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 10000
            });
            Toast.fire({
                icon: 'error',
                title: '{{ session('errorMessage') }}'
            });
        });
    </script>
@endif

@if (session('successMessage'))
    <script>
        $(function() {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000
            });
            Toast.fire({
                icon: 'success',
                title: '{{ session('successMessage') }}'
            });
        });
    </script>
@endif

@if (session('infoMessage'))
    <script>
        $(function() {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            Toast.fire({
                icon: 'info',
                title: '{{ session('infoMessage') }}'
            });
        });
    </script>
@endif

@if (session('warningMessage'))
    <script>
        $(function() {
            var Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 10000
            });
            Toast.fire({
                icon: 'warning',
                title: '{{ session('warningMessage') }}'
            });
        });
    </script>
@endif