<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AbsensiResource;
use App\Http\Resources\Api\V1\LokasiAbsensiResource;
use App\Models\Absensi;
use App\Models\LokasiAbsensi;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function __construct(private AbsensiService $absensiService) {}

    public function hariIni(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $tz = $pengaturan?->zona_waktu ?? config('app.timezone', 'Asia/Jakarta');
        $tanggal = Carbon::now($tz)->toDateString();

        $absensi = Absensi::query()
            ->with(['logAbsensis' => fn ($q) => $q->orderBy('waktu')])
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $tanggal)
            ->first();

        return response()->json([
            'data' => [
                'tanggal' => $tanggal,
                'pengaturan' => $pengaturan ? [
                    'jam_masuk' => $pengaturan->jam_masuk,
                    'jam_pulang' => $pengaturan->jam_pulang,
                    'wajib_foto' => $pengaturan->wajib_foto,
                    'wajib_lokasi' => $pengaturan->wajib_lokasi,
                    'tolak_jika_di_luar_radius' => $pengaturan->tolak_jika_di_luar_radius,
                    'ukuran_foto_maks_kb' => $pengaturan->ukuran_foto_maks_kb,
                ] : null,
                'absensi' => $absensi ? new AbsensiResource($absensi) : null,
                'bisa_masuk' => ! $absensi?->jam_masuk,
                'bisa_pulang' => (bool) $absensi?->jam_masuk && ! $absensi?->jam_pulang,
            ],
        ]);
    }

    public function lokasi(): JsonResponse
    {
        $lokasi = LokasiAbsensi::query()->aktif()->get();

        return response()->json([
            'data' => LokasiAbsensiResource::collection($lokasi),
        ]);
    }

    public function cekLokasi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lintang' => ['required', 'numeric', 'between:-90,90'],
            'bujur' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $hasil = $this->absensiService->cekLokasi((float) $data['lintang'], (float) $data['bujur']);

        return response()->json([
            'data' => [
                'dalam_radius' => $hasil['dalam_radius'],
                'jarak_meter' => $hasil['jarak_meter'],
                'lokasi' => $hasil['lokasi']
                    ? new LokasiAbsensiResource($hasil['lokasi'])
                    : null,
            ],
        ]);
    }

    public function masuk(Request $request): JsonResponse
    {
        return $this->proses($request, 'masuk');
    }

    public function pulang(Request $request): JsonResponse
    {
        return $this->proses($request, 'pulang');
    }

    public function riwayat(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Absensi::query()
            ->with(['logAbsensis' => fn ($q) => $q->orderBy('waktu')])
            ->where('user_id', $user->id)
            ->orderByDesc('tanggal');

        if ($request->filled('from')) {
            $query->whereDate('tanggal', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('tanggal', '<=', $request->query('to'));
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 15)));
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => AbsensiResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function ringkasan(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $pengaturan = PengaturanAbsensi::aktifSekarang();
        $tz = $pengaturan?->zona_waktu ?? config('app.timezone', 'Asia/Jakarta');
        $bulan = Carbon::now($tz)->startOfMonth();

        $rows = Absensi::query()
            ->where('user_id', $user->id)
            ->whereYear('tanggal', $bulan->year)
            ->whereMonth('tanggal', $bulan->month)
            ->get();

        return response()->json([
            'data' => [
                'periode' => $bulan->format('Y-m'),
                'total_hari' => $rows->count(),
                'hadir' => $rows->where('status', Absensi::STATUS_HADIR)->count(),
                'terlambat' => $rows->where('status', Absensi::STATUS_TERLAMBAT)->count(),
                'alfa' => $rows->where('status', Absensi::STATUS_ALFA)->count(),
                'cuti' => $rows->where('status', Absensi::STATUS_CUTI)->count(),
                'total_menit_terlambat' => (int) $rows->sum('menit_terlambat'),
                'total_menit_kerja' => (int) $rows->sum('menit_kerja'),
            ],
        ]);
    }

    protected function proses(Request $request, string $jenis): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'lintang' => ['nullable', 'numeric', 'between:-90,90'],
            'bujur' => ['nullable', 'numeric', 'between:-180,180'],
            'akurasi_meter' => ['nullable', 'numeric', 'min:0'],
            'nama_perangkat' => ['nullable', 'string', 'max:100'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $payload = [
            'lintang' => isset($data['lintang']) ? (float) $data['lintang'] : null,
            'bujur' => isset($data['bujur']) ? (float) $data['bujur'] : null,
            'akurasi_meter' => isset($data['akurasi_meter']) ? (float) $data['akurasi_meter'] : null,
            'nama_perangkat' => $data['nama_perangkat'] ?? 'ios-wofins',
            'alamat_ip' => $request->ip(),
            'sumber' => 'mobile',
            'meta' => [
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ],
        ];

        $foto = $request->file('foto');

        $absensi = $jenis === 'masuk'
            ? $this->absensiService->absenMasuk($user, $payload, $foto)
            : $this->absensiService->absenPulang($user, $payload, $foto);

        return response()->json([
            'message' => $jenis === 'masuk' ? 'Absen masuk berhasil.' : 'Absen pulang berhasil.',
            'data' => new AbsensiResource($absensi->load(['logAbsensis' => fn ($q) => $q->orderBy('waktu')])),
        ], 201);
    }
}
