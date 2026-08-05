<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\KoreksiAbsensi;
use App\Models\LogAbsensi;
use App\Models\LokasiAbsensi;
use App\Models\PengajuanLembur;
use App\Models\PengaturanAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        $this->seedAttendanceData();
    }

    private function seedSettings(): void
    {
        PengaturanAbsensi::query()->updateOrCreate(
            ['nama' => 'Aturan Kantor Default'],
            [
                'jam_masuk' => '09:00:00',
                'jam_pulang' => '18:00:00',
                'toleransi_terlambat_menit' => 15,
                'toleransi_pulang_cepat_menit' => 10,
                'wajib_pulang' => true,
                'wajib_lokasi' => true,
                'wajib_foto' => true,
                'tolak_jika_di_luar_radius' => true,
                'akurasi_gps_maksimal_meter' => 100,
                'ukuran_foto_maks_kb' => 2048,
                'zona_waktu' => 'Asia/Jakarta',
                'aktif' => true,
                'catatan' => 'Pengaturan awal modul absensi Wofins. Sesuaikan koordinat lokasi kantor.',
            ]
        );

        LokasiAbsensi::query()->updateOrCreate(
            ['nama' => 'Kantor HQ'],
            [
                'lintang' => -6.2000000,
                'bujur' => 106.8166660,
                'radius_meter' => 150,
                'aktif' => true,
                'alamat' => 'Ganti dengan alamat kantor sebenarnya',
                'urutan' => 1,
            ]
        );
    }

    private function seedAttendanceData(): void
    {
        $this->command->info('Seeding Absensi, Log, Koreksi & Lembur (2026)...');

        $users = User::query()->orderBy('id')->get();
        $approvers = User::query()->whereIn('email', [
            'superadmin@wofins.com',
            'sarah.wijaya@wofins.com',
            'qoyyum@wofins.com',
            'sinta.maharani@wofins.com',
        ])->get();

        if ($approvers->isEmpty()) {
            $approvers = $users->take(3);
        }

        $lokasi = LokasiAbsensi::query()->where('aktif', true)->first();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Run UserSeeder first.');

            return;
        }

        $absensiCount = 0;
        $logCount = 0;
        $koreksiCount = 0;
        $lemburCount = 0;

        // Seed ~15 weekdays in Aug 2026 for every user
        $start = Carbon::parse('2026-08-01');
        $end = Carbon::parse('2026-08-20');

        foreach ($users as $userIndex => $user) {
            $approver = $approvers->firstWhere('id', '!=', $user->id) ?? $approvers->first();

            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                if ($date->isWeekend()) {
                    Absensi::query()->updateOrCreate(
                        ['user_id' => $user->id, 'tanggal' => $date->toDateString()],
                        [
                            'status' => 'libur_mingguan',
                            'jam_masuk' => null,
                            'jam_pulang' => null,
                            'menit_kerja' => 0,
                            'menit_terlambat' => 0,
                            'menit_pulang_cepat' => 0,
                            'sumber' => 'sistem',
                            'catatan' => 'Weekend otomatis',
                            'disetujui_oleh' => null,
                        ]
                    );
                    $absensiCount++;

                    continue;
                }

                $isLate = ($userIndex + $date->day) % 5 === 0;
                $isAlfa = ($userIndex + $date->day) % 17 === 0;
                $status = $isAlfa ? 'alfa' : ($isLate ? 'terlambat' : 'hadir');

                $jamMasuk = null;
                $jamPulang = null;
                $menitKerja = 0;
                $menitTerlambat = 0;
                $menitPulangCepat = 0;

                if ($status !== 'alfa') {
                    $masukHour = $isLate ? 9 : 8;
                    $masukMinute = $isLate ? rand(20, 45) : rand(45, 59);
                    $jamMasuk = $date->copy()->setTime($masukHour, $masukMinute, 0);
                    $jamPulang = $date->copy()->setTime(18, rand(0, 30), 0);
                    $menitKerja = (int) $jamMasuk->diffInMinutes($jamPulang);
                    $menitTerlambat = $isLate ? max(0, (int) $date->copy()->setTime(9, 0)->diffInMinutes($jamMasuk, false)) : 0;
                }

                $absensi = Absensi::query()->updateOrCreate(
                    ['user_id' => $user->id, 'tanggal' => $date->toDateString()],
                    [
                        'status' => $status,
                        'jam_masuk' => $jamMasuk,
                        'jam_pulang' => $jamPulang,
                        'menit_kerja' => $menitKerja,
                        'menit_terlambat' => $menitTerlambat,
                        'menit_pulang_cepat' => $menitPulangCepat,
                        'sumber' => $status === 'alfa' ? 'sistem' : collect(['mobile', 'web'])->random(),
                        'catatan' => $status === 'alfa' ? 'Tidak absen (seed)' : 'Absensi seeder '.$user->name,
                        'disetujui_oleh' => $status === 'alfa' ? null : $approver?->id,
                    ]
                );
                $absensiCount++;

                if ($status === 'alfa' || ! $jamMasuk) {
                    continue;
                }

                // Log masuk
                LogAbsensi::query()->updateOrCreate(
                    [
                        'absensi_id' => $absensi->id,
                        'user_id' => $user->id,
                        'jenis' => 'masuk',
                    ],
                    [
                        'waktu' => $jamMasuk,
                        'lokasi_absensi_id' => $lokasi?->id,
                        'lintang' => -6.2000000 + (rand(-5, 5) / 10000),
                        'bujur' => 106.8166660 + (rand(-5, 5) / 10000),
                        'akurasi_meter' => rand(5, 40),
                        'jarak_ke_kantor_meter' => rand(5, 80),
                        'dalam_radius' => true,
                        'path_foto' => null,
                        'nama_perangkat' => collect(['iPhone 15', 'Pixel 8', 'Samsung S24'])->random(),
                        'alamat_ip' => '192.168.1.'.rand(10, 200),
                        'meta' => ['seed' => true, 'user' => $user->email],
                        'valid' => true,
                        'alasan_tolak' => null,
                    ]
                );
                $logCount++;

                // Log pulang
                LogAbsensi::query()->updateOrCreate(
                    [
                        'absensi_id' => $absensi->id,
                        'user_id' => $user->id,
                        'jenis' => 'pulang',
                    ],
                    [
                        'waktu' => $jamPulang,
                        'lokasi_absensi_id' => $lokasi?->id,
                        'lintang' => -6.2000000 + (rand(-5, 5) / 10000),
                        'bujur' => 106.8166660 + (rand(-5, 5) / 10000),
                        'akurasi_meter' => rand(5, 40),
                        'jarak_ke_kantor_meter' => rand(5, 80),
                        'dalam_radius' => true,
                        'path_foto' => null,
                        'nama_perangkat' => collect(['iPhone 15', 'Pixel 8', 'Samsung S24'])->random(),
                        'alamat_ip' => '192.168.1.'.rand(10, 200),
                        'meta' => ['seed' => true, 'user' => $user->email],
                        'valid' => true,
                        'alasan_tolak' => null,
                    ]
                );
                $logCount++;

                // Koreksi for late days
                if ($isLate && $date->day % 2 === 0) {
                    KoreksiAbsensi::query()->updateOrCreate(
                        [
                            'absensi_id' => $absensi->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'jam_masuk_diajukan' => $date->copy()->setTime(8, 55, 0),
                            'jam_pulang_diajukan' => $jamPulang,
                            'alasan' => 'Meeting klien di luar kantor, terlambat clock-in. Diajukan oleh '.$user->name,
                            'status' => collect(['menunggu', 'disetujui', 'ditolak'])->random(),
                            'ditinjau_oleh' => $approver?->id,
                            'ditinjau_pada' => now()->subDays(rand(0, 5)),
                            'catatan_peninjau' => 'Ditinjau oleh '.($approver?->name ?? 'admin'),
                        ]
                    );
                    $koreksiCount++;
                }

                // Lembur once per user on mid-month workday
                if ($date->day === 12) {
                    $mulai = $date->copy()->setTime(18, 30, 0);
                    $selesai = $date->copy()->setTime(21, 0, 0);

                    PengajuanLembur::query()->updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'tanggal' => $date->toDateString(),
                        ],
                        [
                            'absensi_id' => $absensi->id,
                            'mulai_pada' => $mulai,
                            'selesai_pada' => $selesai,
                            'menit' => 150,
                            'alasan' => 'Persiapan event wedding malam hari — '.$user->name,
                            'status' => collect(['menunggu', 'disetujui'])->random(),
                            'disetujui_oleh' => $approver?->id,
                            'disetujui_pada' => now()->subDays(rand(0, 3)),
                            'catatan' => 'Seed lembur 2026',
                        ]
                    );
                    $lemburCount++;
                }
            }
        }

        $this->command->info("✅ Absensi: {$absensiCount}, Log: {$logCount}, Koreksi: {$koreksiCount}, Lembur: {$lemburCount}");
    }
}
