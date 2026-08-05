<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LeaveBalanceResource;
use App\Http\Resources\Api\V1\LeaveRequestResource;
use App\Http\Resources\Api\V1\PayrollResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\Ess\ProfileEssService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class MeController extends Controller
{
    public function __construct(
        private readonly ProfileEssService $ess,
    ) {}

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
     * Payroll + leave usage summary.
     */
    public function compensation(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $period = (string) $request->query('period', 'year');
        $payload = $this->ess->compensation($user, $period);

        return response()->json([
            'data' => [
                'period' => $payload['period'],
                'current_year' => $payload['current_year'],
                'payroll' => $payload['payroll']
                    ? new PayrollResource($payload['payroll'])
                    : null,
                'leave_stats' => $payload['leave_stats'],
                'leave_by_type' => $payload['leave_by_type'],
                'annual_leave_allowance' => $payload['annual_leave_allowance'],
                'used_leave' => $payload['used_leave'],
                'display_used_leave' => $payload['display_used_leave'],
                'remaining_leave' => $payload['remaining_leave'],
                'prev_year' => $payload['prev_year'],
                'prev_used_leave' => $payload['prev_used_leave'],
                'prev_usage_percentage' => $payload['prev_usage_percentage'],
                'carry_over' => $payload['carry_over'],
                'effective_allowance_year' => $payload['effective_allowance_year'],
            ],
        ]);
    }

    /**
     * Leave balances for the authenticated user.
     */
    public function leaveBalances(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $year = (int) $request->query('year', date('Y'));

        $balances = $user->leaveBalances()
            ->with('leaveType')
            ->where('year', $year)
            ->orderBy('leave_type_id')
            ->get();

        return response()->json([
            'data' => [
                'year' => $year,
                'annual_leave_allowance' => $this->ess->annualLeaveAllowance($user),
                'balances' => LeaveBalanceResource::collection($balances),
            ],
        ]);
    }

    /**
     * Upcoming + recent leave schedule.
     */
    public function schedule(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $payload = $this->ess->schedule($user);

        return response()->json([
            'data' => [
                'current_date' => $payload['current_date'],
                'days_until_next_leave' => $payload['days_until_next_leave'],
                'next_leave' => $payload['next_leave']
                    ? new LeaveRequestResource($payload['next_leave'])
                    : null,
                'upcoming_leaves' => LeaveRequestResource::collection($payload['upcoming_leaves']),
                'recent_leaves' => LeaveRequestResource::collection($payload['recent_leaves']),
                'status_translations' => $payload['status_translations'],
                'leave_type_translations' => $payload['leave_type_translations'],
            ],
        ]);
    }

    /**
     * Available leave types (for create form).
     */
    public function leaveTypes(): JsonResponse
    {
        $types = LeaveType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'keterangan', 'max_days_per_year']);

        return response()->json([
            'data' => $types,
        ]);
    }
}
