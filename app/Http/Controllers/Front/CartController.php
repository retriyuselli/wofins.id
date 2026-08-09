<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionOrder;
use App\Support\PricingPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class CartController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($blocked = $this->redirectIfPendingOrder()) {
            return $blocked;
        }

        $cart = $this->resolveCart($request);

        if ($cart instanceof RedirectResponse) {
            return $cart;
        }

        return view('front.keranjang', $cart);
    }

    public function paymentForm(Request $request): View|RedirectResponse
    {
        if ($blocked = $this->redirectIfPendingOrder()) {
            return $blocked;
        }

        $cart = $this->resolveCart($request, requireSessionCart: true);

        if ($cart instanceof RedirectResponse) {
            return $cart;
        }

        return view('front.keranjang-bayar', $cart);
    }

    public function update(Request $request): RedirectResponse
    {
        if ($blocked = $this->redirectIfPendingOrder()) {
            return $blocked;
        }

        $data = $request->validate([
            'paket' => ['required', Rule::in(PricingPlans::selectableKeys())],
            'billing' => ['required', Rule::in(PricingPlans::billingKeys())],
        ]);

        return redirect()->route('keranjang', [
            'paket' => $data['paket'],
            'billing' => $data['billing'],
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        if ($blocked = $this->redirectIfPendingOrder()) {
            return $blocked;
        }

        $data = $request->validate([
            'paket' => ['required', Rule::in(PricingPlans::selectableKeys())],
            'billing' => ['required', Rule::in(PricingPlans::billingKeys())],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:4096'],
        ]);

        $user = Auth::user();
        $data['email'] = (string) $user->email;

        $plan = PricingPlans::find($data['paket']);

        if (! $plan) {
            return redirect()->route('harga')->with('error', 'Paket tidak valid.');
        }

        $pricing = PricingPlans::resolveBillingPrice($plan, $data['billing']);
        $uniqueAmount = $this->cartUniqueAmount();
        $payable = (int) $pricing['amount'] + $uniqueAmount;
        $path = $request->file('payment_proof')->store('subscription-orders', 'public');

        $order = SubscriptionOrder::query()->create([
            'user_id' => Auth::id(),
            'order_code' => $this->makeOrderCode(),
            'plan_key' => $plan['key'],
            'plan_name' => $plan['name'],
            'billing' => $data['billing'],
            'amount' => $payable,
            'unique_amount' => $uniqueAmount,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'company_name' => $data['company_name'] ?: null,
            'payment_proof_path' => $path,
            'notes' => $data['notes'] ?: null,
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        session()->forget(['cart.plan_key', 'cart.billing', 'cart.unique_amount']);

        try {
            Mail::send('emails.subscription-order-received', [
                'order' => $order,
            ], function ($message) use ($order) {
                $message->to($order->email, $order->full_name)
                    ->subject('Pesanan WOFINS Anda sedang diproses — '.$order->order_code);
            });
        } catch (Throwable $e) {
            Log::warning('Failed to send subscription order email', [
                'order_code' => $order->order_code,
                'email' => $order->email,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('keranjang.sukses', ['code' => $order->order_code])
            ->with('success', 'Pesanan terkirim. Kami juga mengirim konfirmasi ke email Anda.');
    }

    public function success(string $code): View|RedirectResponse
    {
        $order = $this->findOwnedOrder($code);

        if (! $order) {
            return redirect()->route('harga')->with('error', 'Pesanan tidak ditemukan.');
        }

        return view('front.keranjang-sukses', [
            'order' => $order,
            'bank' => config('wofins.checkout_bank'),
        ]);
    }

    public function myOrders(): View
    {
        $user = Auth::user();

        $orders = SubscriptionOrder::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest('submitted_at')
            ->latest('id')
            ->paginate(10);

        return view('front.pesanan-saya', [
            'orders' => $orders,
            'user' => $user,
        ]);
    }

    public function myOrderShow(string $code): View|RedirectResponse
    {
        $order = $this->findOwnedOrder($code);

        if (! $order) {
            return redirect()
                ->route('pesanan-saya')
                ->with('error', 'Pesanan tidak ditemukan.');
        }

        return view('front.pesanan-saya-detail', [
            'order' => $order,
            'bank' => config('wofins.checkout_bank'),
            'user' => Auth::user(),
        ]);
    }

    private function redirectIfPendingOrder(): ?RedirectResponse
    {
        $pending = SubscriptionOrder::pendingForUser(Auth::user());

        if (! $pending) {
            return null;
        }

        return redirect()
            ->route('pesanan-saya.show', $pending->order_code)
            ->with('error', 'Anda masih punya pesanan menunggu tinjauan ('.$pending->order_code.'). Selesaikan dulu — disetujui atau ditolak — sebelum memesan paket lain.');
    }

    private function findOwnedOrder(string $code): ?SubscriptionOrder
    {
        $user = Auth::user();

        return SubscriptionOrder::query()
            ->where('order_code', $code)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->first();
    }

    /**
     * @return array{plan: array<string, mixed>, billing: string, pricing: array<string, mixed>, uniqueAmount: int, bank: mixed, user: mixed}|RedirectResponse
     */
    private function resolveCart(Request $request, bool $requireSessionCart = false): array|RedirectResponse
    {
        if ($requireSessionCart && ! session()->has('cart.plan_key')) {
            return redirect()
                ->route('harga')
                ->with('error', 'Keranjang kosong. Silakan pilih paket dulu.');
        }

        $planKey = (string) $request->query('paket', session('cart.plan_key', 'starter'));
        $billing = (string) $request->query('billing', session('cart.billing', 'quadrennial'));

        if (! in_array($billing, PricingPlans::billingKeys(), true)) {
            $billing = 'quadrennial';
        }

        $plan = PricingPlans::find($planKey);

        if (! $plan || ! ($plan['selectable'] ?? false)) {
            return redirect()
                ->route('harga')
                ->with('error', 'Paket tidak ditemukan. Silakan pilih ulang.');
        }

        session([
            'cart.plan_key' => $plan['key'],
            'cart.billing' => $billing,
        ]);

        return [
            'plan' => $plan,
            'billing' => $billing,
            'pricing' => PricingPlans::resolveBillingPrice($plan, $billing),
            'uniqueAmount' => $this->cartUniqueAmount(),
            'bank' => config('wofins.checkout_bank'),
            'user' => Auth::user(),
        ];
    }

    private function cartUniqueAmount(): int
    {
        $existing = (int) session('cart.unique_amount', 0);

        if ($existing >= 100 && $existing <= 999) {
            return $existing;
        }

        $unique = random_int(100, 999);
        session(['cart.unique_amount' => $unique]);

        return $unique;
    }

    private function makeOrderCode(): string
    {
        do {
            $code = 'WF'.now()->format('ymd').Str::upper(Str::random(6));
        } while (SubscriptionOrder::query()->where('order_code', $code)->exists());

        return $code;
    }
}
