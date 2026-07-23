<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPerson;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeliveryInviteController extends Controller
{
    public function __construct(
        private readonly DeliveryService $deliveryService
    ) {}

    public function show(string $token): View|RedirectResponse
    {
        $delivery = DeliveryPerson::withoutTenant()
            ->where('invite_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$delivery || !$delivery->hasValidInvite()) {
            return view('delivery.invite-expired');
        }

        return view('delivery.accept-invite', [
            'name' => $delivery->name,
            'phone' => $delivery->phone,
            'token' => $token,
        ]);
    }

    public function accept(Request $request, string $token): View|RedirectResponse
    {
        $delivery = DeliveryPerson::withoutTenant()
            ->where('invite_token', $token)
            ->where('status', 'active')
            ->first();

        if (!$delivery || !$delivery->hasValidInvite()) {
            return redirect()->route('delivery.invite.show', $token);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
            'email' => 'required|email|unique:delivery_people,email',
            'cpf' => 'required|string|max:14|regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/',
            'cnh' => 'required|string|max:20',
            'vehicle_plate' => 'required|string|max:10|regex:/^[A-Z]{3}-\d{4}$/',
            'vehicle_model' => 'required|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $data = [
            'password' => bcrypt($validated['password']),
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
            'cnh' => $validated['cnh'],
            'vehicle_plate' => $validated['vehicle_plate'],
            'vehicle_model' => $validated['vehicle_model'],
            'invite_token' => null,
            'invite_expires_at' => null,
            'activated_at' => now(),
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $this->deliveryService->uploadAvatar(
                $request->file('avatar'),
                $delivery->tenant_id
            );
        }

        $delivery->update($data);

        Auth::guard('delivery-web')->login($delivery);
        $request->session()->regenerate();

        return redirect()->route('delivery.dashboard')->with('success', 'Conta ativada com sucesso! Bem-vindo ao painel de entregas.');
    }

    public function success(): View
    {
        return view('delivery.invite-success');
    }
}
