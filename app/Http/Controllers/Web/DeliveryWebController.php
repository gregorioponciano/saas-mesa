<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\DeliveryPerson;
use App\Models\Notification;
use App\Models\Order;
use App\Services\DeliveryNotificationService;
use App\Services\DeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeliveryWebController extends Controller
{
    public function __construct(
        private readonly DeliveryService $deliveryService
    ) {}

    public function loginForm(): View
    {
        return view('delivery.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('delivery-web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('delivery.dashboard'));
        }

        return back()->withErrors([
            'phone' => 'Telefone ou senha inválidos.',
        ])->onlyInput('phone');
    }

    public function forgotPasswordForm(): View
    {
        return view('delivery.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $delivery = DeliveryPerson::where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if (!$delivery) {
            return back()->withErrors(['email' => 'Email não encontrado.']);
        }

        $tenant = $delivery->tenant;

        if (!$tenant->mail_host) {
            return back()->withErrors(['email' => 'Restaurante não configurou envio de email. O administrador precisa configurar em Configurar Email.']);
        }

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email, 'tenant_id' => $tenant->id],
            ['token' => $token, 'created_at' => now()]
        );

        try {
            $this->applyTenantMailConfig($tenant);
            Mail::to($request->email)->send(new ResetPasswordMail($tenant, $token, $request->email, isDelivery: true));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de reset delivery: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Erro ao enviar email. Verifique as configurações de SMTP do restaurante.']);
        }

        return back()->with('status', 'Link de redefinição enviado para seu email!');
    }

    public function resetPasswordForm(string $token): View|RedirectResponse
    {
        $reset = DB::table('password_resets')
            ->where('token', $token)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return redirect()->route('delivery.forgot.form')
                ->withErrors(['email' => 'Link expirado ou inválido. Solicite novamente.']);
        }

        return view('delivery.reset-password', [
            'token' => $token,
            'email' => $reset->email,
        ]);
    }

    public function resetPassword(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('token', $token)
            ->first();

        if (!$reset || now()->diffInMinutes($reset->created_at) > 60) {
            return redirect()->route('delivery.forgot.form')
                ->withErrors(['email' => 'Link expirado ou inválido. Solicite novamente.']);
        }

        $delivery = DeliveryPerson::where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if (!$delivery) {
            return redirect()->route('delivery.forgot.form')
                ->withErrors(['email' => 'Usuário não encontrado.']);
        }

        $delivery->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')
            ->where('email', $request->email)
            ->where('tenant_id', $delivery->tenant_id)
            ->delete();

        Auth::guard('delivery-web')->login($delivery);
        $request->session()->regenerate();

        return redirect()->route('delivery.dashboard')->with('success', 'Senha redefinida com sucesso!');
    }

    public function dashboard(): View
    {
        $delivery = Auth::guard('delivery-web')->user();

        $notificationService = app(DeliveryNotificationService::class);
        $unreadCount = $notificationService->getUnreadCount(
            get_class($delivery),
            $delivery->id
        );

        $tenant = $delivery->tenant;

        return view('delivery.dashboard', [
            'profile' => $this->deliveryService->getProfile($delivery),
            'todayStats' => $this->deliveryService->getTodayStats($delivery),
            'weeklyEarnings' => $this->deliveryService->getWeeklyEarnings($delivery),
            'ranking' => $this->deliveryService->getDeliveryRanking($delivery),
            'earningsSummary' => $this->deliveryService->getEarningsSummary($delivery),
            'earningsDays' => $this->deliveryService->getEarningsDailyHistory($delivery),
            'earningsOrders' => $this->deliveryService->getEarningsOrders($delivery),
            'availableOrders' => $this->deliveryService->getAvailableOrders($delivery),
            'myOrders' => $this->deliveryService->getMyOrders($delivery),
            'history' => $this->deliveryService->getOrderHistory($delivery),
            'delivery' => $delivery,
            'unreadCount' => $unreadCount,
            'restaurantLat' => $tenant->latitude,
            'restaurantLng' => $tenant->longitude,
            'restaurantAddress' => $tenant->address ? ($tenant->address . ', ' . ($tenant->number ?: '') . ' - ' . ($tenant->neighborhood ?: '') . ', ' . ($tenant->city ?: '')) : '',
        ]);
    }

    public function acceptOrder(int $orderId): RedirectResponse
    {
        $delivery = Auth::guard('delivery-web')->user();
        $order = $this->deliveryService->acceptOrder($delivery, $orderId);

        if (!$order) {
            return back()->with('error', 'Pedido não encontrado ou já foi aceito.');
        }

        return back()->with('success', 'Pedido aceito! Vá até o estabelecimento para retirar.');
    }

    public function pickupOrder(int $orderId): RedirectResponse
    {
        $delivery = Auth::guard('delivery-web')->user();
        $order = $this->deliveryService->markPickedUp($delivery, $orderId);

        if (!$order) {
            return back()->with('error', 'Pedido não encontrado ou não está como coletado.');
        }

        return back()->with('success', 'Pedido saiu para entrega!');
    }

    public function deliverOrder(Request $request, int $orderId): RedirectResponse
    {
        $delivery = Auth::guard('delivery-web')->user();

        $photo = null;
        if ($request->filled('photo_data') && str_starts_with($request->input('photo_data'), 'data:image')) {
            try {
                $photo = $this->deliveryService->uploadDeliveryPhotoData(
                    $request->input('photo_data'),
                    $delivery->tenant_id,
                    $delivery->id
                );
            } catch (\InvalidArgumentException $e) {
                return back()->with('error', $e->getMessage());
            }
        } elseif ($request->hasFile('photo')) {
            $photo = $this->deliveryService->uploadDeliveryPhoto(
                $request->file('photo'),
                $delivery->tenant_id,
                $delivery->id
            );
        }

        $order = $this->deliveryService->markDelivered(
            $delivery,
            $orderId,
            $photo,
            $request->float('lat'),
            $request->float('lng')
        );

        if (!$order) {
            return back()->with('error', 'Pedido não encontrado ou não está em rota de entrega.');
        }

        return back()->with('success', 'Entrega confirmada!');
    }

    public function toggleAvailability(): RedirectResponse
    {
        $delivery = Auth::guard('delivery-web')->user();
        $online = $this->deliveryService->toggleAvailability($delivery);

        return back()->with('success', $online ? 'Você está online e recebendo pedidos!' : 'Você está offline.');
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $delivery = Auth::guard('delivery-web')->user();

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'vehicle_plate' => 'nullable|string|max:10',
            'vehicle_model' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:6|confirmed',
        ];

        // CPF cannot be changed - not in validation rules

        $validated = $request->validate($rules);

        $data = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'vehicle_plate' => strtoupper($validated['vehicle_plate'] ?? ''),
            'vehicle_model' => $validated['vehicle_model'] ?? '',
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $delivery->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Configurações salvas com sucesso!',
        ]);
    }

    public function refreshOrders(): RedirectResponse
    {
        return back();
    }

    public function getNotifications(): JsonResponse
    {
        $delivery = Auth::guard('delivery-web')->user();

        $notificationService = app(DeliveryNotificationService::class);

        $notifications = $notificationService->getRecent(
            get_class($delivery),
            $delivery->id,
            50
        );

        $unreadCount = $notificationService->getUnreadCount(
            get_class($delivery),
            $delivery->id
        );

        // Get fresh orders for polling
        $todaysOrders = $this->deliveryService->getMyOrders($delivery)['active'] ?? [];
        $availableOrders = $this->deliveryService->getAvailableOrders($delivery);

        $online = $delivery->is_online;

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'todays_orders' => $todaysOrders,
            'available_orders' => $availableOrders,
            'is_online' => $online,
        ]);
    }

    public function markNotificationRead(int $notificationId): JsonResponse
    {
        $delivery = Auth::guard('delivery-web')->user();
        app(DeliveryNotificationService::class)->markAsRead(
            $notificationId,
            get_class($delivery),
            $delivery->id
        );

        return response()->json([
            'success' => true,
            'unread_count' => app(DeliveryNotificationService::class)->getUnreadCount(
                get_class($delivery),
                $delivery->id
            ),
        ]);
    }

    private function applyTenantMailConfig(\App\Models\Tenant $tenant): void
    {
        if ($tenant->mail_host) {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $tenant->mail_host,
                'mail.mailers.smtp.port' => $tenant->mail_port,
                'mail.mailers.smtp.username' => $tenant->mail_username,
                'mail.mailers.smtp.password' => $tenant->mail_password,
                'mail.mailers.smtp.encryption' => $tenant->mail_encryption,
                'mail.from.address' => $tenant->mail_from_address,
                'mail.from.name' => $tenant->mail_from_name ?? $tenant->name,
            ]);
        }
    }

    public function exportData(): JsonResponse
    {
        $delivery = Auth::guard('delivery-web')->user();

        return response()->json([
            'name' => $delivery->name,
            'email' => $delivery->email,
            'phone' => $delivery->phone,
            'cpf' => $delivery->cpf,
            'vehicle_plate' => $delivery->vehicle_plate,
            'vehicle_model' => $delivery->vehicle_model,
            'created_at' => $delivery->created_at,
            'orders' => $delivery->orders()
                ->select('id', 'status', 'total', 'created_at', 'delivered_at')
                ->get(),
            'earnings' => $delivery->earnings()
                ->select('id', 'order_id', 'amount', 'status', 'paid_at', 'earned_at')
                ->get(),
        ]);
    }

    public function deleteAccount(): JsonResponse
    {
        $delivery = Auth::guard('delivery-web')->user();

        $delivery->orders()->update(['delivery_person_id' => null]);
        $delivery->delete();

        Auth::guard('delivery-web')->logout();

        return response()->json(['success' => true]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('delivery-web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('delivery.login');
    }

    public function trackOrder(int $id): View
    {
        $order = Order::withoutTenant()->with(['items', 'deliveryPerson', 'tenant'])->findOrFail($id);

        if (!$order->isEntrega()) {
            abort(404, 'Pedido não encontrado.');
        }

        return view('delivery.tracking', [
            'order' => $order,
            'items' => $order->items,
            'deliveryPerson' => $order->deliveryPerson,
            'tenant' => $order->tenant,
            'address' => $order->address_json,
            'statusTimeline' => $this->buildStatusTimeline($order),
        ]);
    }

    private function buildStatusTimeline(Order $order): array
    {
        $steps = match ($order->type) {
            'entrega' => ['novo', 'em_preparo', 'coletado', 'saiu_entrega', 'entregue'],
            default => ['novo', 'em_preparo', 'pronto', 'entregue'],
        };

        $currentIndex = array_search($order->status, $steps);
        if ($currentIndex === false) {
            $currentIndex = -1;
        }

        $timestamps = [
            'novo' => $order->created_at,
            'coletado' => $order->accepted_at,
            'saiu_entrega' => $order->picked_up_at,
            'entregue' => $order->delivered_at,
        ];

        return array_map(function (string $status, int $index) use ($currentIndex, $timestamps, $steps) {
            return [
                'status' => $status,
                'label' => Order::STATUS_LABELS[$status] ?? $status,
                'reached' => $index <= $currentIndex,
                'current' => $index === $currentIndex,
                'timestamp' => $timestamps[$status] ?? null,
            ];
        }, $steps, array_keys($steps));
    }
}
