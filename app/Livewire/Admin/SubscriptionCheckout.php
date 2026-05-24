<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class SubscriptionCheckout extends Component
{
    public function render()
    {
        return view('livewire.admin.subscription-checkout')
            ->layout('layouts.admin');
    }
}
