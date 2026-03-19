@php
     Assets::addScripts(['survey'])->addScriptsDirectly('vendor/core/plugins/survey/js/survey.js');
     Assets::addStyles(['survey'])->addStylesDirectly('vendor/core/plugins/survey/css/survey.css');
@endphp
{!! Form::open($attributes->getAttributes()) !!}
    {{ $slot }}
{!! Form::close() !!}
