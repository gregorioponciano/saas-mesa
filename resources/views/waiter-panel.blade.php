@extends('layouts.waiter')

@section('content')
    @livewire('waiter.waiter-dashboard', ['tenant' => $tenant])
@endsection
