<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * HRIS berpusat ke Employee (banyak karyawan, tidak makan seat User).
 * User hanya opsional: login admin / portal ESS.
 */
class HrEmployee
{
    /**
     * Employee aktif yang terhubung ke akun login (untuk ESS).
     */
    public static function forUser(?User $user): ?Employee
    {
        if (! $user) {
            return null;
        }

        return $user->relationLoaded('activeEmployee')
            ? $user->activeEmployee
            : $user->activeEmployee()->first();
    }

    /**
     * Pastikan User punya Employee (buat jika belum) — untuk ESS / migrasi.
     */
    public static function ensureForUser(User $user): Employee
    {
        $existing = static::forUser($user);
        if ($existing) {
            return $existing;
        }

        $any = $user->employees()->latest('id')->first();
        if ($any) {
            if ($any->date_of_out) {
                $any->forceFill(['date_of_out' => null])->save();
            }

            return $any;
        }

        $baseSlug = Str::slug($user->name ?: ('user-'.$user->id)) ?: ('user-'.$user->id);
        $slug = Employee::generateUniqueSlug($baseSlug);

        return Employee::query()->create([
            'company_id' => $user->company_id,
            'name' => $user->name ?: ('User #'.$user->id),
            'slug' => $slug,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'user_id' => $user->id,
            'date_of_join' => now()->toDateString(),
            'salary' => (int) ($user->gaji_pokok_base ?? 0),
            'tunjangan' => (int) ($user->tunjangan_base ?? 0),
        ]);
    }

    /**
     * ESS: wajib punya Employee. Admin bisa absenkan Employee tanpa User.
     */
    public static function requireForUser(User $user): Employee
    {
        $employee = static::forUser($user) ?? static::ensureForUser($user);

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => ['Akun Anda belum terhubung ke data karyawan (Employee). Hubungi admin HR.'],
            ]);
        }

        return $employee;
    }

    /**
     * user_id denormalisasi dari Employee (nullable jika karyawan tanpa login).
     */
    public static function userId(?Employee $employee): ?int
    {
        return $employee?->user_id ? (int) $employee->user_id : null;
    }
}
