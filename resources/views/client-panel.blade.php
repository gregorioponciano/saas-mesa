@extends('layouts.client')

@section('content')
    @livewire('client.client-dashboard', ['tenant' => $tenant])
@endsection
