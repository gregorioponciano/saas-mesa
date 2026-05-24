@component('layouts.app')
    @livewire('public.menu', ['tenant' => $tenant, 'token' => request()->query('token')])
@endcomponent
