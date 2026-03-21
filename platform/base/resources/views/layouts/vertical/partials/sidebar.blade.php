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
            <img src="{{ asset('vendor/core/core/base/images/favicon.png') }}" alt="Alphasky" width="35" height="35">
            <span class="titlealphasky">AI Alphasky Copilot</span>
        </button>
    </div>
    @endif
</div>

