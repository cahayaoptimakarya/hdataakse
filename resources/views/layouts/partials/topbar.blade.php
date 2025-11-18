<div id="kt_header" class="header" data-kt-sticky="true" data-kt-sticky-name="header" data-kt-sticky-offset="{default: '120px', lg: '170px'}">
    <div class="container-xxl d-flex flex-column gap-3 py-3">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-icon btn-active-color-primary w-30px h-30px d-lg-none" id="kt_header_menu_mobile_toggle">
                    <span class="svg-icon svg-icon-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M21 7H3C2.4 7 2 6.6 2 6V4C2 3.4 2.4 3 3 3H21C21.6 3 22 3.4 22 4V6C22 6.6 21.6 7 21 7Z" fill="black" />
                            <path opacity="0.3" d="M21 14H3C2.4 14 2 13.6 2 13V11C2 10.4 2.4 10 3 10H21C21.6 10 22 10.4 22 11V13C22 13.6 21.6 14 21 14ZM22 20V18C22 17.4 21.6 17 21 17H3C2.4 17 2 17.4 2 18V20C2 20.6 2.4 21 3 21H21C21.6 21 22 20.6 22 20Z" fill="black" />
                        </svg>
                    </span>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <img alt="Logo" src="{{ asset('metronic/media/logos/logo-demo11.svg') }}" class="h-24px h-lg-30px" />
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="d-none d-lg-flex align-items-center">
                    <div class="input-group input-group-solid border rounded w-250px">
                        <span class="input-group-text bg-transparent border-0 ps-3">
                            <span class="svg-icon svg-icon-2 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path opacity="0.3" d="M21.7 18.9L18.6 15.8C17.9 16.9 16.9 17.9 15.8 18.6L18.9 21.7C19.3 22.1 19.9 22.1 20.3 21.7L21.7 20.3C22.1 19.9 22.1 19.3 21.7 18.9Z" fill="black" />
                                    <path d="M11 20C6 20 2 16 2 11C2 6 6 2 11 2C16 2 20 6 20 11C20 16 16 20 11 20ZM11 4C7.1 4 4 7.1 4 11C4 14.9 7.1 18 11 18C14.9 18 18 14.9 18 11C18 7.1 14.9 4 11 4Z" fill="black" />
                                </svg>
                            </span>
                        </span>
                        <input type="text" class="form-control border-0 ps-0" placeholder="Quick Search" />
                    </div>
                </div>
                <button class="btn btn-icon btn-outline btn-outline-dashed btn-outline-default">
                    <span class="svg-icon svg-icon-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <rect x="8" y="9" width="3" height="10" rx="1.5" fill="black" />
                            <rect opacity="0.5" x="13" y="5" width="3" height="14" rx="1.5" fill="black" />
                            <rect x="18" y="11" width="3" height="8" rx="1.5" fill="black" />
                            <rect x="3" y="13" width="3" height="6" rx="1.5" fill="black" />
                        </svg>
                    </span>
                </button>
                <button class="btn btn-icon btn-outline btn-outline-dashed btn-outline-default">
                    <span class="svg-icon svg-icon-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M6.28548 15.0861C7.34369 13.1814 9.35142 12 11.5304 12H12.4696C14.6486 12 16.6563 13.1814 17.7145 15.0861L19.3493 18.0287C20.0899 19.3618 19.1259 21 17.601 21H6.39903C4.87406 21 3.91012 19.3618 4.65071 18.0287L6.28548 15.0861Z" fill="black" />
                            <rect opacity="0.3" x="8" y="3" width="8" height="8" rx="4" fill="black" />
                        </svg>
                    </span>
                </button>
                <span class="btn btn-success fw-bolder px-3">3</span>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center flex-grow-1" id="kt_header_nav">
                @include('layouts.partials.menu')
            </div>
            <a href="https://preview.keenthemes.com/metronic8/demo11/documentation/getting-started.html" target="_blank" class="btn btn-sm btn-light-success fw-bold">Docs &amp; Components</a>
        </div>
    </div>
</div>
