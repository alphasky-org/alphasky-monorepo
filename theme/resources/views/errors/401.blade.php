@extends('errors::minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Unauthorized'))

    <a href="{{ route('access.logout') }}" class="btn btn-primary mt-4">{{ __('Logout') }}</a>
