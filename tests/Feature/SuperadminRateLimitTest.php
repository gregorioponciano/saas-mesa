<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

it('bloqueia requisições em excesso na API superadmin', function () {
    RateLimiter::for('superadmin', fn () => Limit::perMinute(2));

    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->getJson('/api/superadmin/tenants')->assertOk();
    $this->getJson('/api/superadmin/tenants')->assertOk();
    $this->getJson('/api/superadmin/tenants')->assertStatus(429);
});

it('bloqueia ações sensíveis do superadmin com teto mais baixo', function () {
    RateLimiter::for('superadmin-sensitive', fn () => Limit::perMinute(2));

    $superadmin = createSuperAdmin();
    $tenant = createTenant();
    $this->actingAs($superadmin);

    $this->postJson("/api/superadmin/tenants/{$tenant->id}/suspend")->assertOk();
    $this->postJson("/api/superadmin/tenants/{$tenant->id}/suspend")->assertOk();
    $this->postJson("/api/superadmin/tenants/{$tenant->id}/suspend")->assertStatus(429);
});

it('bloqueia o painel web do superadmin acima do limite', function () {
    RateLimiter::for('superadmin', fn () => Limit::perMinute(2));

    $superadmin = createSuperAdmin();
    $this->actingAs($superadmin);

    $this->get(route('superadmin.dashboard'))->assertOk();
    $this->get(route('superadmin.dashboard'))->assertOk();
    $this->get(route('superadmin.dashboard'))->assertStatus(429);
});

it('aplica limite de 10 tentativas no login do superadmin', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('superadmin.login'), [
            'email' => 'admin@saas.com',
            'password' => 'errada',
        ])->assertRedirect();
    }

    $this->post(route('superadmin.login'), [
        'email' => 'admin@saas.com',
        'password' => 'errada',
    ])->assertStatus(429);
});
