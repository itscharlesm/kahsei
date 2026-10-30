<script>
    document.addEventListener('click', function (e) {

        const btn = e.target.closest('button[type="submit"]:not(.no-auto-submit)');
        if (!btn) return;

        const form = btn.closest('form');
        if (!form) return;

        // Keep HTML5 validation
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Prevent double submit
        if (btn.disabled) {
            e.preventDefault();
            return;
        }

        setTimeout(function () {
            btn.disabled = true;

            const icon = btn.querySelector('.fa');
            if (icon) {
                icon.classList.add('fa-spin');
            }
        }, 0);

    });
</script>

{{-- Excluded button --}}
{{-- <button type="submit" class="no-auto-submit"> --}}