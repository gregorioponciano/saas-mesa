<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderPaid;
use App\Listeners\NotifyOrderPaid;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPaid::class => [
            NotifyOrderPaid::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
