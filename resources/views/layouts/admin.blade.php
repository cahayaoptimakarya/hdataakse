<!DOCTYPE html>
<html lang="en">

<head>
    <base href="">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Import Analytics')</title>
    <link rel="shortcut icon" href="{{ asset('metronic/media/logos/favicon.png') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <link href="{{ asset('metronic/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .select2-container .select2-selection--single {
            height: 42px;
            padding: 0.5rem 1rem;
            border-radius: 0.475rem;
            border: 1px solid #e4e6ef;
        }

        .form-select.form-select-solid+.select2-container .select2-selection--single {
            background-color: #f5f8fa;
            border-color: #f5f8fa;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 12px;
        }

        .select2-container .select2-selection--multiple {
            min-height: 42px;
            border-radius: 0.475rem;
            border: 1px solid #e4e6ef;
        }
    </style>
    @stack('styles')
    @yield('styles')
    <meta name="description" content="Import Analytics dashboard" />
    <meta name="keywords" content="import analytics,dashboard,analytics" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
</head>

<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled">
    <div class="d-flex flex-column flex-root">
        <!--begin::Page-->
        <div class="page d-flex flex-row flex-column-fluid">
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                @include('layouts.partials.header')
                @include('layouts.partials.toolbar')

                {{-- <div class="toolbar py-5 py-lg-5" id="kt_toolbar">
                    <div id="kt_toolbar_container" class="container-xxl d-flex flex-stack flex-wrap">
                        <div class="page-title d-flex flex-column me-3">
                            <h1 class="d-flex text-dark fw-bolder my-1 fs-3">@yield('page_title', 'Dashboard')</h1>
                            <ul class="breadcrumb breadcrumb-dot fw-bold text-gray-600 fs-7 my-1">
                                @yield('page_breadcrumbs')
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 my-1">
                            @yield('page_actions')
                        </div>
                    </div>
                </div> --}}

                <div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
                    <!--begin::Post-->
                    <div class="content flex-row-fluid" id="kt_content">
                        @yield('content')

                    </div>
                </div>

                @include('layouts.partials.footer')
            </div>
        </div>
    </div>

    <script src="{{ asset('metronic/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('metronic/plugins/custom/datatables/datatables.bundle.js') }}"></script>
    <script>
        // Simple SweetAlert2 wrapper used by masterdata pages.
        window.AppSwal = window.AppSwal || (function () {
            const defaults = {
                confirm: {
                    icon: 'warning',
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-active-light'
                    }
                },
                error: {
                    icon: 'error',
                    buttonsStyling: false,
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                },
                success: {
                    icon: 'success',
                    buttonsStyling: false,
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                }
            };

            const ensureSwal = () => {
                if (!window.Swal || typeof window.Swal.fire !== 'function') {
                    console.error('SweetAlert2 (Swal) is not available');
                    return false;
                }
                return true;
            };

            const normalizeButtonTypes = (config, options) => {
                if (!options) return config;
                const customClass = Object.assign({}, config.customClass || {});
                if (!customClass.confirmButton && typeof options.confirmButtonClass === 'string') {
                    customClass.confirmButton = options.confirmButtonClass;
                }
                if (!customClass.confirmButton && typeof options.confirmButtonType === 'string') {
                    customClass.confirmButton = `btn btn-${options.confirmButtonType}`;
                }
                if (!customClass.cancelButton && typeof options.cancelButtonClass === 'string') {
                    customClass.cancelButton = options.cancelButtonClass;
                }
                if (Object.keys(customClass).length) {
                    config.customClass = customClass;
                }
                return config;
            };

            return {
                confirm: async (text, options = {}) => {
                    if (!ensureSwal()) return false;
                    const config = normalizeButtonTypes(Object.assign({}, defaults.confirm, options), options);
                    if (text !== undefined && text !== null) config.text = text;
                    const result = await window.Swal.fire(config);
                    return !!result.isConfirmed;
                },
                error: (text, options = {}) => {
                    if (!ensureSwal()) return;
                    const config = normalizeButtonTypes(Object.assign({}, defaults.error, options), options);
                    if (text !== undefined && text !== null) config.text = text;
                    return window.Swal.fire(config);
                },
                success: (text, options = {}) => {
                    if (!ensureSwal()) return;
                    const config = normalizeButtonTypes(Object.assign({}, defaults.success, options), options);
                    if (text !== undefined && text !== null) config.text = text;
                    return window.Swal.fire(config);
                }
            };
        })();
    </script>
    @stack('scripts')
    @yield('scripts')
</body>

</html>
