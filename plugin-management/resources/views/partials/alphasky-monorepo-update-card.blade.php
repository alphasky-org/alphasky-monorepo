<div class="d-flex flex-column gap-3">
    <div>
        <h4 class="mb-1">{{ trans('packages/plugin-management::plugin.alphasky_monorepo.title') }}</h4>
        <p class="text-muted mb-0">{{ trans('packages/plugin-management::plugin.alphasky_monorepo.description') }}</p>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="text-muted small">{{ trans('packages/plugin-management::plugin.alphasky_monorepo.current_version') }}</div>
            <div class="fw-semibold">{{ $status['local_version'] ?: trans('packages/plugin-management::plugin.alphasky_monorepo.unknown_version') }}</div>
        </div>

        <div class="col-12 col-md-6">
            <div class="text-muted small">{{ trans('packages/plugin-management::plugin.alphasky_monorepo.latest_version') }}</div>
            <div class="fw-semibold">{{ $status['remote_version'] ?: trans('packages/plugin-management::plugin.alphasky_monorepo.unavailable_version') }}</div>
        </div>
    </div>

    @if ($status['update_available'])
        <x-core::alert
            type="warning"
            class="mb-0"
        >
            {{ trans('packages/plugin-management::plugin.alphasky_monorepo.update_available') }}
        </x-core::alert>

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
        @else
            <x-core::alert
                type="danger"
                class="mb-0"
            >
                {{ trans('packages/plugin-management::plugin.alphasky_monorepo.not_git_repository') }}
            </x-core::alert>
        @endif
    @else
        <x-core::alert
            type="success"
            class="mb-0"
        >
            {{ trans('packages/plugin-management::plugin.alphasky_monorepo.no_update', [
                'version' => $status['local_version'] ?: trans('packages/plugin-management::plugin.alphasky_monorepo.unknown_version'),
            ]) }}
        </x-core::alert>
    @endif
</div>
