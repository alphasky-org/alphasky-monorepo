@php
    $adminLocales = AdminHelper::getAdminLocales();
    $currentLocale = \Alphasky\Base\Facades\AdminAppearance::forCurrentUser()->getLocale();
@endphp

@if (count($adminLocales) > 1)
    <div class="nav-item dropdown">
        <a
            href="#"
            class="px-0 nav-link"
            title="{{ trans('core/setting::setting.admin_appearance.language') }}"
            data-bs-toggle="dropdown"
            data-bs-placement="bottom"
            aria-expanded="false"
        >
            <x-core::icon name="ti ti-language" />
        </a>

        <div class="dropdown-menu dropdown-menu-end">
            @foreach ($adminLocales as $locale => $language)
                <a
                    href="{{ route('toggle-admin-locale', ['locale' => $locale]) }}"
                    class="dropdown-item d-flex align-items-center justify-content-between @if($currentLocale === $locale) disabled @endif"
                >
                    <span>{{ $language }}</span>

                    @if ($currentLocale === $locale)
                        <x-core::icon name="ti ti-check" />
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endif

@if(AdminHelper::themeMode() === 'dark')
    <a
        href="{{ route('toggle-theme-mode', ['theme' => 'light']) }}"
        class="px-0 nav-link"
        title="{{ __('Enable light mode') }}"
        data-bs-toggle="tooltip"
        data-bs-placement="bottom"
    >
        <x-core::icon name="ti ti-sun" />
    </a>
@else
    <a
        href="{{ route('toggle-theme-mode', ['theme' => 'dark']) }}"
        class="px-0 nav-link"
        title="{{ __('Enable dark mode') }}"
        data-bs-toggle="tooltip"
        data-bs-placement="bottom"
    >
        <x-core::icon name="ti ti-moon" />
    </a>
@endif
