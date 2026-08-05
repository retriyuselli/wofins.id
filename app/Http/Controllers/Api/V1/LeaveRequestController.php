<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveRequestController extends Controller
{
    /**
     * List leave requests for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = $user->leaveRequests()
            ->with(['leaveType', 'replacementEmployee', 'approver'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
                $query->where('status', $status);
            }
        }

        if ($request->filled('year')) {
            $query->whereYear('start_date', (int) $request->query('year'));
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => LeaveRequestResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Show a single leave request (own only).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $leaveRequest = $user->leaveRequests()
            ->with(['leaveType', 'replacementEmployee', 'approver'])
            ->findOrFail($id);

        return response()->json([
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    /**
     * Submit a new leave request.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'replacement_employee_id' => [
                'nullable',
                'exists:users,id',
                Rule::notIn([$user->id]),
            ],
            'documents' => ['nullable', 'array', 'max:5'],
            'documents.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $data['reason'],
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'replacement_employee_id' => $data['replacement_employee_id'] ?? null,
            'status' => 'pending',
        ]);

        if ($request->hasFile('documents')) {
            $uploaded = [];
            foreach ($request->file('documents') as $file) {
                $filename = time().'_'.$file->getClientOriginalName();
                $uploaded[] = $file->storeAs('leave-documents', $filename, 'public');
            }
            // Assign array — model casts to JSON (avoid double-encode like web store)
            $leaveRequest->documents = $uploaded;
            $leaveRequest->save();
        }

        $leaveRequest->load(['leaveType', 'replacementEmployee', 'approver']);

        return response()->json([
            'message' => 'Permohonan cuti berhasil diajukan.',
            'data' => new LeaveRequestResource($leaveRequest),
        ], 201);
    }
}
