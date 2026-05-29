@extends('layouts.app')

@section('content')
    @livewire('public.menu', ['tenant' => $tenant, 'token' => request()->query('token')])
@endsection
