@php
     Assets::addScripts(['survey'])->addScriptsDirectly('/vendor/core/core/base/js/survey.js');
     Assets::addStyles(['survey'])->addStylesDirectly('/vendor/core/core/base/css/survey.css');
@endphp
{!! Form::open($attributes->getAttributes()) !!}
    {{ $slot }}
{!! Form::close() !!}
