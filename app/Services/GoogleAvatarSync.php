<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GoogleAvatarSync
{
    /**
     * Unduh foto profil Google dan simpan sebagai avatar default.
     * Tidak menimpa foto yang sudah diunggah user.
     */
    public function sync(User $user, ?string $avatarUrl): void
    {
        if (filled($user->avatar_url) || ! filled($avatarUrl)) {
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
}
