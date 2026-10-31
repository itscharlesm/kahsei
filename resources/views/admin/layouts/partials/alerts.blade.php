<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@php
    $toasts = [
        'errorMessage' => ['icon' => 'error', 'timer' => 10000],
        'successMessage' => ['icon' => 'success', 'timer' => 5000],
        'infoMessage' => ['icon' => 'info', 'timer' => 3000],
        'warningMessage' => ['icon' => 'warning', 'timer' => 10000],
    ];
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        @foreach ($toasts as $key => $config)
            @if (session($key))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: {{ $config['timer'] }},
                    icon: '{{ $config['icon'] }}',
                    title: @json(session($key))
                });
            @endif
        @endforeach
    });
</script>