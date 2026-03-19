<div
    class="collapse navbar-collapse"
    id="sidebar-menu"
>
    @include('core/base::layouts.partials.navbar-nav', [
        'autoClose' => 'false',
    ])
    @if(auth()->check() && auth()->user()->isSuperUser())  
    <div class="sidebar-toggler-wrapper d-none d-lg-block mt-4">
        <button type="button" id="ai-copilot-toggle-button" class="btn btn-primary sidebar-toggler " data-bs-toggle="tooltip" data-bs-placement="right" title="{{ trans('core/base::layouts.collapse_sidebar') }}">
            <i class="mdi mdi-backburger"></i>
            AI Alphasky Copilot
        </button>
    </div>
    @endif
</div>

