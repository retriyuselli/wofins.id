<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AppleTokenVerifier;
use App\Services\GoogleAvatarSync;
use App\Support\CompanyBrand;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('front.auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user instanceof User) {
                $blockReason = $this->loginBlockReason($user);
                if ($blockReason !== null) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    throw ValidationException::withMessages([
                        'email' => [$blockReason],
                    ]);
                }
            }

            CompanyBrand::remember($user);

            return $this->redirectAfterAuth(Auth::user());
        }

        throw ValidationException::withMessages([
            'email' => ['Email atau password tidak valid.'],
        ]);
    }

    /**
     * Alasan blokir login, atau null jika boleh masuk.
     */
    protected function loginBlockReason(User $user): ?string
    {
        if (in_array($user->status, ['inactive', 'terminated'], true)) {
            return $user->status === 'terminated'
                ? 'Akun Anda telah dinonaktifkan permanen. Hubungi administrator.'
                : 'Akun Anda sedang nonaktif. Hubungi administrator.';
        }

        if ($user->hasRole('super_admin')) {
            return null;
        }

        $user->loadMissing('company');

        if ($user->company && $user->company->isDeactivated()) {
            return 'Perusahaan Anda dinonaktifkan. Hubungi admin WOFINS.';
        }

        return null;
    }

    /**
     * Show the registration form
     */
    public function showRegisterForm()
    {
        return view('front.auth.register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        CompanyBrand::remember($user);

        $user->sendEmailVerificationNotification();

        return redirect()
            ->route('verification.notice')
            ->with('info', 'Akun berhasil dibuat. Silakan verifikasi email Anda untuk melanjutkan.');
    }

    /**
     * Halaman pemberitahuan: cek email untuk verifikasi.
     */
    public function showVerificationNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($request->user());
        }

        return view('front.auth.verify-email');
    }

    /**
     * Proses tautan verifikasi dari email.
     * Tidak wajib login: klik dari kotak masuk tetap memverifikasi, lalu minta user masuk.
     */
    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Tautan verifikasi tidak valid.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()
            ->route('verification.success')
            ->with('verified', true);
    }

    /**
     * Halaman sukses setelah klik tautan verifikasi.
     */
    public function showVerified()
    {
        return view('front.auth.email-verified');
    }

    /**
     * Kirim ulang email verifikasi.
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectAfterAuth($request->user());
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle()
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return redirect()
                ->route('front.login')
                ->with('error', 'Login Google belum dikonfigurasi. Hubungi administrator.');
        }

        return Socialite::driver('google')
            ->redirectUrl($this->googleRedirectUri())
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback (login + register)
     */
    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('front.login')
                ->with('error', 'Login Google dibatalkan.');
        }

        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl($this->googleRedirectUri())
                ->user();
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback failed', [
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('front.login')
                ->with('error', 'Gagal masuk dengan Google. Silakan coba lagi.');
        }

        $email = $googleUser->getEmail();
        $googleId = $googleUser->getId();

        if (! $email || ! $googleId) {
            return redirect()
                ->route('front.login')
                ->with('error', 'Akun Google tidak menyediakan email yang valid.');
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            if ($reason = $this->loginBlockReason($user)) {
                return redirect()
                    ->route('front.login')
                    ->with('error', $reason);
            }

            $updates = [];
            if (! $user->google_id) {
                $updates['google_id'] = $googleId;
            }
            if (! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'password' => Str::password(32),
                'status' => 'active',
            ]);
        }

        // Ambil foto Google sebagai avatar default jika user belum punya foto
        app(GoogleAvatarSync::class)->sync($user, $googleUser->getAvatar());

        Auth::login($user, true);
        $request->session()->regenerate();
        CompanyBrand::remember($user);

        return $this->redirectAfterAuth($user);
    }

    /**
     * Redirect to Sign in with Apple (web Services ID).
     */
    public function redirectToApple()
    {
        $clientId = trim((string) config('services.apple.web_client_id'));
        $redirectUri = $this->appleRedirectUri();

        if ($clientId === '' || $redirectUri === '') {
            return redirect()
                ->route('front.login')
                ->with('error', 'Login Apple belum dikonfigurasi. Hubungi administrator.');
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'scope' => 'name email',
            'state' => $this->makeAppleOAuthState(),
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect()->away('https://appleid.apple.com/auth/authorize?'.$query);
    }

    /**
     * Handle Apple form_post callback (id_token).
     */
    public function handleAppleCallback(Request $request, AppleTokenVerifier $verifier)
    {
        try {
            Log::info('Apple Sign In callback received', [
                'keys' => array_keys($request->except(['id_token', 'code', 'user'])),
                'has_id_token' => $request->filled('id_token'),
                'has_code' => $request->filled('code'),
                'has_state' => $request->filled('state'),
                'has_error' => $request->filled('error'),
            ]);

            return $this->completeAppleCallback($request, $verifier);
        } catch (Throwable $e) {
            Log::error('Apple Sign In callback crashed', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return $this->appleBridgeInline(
                route('front.login'),
                'Gagal masuk dengan Apple. Silakan coba lagi dari halaman masuk.'
            );
        }
    }

    /**
     * GET ke Return URL (bukan form_post Apple) — arahkan ke login.
     */
    public function showAppleCallback()
    {
        return $this->appleBridgeInline(
            route('front.login'),
            'Login Apple harus dimulai dari tombol Masuk dengan Apple.'
        );
    }

    protected function completeAppleCallback(Request $request, AppleTokenVerifier $verifier)
    {
        if ($request->filled('error')) {
            return $this->appleBridgeToLogin('Login Apple dibatalkan.');
        }

        if (! $this->assertAppleOAuthState($request->input('state'))) {
            return $this->appleBridgeToLogin('Sesi login Apple tidak valid. Silakan coba lagi.');
        }

        $identityToken = trim((string) $request->input('id_token', ''));
        if ($identityToken === '') {
            return $this->appleBridgeToLogin('Token Sign in with Apple tidak tersedia.');
        }

        try {
            $payload = $verifier->verify($identityToken);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'Token Sign in with Apple tidak valid.';

            return $this->appleBridgeToLogin($message);
        }

        $appleId = (string) ($payload['sub'] ?? '');
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $emailVerified = filter_var($payload['email_verified'] ?? true, FILTER_VALIDATE_BOOL);

        $appleUser = $this->parseAppleUserPayload($request->input('user'));
        if ($email === '' && filled($appleUser['email'] ?? null)) {
            $email = mb_strtolower(trim((string) $appleUser['email']));
        }

        if ($appleId === '') {
            return $this->appleBridgeToLogin('Identitas akun Apple tidak tersedia.');
        }

        $user = User::query()->where('apple_id', $appleId)->first();

        if (! $user && $email !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        }

        if ($user) {
            if ($reason = $this->loginBlockReason($user)) {
                return $this->appleBridgeToLogin($reason);
            }

            if ($user->apple_id && ! hash_equals((string) $user->apple_id, $appleId)) {
                return $this->appleBridgeToLogin('Email ini sudah ditautkan ke akun Apple lain.');
            }

            $updates = [];
            if (! $user->apple_id) {
                $updates['apple_id'] = $appleId;
            }
            if ($emailVerified && ! $user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }
        } else {
            if ($email === '') {
                return $this->appleBridgeToLogin(
                    'Akun Apple tidak menyediakan email. Izinkan berbagi email, atau masuk dengan email & password lalu tautkan Apple.'
                );
            }

            $name = trim((string) ($appleUser['name'] ?? ''));
            $user = User::create([
                'name' => $name !== '' ? $name : Str::before($email, '@'),
                'email' => $email,
                'apple_id' => $appleId,
                'email_verified_at' => $emailVerified ? now() : null,
                'password' => Str::password(32),
                'status' => 'active',
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();
        CompanyBrand::remember($user);

        $redirect = $this->redirectAfterAuth($user);
        $target = $redirect instanceof \Illuminate\Http\RedirectResponse
            ? $redirect->getTargetUrl()
            : route('profile');

        // form_post Apple = cross-site POST. Jangan 302 langsung:
        // kembalikan HTML 200 agar cookie sesi menempel, lalu redirect via JS.
        return $this->appleBridgeRedirect($target, 'Login Apple berhasil. Mengalihkan…');
    }

    protected function appleBridgeToLogin(string $message)
    {
        session()->flash('error', $message);

        return $this->appleBridgeRedirect(route('front.login'), $message);
    }

    protected function appleBridgeRedirect(string $redirectUrl, ?string $message = null)
    {
        try {
            return response()
                ->view('front.auth.apple-callback-bridge', [
                    'redirectUrl' => $redirectUrl,
                    'message' => $message,
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        } catch (Throwable) {
            return $this->appleBridgeInline($redirectUrl, $message);
        }
    }

    /**
     * Fallback HTML tanpa Blade — agar callback tidak pernah putih polos.
     */
    protected function appleBridgeInline(string $redirectUrl, ?string $message = null)
    {
        $safeUrl = e($redirectUrl);
        $safeMessage = e($message ?: 'Mengalihkan ke WOFINS…');
        $jsonUrl = json_encode($redirectUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="0;url={$safeUrl}">
<title>Mengalihkan — WOFINS</title>
<style>
body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:system-ui,-apple-system,sans-serif;background:#f4f6f9;color:#0b1f3a;padding:24px;text-align:center}
.card{max-width:360px;background:#fff;border-radius:16px;padding:28px 24px;box-shadow:0 12px 40px rgba(11,31,58,.08)}
a{display:inline-block;margin-top:12px;color:#0b1f3a;font-weight:700}
</style>
</head>
<body>
<div class="card">
<p><strong>WOFINS</strong></p>
<p>{$safeMessage}</p>
<p><a href="{$safeUrl}">Lanjutkan ke WOFINS</a></p>
</div>
<script>window.location.replace({$jsonUrl});</script>
</body>
</html>
HTML;

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah logout.');
    }

    /**
     * Redirect after successful authentication.
     */
    protected function redirectAfterAuth(?User $user)
    {
        if (! $user) {
            return redirect()->route('home');
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('info', 'Silakan verifikasi email Anda sebelum melanjutkan.');
        }

        // Checkout paket: izinkan lanjut ke keranjang meskipun role belum di-Approve.
        if ($checkoutUrl = $this->checkoutIntendedUrl()) {
            session()->forget('url.intended');

            return redirect()->to($checkoutUrl);
        }

        // Paket company habis: boleh login, tapi arahkan ke halaman perpanjang (bukan admin).
        if (! $user->hasRole('super_admin')
            && \App\Support\CompanySubscription::isExpired($user)) {
            $canManage = \App\Support\CompanySubscription::canManageSubscription($user);
            $message = $canManage
                ? 'Masa aktif paket perusahaan sudah berakhir. Perpanjang paket agar seluruh tim kembali bisa memakai dashboard.'
                : 'Masa aktif paket perusahaan sudah berakhir. Hubungi admin perusahaan Anda untuk perpanjang.';

            return redirect()
                ->route('account.subscription-expired')
                ->with('error', $message);
        }

        if (! $user->hasAssignedRole()) {
            return redirect()
                ->route('account.pending')
                ->with('info', 'Akun Anda berhasil masuk. Lanjutkan pendaftaran melalui aplikasi WOFINS untuk mengaktifkan akses.');
        }

        return redirect()->intended(route('profile'));
    }

    /**
     * Path (+ query) keranjang dari session intended, atau null.
     * Pakai path relatif agar tidak gagal saat host intended ≠ APP_URL
     * (mis. localhost vs 127.0.0.1).
     */
    protected function checkoutIntendedUrl(): ?string
    {
        $intended = session('url.intended');

        if (! is_string($intended) || $intended === '') {
            return null;
        }

        $path = parse_url($intended, PHP_URL_PATH);
        $query = parse_url($intended, PHP_URL_QUERY);

        if (! is_string($path) || $path === '') {
            return null;
        }

        if ($path !== '/keranjang' && ! str_starts_with($path, '/keranjang/')
            && $path !== '/pesanan-saya' && ! str_starts_with($path, '/pesanan-saya/')) {
            return null;
        }

        return $path.(is_string($query) && $query !== '' ? '?'.$query : '');
    }

    /**
     * @deprecated gunakan checkoutIntendedUrl()
     */
    protected function hasCheckoutIntendedUrl(): bool
    {
        return $this->checkoutIntendedUrl() !== null;
    }

    protected function googleRedirectUri(): string
    {
        // Gunakan URL aplikasi saat ini agar local/production tetap cocok
        // (daftarkan kedua URI di Google Cloud Console bila perlu).
        return route('auth.google.callback', absolute: true);
    }

    protected function appleRedirectUri(): string
    {
        $configured = trim((string) config('services.apple.redirect'));
        if ($configured !== '') {
            return $configured;
        }

        return route('auth.apple.callback', absolute: true);
    }

    protected function makeAppleOAuthState(): string
    {
        $payload = json_encode([
            'n' => Str::random(32),
            't' => time(),
        ], JSON_THROW_ON_ERROR);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=').'.'.hash_hmac('sha256', $payload, (string) config('app.key'));
    }

    protected function assertAppleOAuthState(mixed $state): bool
    {
        if (! is_string($state) || ! str_contains($state, '.')) {
            return false;
        }

        [$encoded, $signature] = explode('.', $state, 2);
        $payload = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($payload === false || $payload === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, (string) config('app.key'));
        if (! hash_equals($expected, $signature)) {
            return false;
        }

        try {
            /** @var array{n?: string, t?: int} $data */
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        $issuedAt = (int) ($data['t'] ?? 0);

        return $issuedAt > 0 && abs(time() - $issuedAt) <= 600;
    }

    /**
     * @return array{name?: string, email?: string}
     */
    protected function parseAppleUserPayload(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        try {
            /** @var array{name?: array{firstName?: string, lastName?: string}, email?: string} $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        $first = trim((string) ($decoded['name']['firstName'] ?? ''));
        $last = trim((string) ($decoded['name']['lastName'] ?? ''));
        $name = trim($first.' '.$last);

        return array_filter([
            'name' => $name !== '' ? $name : null,
            'email' => filled($decoded['email'] ?? null) ? (string) $decoded['email'] : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Show the forgot password form
     */
    public function showForgotPasswordForm()
    {
        return view('front.auth.forgot-password');
    }

    /**
     * Send password reset link to email
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password telah dikirim ke email Anda.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Show the reset password form
     */
    public function showResetPasswordForm(Request $request, string $token)
    {
        return view('front.auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                // forceFill tetap melewati cast 'hashed' — jangan hash dua kali
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('front.login')
                ->with('status', 'Password berhasil direset. Silakan login.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
