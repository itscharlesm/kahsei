{{-- jQuery (needed by Select2, DataTables, daterangepicker, Summernote) --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

{{-- Bootstrap 5 + OverlayScrollbars + AdminLTE 4 --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script
    src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc4/dist/js/adminlte.min.js"></script>

{{-- Moment + daterangepicker --}}
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

{{-- Select2 --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

{{-- Summernote --}}
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

{{-- Charts (Chart.js loaded once) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

{{-- DataTables + export buttons --}}
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sidebar scrollbar
        const sidebarWrapper = document.querySelector('.sidebar-wrapper');
        if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: 'os-theme-light',
                    autoHide: 'leave',
                    clickScroll: true
                }
            });
        }

        // Back to top
        const btt = document.getElementById('back-to-top');
        window.addEventListener('scroll', () => {
            btt?.classList.toggle('d-none', window.scrollY <= 200);
        });
        btt?.addEventListener('click', e => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    $(function () {
        // Select2
        $('.select2').select2();

        // Select2 inside modals
        $('.select3').each(function () {
            $(this).select2({ dropdownParent: $(this).closest('.modal') });
        });
        $('.modal').on('shown.bs.modal', function () {
            $(this).find('.select3').each(function () {
                $(this).select2({ dropdownParent: $(this).closest('.modal') });
            });
        });

        // DataTables
        $('#RegTable').DataTable();

        const baseOpts = {
            searching: false,
            dom: '<"top"lf>rt<"bottom"ip><"clear">'
        };
        $('#profilingTable, #defaultTable').DataTable({ ...baseOpts, lengthMenu: [25, 50, 100] });
        $('#logsTable').DataTable({ ...baseOpts, lengthMenu: [50, 100, 1000] });
    });
</script>