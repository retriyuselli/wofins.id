<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MobileModuleService;
use App\Support\UserVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileModuleController extends Controller
{
    public function __construct(
        private readonly MobileModuleService $modules,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->modules->catalog($request->user()),
        ]);
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($this->modules->paginate(
            $request->user(),
            $key,
            $data['q'] ?? null,
            (int) ($data['per_page'] ?? 20),
        ));
    }

    public function form(Request $request, string $key): JsonResponse
    {
        return response()->json([
            'data' => $this->modules->form($request->user(), $key),
        ]);
    }

    public function store(Request $request, string $key): JsonResponse
    {
        $this->assertCompany();

        try {
            $record = $this->modules->store($request->user(), $key, $request->all());
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => 'Data berhasil disimpan.',
            'data' => $record,
        ], 201);
    }

    public function detail(Request $request, string $key, int $id): JsonResponse
    {
        $record = $this->modules->detail($request->user(), $key, $id);

        if (! $record) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json(['data' => $record]);
    }

    public function update(Request $request, string $key, int $id): JsonResponse
    {
        $this->assertCompany();

        $record = $this->modules->update($request->user(), $key, $id, $request->all());

        if (! $record) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json([
            'message' => 'Data berhasil diperbarui.',
            'data' => $record,
        ]);
    }

    private function assertCompany(): void
    {
        if (UserVisibility::companyId() === null) {
            abort(response()->json([
                'message' => 'Akun ini belum terhubung ke company. Data tidak ditampilkan lintas tenant.',
            ], 403));
        }
    }
}
