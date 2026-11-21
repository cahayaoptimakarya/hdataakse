<!DOCTYPE html>
<html lang="en">

<head>
    <base href="">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Dashboard') · Kontrol Biaya</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ asset('metronic/media/logos/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
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
    <link href="{{ asset('css/ui.css') }}?v=2" rel="stylesheet" type="text/css" />
    @stack('styles')
    @yield('styles')
    <meta name="description" content="Kontrol biaya: realisasi vs budget per divisi dan toko" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
</head>

<body id="kt_body" class="ui">
    @php
        $authUser = auth()->user();
        $initials = collect(explode(' ', trim($authUser?->name ?? 'U')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
        $pageTitle = trim($__env->yieldContent('page_title')) ?: (trim($__env->yieldContent('title')) ?: 'Dashboard');
    @endphp
    <div class="ui-app">
        @include('layouts.partials.sidebar')
        <div class="ui-main">
            <header class="ui-top">
                <button type="button" class="ui-toggle" id="ui_side_toggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
                <div class="ui-crumb">
                    @hasSection('page_breadcrumbs')
                        @yield('page_breadcrumbs')
                    @else
                        <a href="{{ route('admin.dashboard') }}">Beranda</a> / {{ $pageTitle }}
                    @endif
                    <strong>{{ $pageTitle }}</strong>
                </div>
                <div class="ui-sp"></div>
                @hasSection('page_actions')
                    <div class="d-none d-md-flex align-items-center gap-2">@yield('page_actions')</div>
                @endif
                <div class="ui-user" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                    <div class="ui-ava">{{ $initials }}</div>
                    <div class="who"><b>{{ $authUser?->name }}</b><small>{{ $authUser?->roles()->pluck('name')->first() ?? 'Pengguna' }}</small></div>
                    <i class="fa-solid fa-chevron-down ui-muted" style="font-size:11px"></i>
                </div>
                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-primary fw-bold py-3 fs-6 w-250px" data-kt-menu="true">
                    <div class="menu-item px-3">
                        <div class="menu-content px-3 py-2">
                            <div class="fw-bolder">{{ $authUser?->name }}</div>
                            <div class="text-muted fs-7">{{ $authUser?->email }}</div>
                        </div>
                    </div>
                    <div class="separator my-2"></div>
                    <div class="menu-item px-3"><a href="{{ route('profile.edit') }}" class="menu-link px-3">Profil & kata sandi</a></div>
                    <div class="menu-item px-3">
                        <a href="#" class="menu-link px-3" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Keluar</a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                    </div>
                </div>
            </header>
            <main class="ui-content" id="kt_content">
                @yield('content')
            </main>
            <footer class="ui-foot">{{ date('Y') }} · Kontrol Biaya</footer>
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
    <script>
        document.getElementById('ui_side_toggle')?.addEventListener('click', () => document.body.classList.toggle('ui-side-open'));
        document.addEventListener('click', (e) => {
            if (document.body.classList.contains('ui-side-open') && !e.target.closest('#ui_side') && !e.target.closest('#ui_side_toggle')) {
                document.body.classList.remove('ui-side-open');
            }
        });
    </script>
    @stack('scripts')
    @yield('scripts')
</body>

</html>
