<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PayrollResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class MeController extends Controller
{
    /**
     * Return the authenticated user (ESS summary).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('roles');

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update profile fields (self only).
     */
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ]);

        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => new UserResource($user->fresh()->loadMissing('roles')),
        ]);
    }

    /**
     * Upload / replace avatar.
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($user->avatar_url && Storage::disk('public')->exists($user->avatar_url)) {
            Storage::disk('public')->delete($user->avatar_url);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_url = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar berhasil diperbarui.',
            'data' => new UserResource($user->fresh()->loadMissing('roles')),
        ]);
    }

    /**
     * Change password; revoke other API tokens.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->password = $request->password;
        $user->save();

        $currentTokenId = $request->user()->currentAccessToken()?->id;
        $user->tokens()
            ->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))
            ->delete();

        return response()->json([
            'message' => 'Password berhasil diubah.',
        ]);
    }

    /**
     * Payroll summary for the authenticated user.
     */
    public function compensation(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $period = (string) $request->query('period', 'year');
        $payroll = $user->payrolls()->latest()->first();

        return response()->json([
            'data' => [
                'period' => $period,
                'current_year' => (int) date('Y'),
                'payroll' => $payroll
                    ? new PayrollResource($payroll)
                    : null,
            ],
        ]);
    }
}
