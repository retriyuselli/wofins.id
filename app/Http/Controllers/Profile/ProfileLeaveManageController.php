<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\UserVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileLeaveManageController extends Controller
{
    private function authorizeManager(): User
    {
        $user = Auth::user();
        if (! $user instanceof User || ! $user->hasRole(['super_admin', 'admin', 'finance'])) {
            abort(403);
        }

        return $user;
    }

    public function index(Request $request): View
    {
        $manager = $this->authorizeManager();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $leaveTypeId = $request->get('leave_type_id');

        $query = LeaveRequest::query()
            ->with(['user', 'leaveType', 'approver', 'replacementEmployee'])
            ->latest('created_at');

        if (! $manager->hasRole('super_admin')) {
            $visibleUserIds = UserVisibility::constrainUsersQuery(User::query())->pluck('id');
            $query->whereIn('user_id', $visibleUserIds);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('reason', 'like', "%{$q}%")
                    ->orWhere('emergency_contact', 'like', "%{$q}%")
                    ->orWhereHas('user', function ($userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        if (filled($leaveTypeId)) {
            $query->where('leave_type_id', (int) $leaveTypeId);
        }

        $baseStatsQuery = LeaveRequest::query();
        if (! $manager->hasRole('super_admin')) {
            $visibleUserIds = UserVisibility::constrainUsersQuery(User::query())->pluck('id');
            $baseStatsQuery->whereIn('user_id', $visibleUserIds);
        }

        return view('profile.leave-manage', [
            'q' => $q,
            'status' => $status,
            'leaveTypeId' => $leaveTypeId,
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(['id', 'name']),
            'requests' => $query->paginate(20)->withQueryString(),
            'pendingCount' => (clone $baseStatsQuery)->where('status', 'pending')->count(),
            'approvedCount' => (clone $baseStatsQuery)->where('status', 'approved')->count(),
            'rejectedCount' => (clone $baseStatsQuery)->where('status', 'rejected')->count(),
            'canDecide' => $manager->hasRole(['super_admin', 'admin', 'finance']),
        ]);
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        $manager = $this->authorizeManager();
        $this->assertCanManageRecord($manager, $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Permohonan cuti ini sudah diproses.');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $manager->id,
        ]);

        return back()->with('success', 'Permohonan cuti disetujui.');
    }

    public function reject(LeaveRequest $leaveRequest): RedirectResponse
    {
        $manager = $this->authorizeManager();
        $this->assertCanManageRecord($manager, $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Permohonan cuti ini sudah diproses.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $manager->id,
        ]);

        return back()->with('success', 'Permohonan cuti ditolak.');
    }

    private function assertCanManageRecord(User $manager, LeaveRequest $leaveRequest): void
    {
        if ($manager->hasRole('super_admin')) {
            return;
        }

        $visible = UserVisibility::constrainUsersQuery(User::query())
            ->whereKey($leaveRequest->user_id)
            ->exists();

        if (! $visible) {
            abort(403);
        }
    }
}
