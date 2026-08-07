@extends('profile.layout')

@section('profile-page-title', 'Panduan Bank Statement')
@section('profile-page-subtitle', 'Penjelasan istilah perbankan di menu Bank Statement')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="space-y-6">
    <div>
        <a href="{{ route('profile.admin-tools.bank-statements') }}" class="wf-admin-link-chip">Kembali</a>
    </div>

    @foreach ([
        [
            'title' => 'Status Parsing',
            'items' => [
                ['Pending', 'Statement sudah terdaftar, tetapi proses parsing file mutasi belum dijalankan atau belum selesai.'],
                ['Processing', 'File mutasi sedang diproses untuk dibaca dan diubah menjadi transaksi (hasil parsing).'],
                ['Parsed', 'Parsing selesai dan data transaksi hasil parsing sudah tersimpan.'],
                ['Failed', 'Proses parsing gagal (format file tidak cocok, kolom tidak ditemukan, atau error saat import).'],
            ],
        ],
        [
            'title' => 'Status Rekonsiliasi',
            'items' => [
                ['Uploaded', 'File/template rekonsiliasi sudah diunggah, tetapi proses import/matching belum dijalankan atau belum selesai.'],
                ['Processing', 'Proses import item rekonsiliasi atau proses pencocokan (matching) sedang berjalan.'],
                ['Completed', 'Proses import item rekonsiliasi selesai dan data siap dibandingkan dengan transaksi aplikasi.'],
                ['Failed', 'Import/matching rekonsiliasi gagal.'],
            ],
        ],
        [
            'title' => 'Istilah di Tabel',
            'items' => [
                ['Rekening', 'Nama rekening/metode pembayaran yang dipakai sebagai sumber statement.'],
                ['Periode', 'Rentang tanggal statement (awal–akhir) yang dicakup oleh file mutasi.'],
                ['Debit', 'Total uang keluar pada periode statement.'],
                ['Kredit', 'Total uang masuk pada periode statement.'],
                ['Saldo Akhir', 'Saldo terakhir pada akhir periode statement (closing balance).'],
                ['trx', 'Jumlah transaksi hasil parsing pada statement tersebut.'],
                ['item', 'Jumlah baris item rekonsiliasi yang ter-import untuk statement tersebut.'],
                ['Kenapa debit/kredit bisa 0?', 'Biasanya karena statement masih pending/processing atau transaksi hasil parsing belum ada (trx = 0), sehingga total debit/kredit belum terisi.'],
            ],
        ],
    ] as $section)
        <div class="wf-profile-card overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60">
                <h3 class="text-base font-bold text-[var(--wf-navy)]">{{ $section['title'] }}</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    @foreach ($section['items'] as [$label, $desc])
                        <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                            <div class="font-bold text-[var(--wf-navy)]">{{ $label }}</div>
                            <div class="text-[var(--wf-muted)] mt-1 text-xs leading-relaxed">{{ $desc }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
