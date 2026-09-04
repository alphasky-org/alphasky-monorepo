<x-core::alert
    type="warning"
    class="mb-3"
>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
        <div>
            <strong>{{ trans('packages/plugin-management::plugin.alphasky_monorepo.update_available') }}</strong>
            <div class="text-muted small">
                {{ trans('packages/plugin-management::plugin.alphasky_monorepo.version_summary', [
                    'current' => $status['local_version'] ?: trans('packages/plugin-management::plugin.alphasky_monorepo.unknown_version'),
                    'latest' => $status['remote_version'],
                ]) }}
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <x-core::button
                tag="a"
                color="warning"
                :outlined="true"
                href="{{ route('settings.general') }}"
                icon="ti ti-settings"
            >
                {{ trans('packages/plugin-management::plugin.alphasky_monorepo.go_to_settings') }}
            </x-core::button>

            @if ($status['can_update'])
                <form
                    method="POST"
                    action="{{ route('plugins.alphasky-monorepo.update') }}"
                    class="m-0"
                >
                    @csrf
                    <x-core::button
                        type="submit"
                        color="warning"
                        icon="ti ti-refresh"
                    >
                        {{ trans('packages/plugin-management::plugin.alphasky_monorepo.update_now') }}
                    </x-core::button>
                </form>
            @endif
        </div>
    </div>
</x-core::alert>
