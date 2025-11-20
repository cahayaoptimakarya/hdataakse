@push('scripts')
<script>
    (function () {
        const confirmationText = {
            title: 'Apakah Anda yakin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            focusConfirm: false,
        };

        const loadingConfig = {
            title: 'Memproses...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            didOpen: () => {
                Swal.showLoading();
            },
        };

        const bindConfirmation = (form) => {
            if (!form || form.dataset.swalConfirmBound === '1') {
                return;
            }
            form.dataset.swalConfirmBound = '1';
            form.addEventListener('submit', function (event) {
                if (form.dataset.swalSubmitting === 'true') {
                    return;
                }
                event.preventDefault();
                Swal.fire(confirmationText).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire(loadingConfig);
                        setTimeout(() => {
                            form.dataset.swalSubmitting = 'true';
                            form.submit();
                        }, 1500);
                    }
                });
            });
        };

        const observeForms = () => {
            document.querySelectorAll('form').forEach(bindConfirmation);
        };

        document.addEventListener('DOMContentLoaded', () => {
            observeForms();
            const observer = new MutationObserver(observeForms);
            observer.observe(document.body, { childList: true, subtree: true });
        });
    })();
</script>
@endpush
