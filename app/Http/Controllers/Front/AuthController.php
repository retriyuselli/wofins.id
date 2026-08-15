<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
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
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return $this->redirectAfterAuth($request->user())
            ->with('success', 'Email berhasil diverifikasi.');
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
        $this->syncGoogleAvatar($user, $googleUser);

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectAfterAuth($user);
    }

    /**
     * Unduh foto profil Google dan simpan sebagai avatar default.
     * Tidak menimpa foto yang sudah diunggah user.
     */
    protected function syncGoogleAvatar(User $user, SocialiteUser $googleUser): void
    {
        if (filled($user->avatar_url)) {
            return;
        }

        $avatarUrl = $googleUser->getAvatar();
        if (! filled($avatarUrl)) {
            return;
        }

        // Minta resolusi lebih besar dari default (sering =s96-c)
        $avatarUrl = preg_replace('/=s\d+(-c)?/', '=s400$1', $avatarUrl) ?: $avatarUrl;

        try {
            $response = Http::timeout(12)
                ->withHeaders(['Accept' => 'image/*'])
                ->get($avatarUrl);

            if (! $response->successful() || blank($response->body())) {
                return;
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $path = 'avatars/google_'.$user->id.'_'.Str::lower(Str::random(12)).'.'.$extension;

            if (! Storage::disk('public')->put($path, $response->body())) {
                return;
            }

            $user->forceFill(['avatar_url' => $path])->save();
        } catch (Throwable $e) {
            Log::warning('Gagal mengambil foto profil Google', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
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
            return redirect()
                ->route('account.subscription-expired')
                ->with('error', 'Masa aktif paket perusahaan Anda sudah berakhir. Perpanjang paket untuk membuka kembali dashboard.');
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
