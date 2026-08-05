<?php

namespace Database\Seeders;

use App\Models\HariJadwalKerja;
use App\Models\HariLibur;
use App\Models\JadwalKerja;
use App\Models\PenugasanJadwal;
use App\Models\User;
use Illuminate\Database\Seeder;

class JadwalKerjaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Jadwal Kerja, Hari Libur & Penugasan...');

        $users = User::query()->orderBy('id')->get();
        if ($users->isEmpty()) {
            $this->command->error('No users found. Run UserSeeder first.');

            return;
        }

        $kantor = JadwalKerja::query()->updateOrCreate(
            ['kode' => 'KTR-REG'],
            [
                'nama' => 'Jadwal Kantor Reguler',
                'default' => true,
                'aktif' => true,
                'deskripsi' => 'Senin–Jumat 09:00–18:00, istirahat 60 menit.',
            ]
        );

        $fleksibel = JadwalKerja::query()->updateOrCreate(
            ['kode' => 'KTR-FLEX'],
            [
                'nama' => 'Jadwal Fleksibel',
                'default' => false,
                'aktif' => true,
                'deskripsi' => 'Senin–Jumat 10:00–19:00 untuk tim field / event.',
            ]
        );

        $this->seedHari($kantor, '09:00:00', '18:00:00');
        $this->seedHari($fleksibel, '10:00:00', '19:00:00');

        // Assign schedule to every user (2026–2027)
        foreach ($users as $index => $user) {
            $jadwal = $index % 3 === 0 ? $fleksibel : $kantor;

            PenugasanJadwal::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'jadwal_kerja_id' => $jadwal->id,
                    'berlaku_dari' => '2026-01-01',
                ],
                [
                    'berlaku_sampai' => '2027-12-31',
                    'catatan' => 'Penugasan seeder 2026–2027 untuk '.$user->name,
                ]
            );
        }

        $hariLibur = [
            ['tanggal' => '2026-01-01', 'nama' => 'Tahun Baru 2026', 'nasional' => true],
            ['tanggal' => '2026-03-20', 'nama' => 'Nyepi 2026', 'nasional' => true],
            ['tanggal' => '2026-05-01', 'nama' => 'Hari Buruh 2026', 'nasional' => true],
            ['tanggal' => '2026-05-14', 'nama' => 'Kenaikan Isa Almasih 2026', 'nasional' => true],
            ['tanggal' => '2026-06-01', 'nama' => 'Hari Lahir Pancasila', 'nasional' => true],
            ['tanggal' => '2026-08-17', 'nama' => 'Hari Kemerdekaan RI', 'nasional' => true],
            ['tanggal' => '2026-12-25', 'nama' => 'Hari Natal 2026', 'nasional' => true],
            ['tanggal' => '2026-12-31', 'nama' => 'Cuti Bersama Akhir Tahun', 'nasional' => false],
            ['tanggal' => '2027-01-01', 'nama' => 'Tahun Baru 2027', 'nasional' => true],
            ['tanggal' => '2027-05-01', 'nama' => 'Hari Buruh 2027', 'nasional' => true],
            ['tanggal' => '2027-06-01', 'nama' => 'Hari Lahir Pancasila 2027', 'nasional' => true],
            ['tanggal' => '2027-08-17', 'nama' => 'Hari Kemerdekaan RI 2027', 'nasional' => true],
            ['tanggal' => '2027-12-25', 'nama' => 'Hari Natal 2027', 'nasional' => true],
        ];

        foreach ($hariLibur as $libur) {
            HariLibur::query()->updateOrCreate(
                ['tanggal' => $libur['tanggal']],
                [
                    'nama' => $libur['nama'],
                    'nasional' => $libur['nasional'],
                    'tetap_masuk' => false,
                    'catatan' => 'Seeded holiday '.$libur['tanggal'],
                ]
            );
        }

        $this->command->info('✅ JadwalKerjaSeeder: '.JadwalKerja::count().' jadwal, '.PenugasanJadwal::count().' penugasan, '.HariLibur::count().' hari libur.');
    }

    private function seedHari(JadwalKerja $jadwal, string $masuk, string $pulang): void
    {
        for ($hari = 0; $hari <= 6; $hari++) {
            $hariKerja = $hari >= 1 && $hari <= 5;

            HariJadwalKerja::query()->updateOrCreate(
                [
                    'jadwal_kerja_id' => $jadwal->id,
                    'hari' => $hari,
                ],
                [
                    'hari_kerja' => $hariKerja,
                    'jam_masuk' => $hariKerja ? $masuk : null,
                    'jam_pulang' => $hariKerja ? $pulang : null,
                    'menit_istirahat' => $hariKerja ? 60 : 0,
                ]
            );
        }
    }
}
