@extends('layouts.app')

@section('title', 'Syarat & Ketentuan Berlangganan — WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
<style>
    .wf-policy-card {
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        background: #fff;
        padding: clamp(1.1rem, 3vw, 2rem);
        box-shadow: 0 16px 45px -36px rgba(11, 31, 58, 0.55);
        min-width: 0;
        max-width: 100%;
    }

    .wf-policy-copy {
        min-width: 0;
        color: var(--wf-muted);
        font-size: 0.95rem;
        line-height: 1.8;
        overflow-wrap: break-word;
    }

    .wf-policy-copy h2 {
        margin-top: 2rem;
        color: var(--wf-navy);
        font-size: 1.15rem;
        font-weight: 700;
        line-height: 1.4;
        text-transform: none;
    }

    .wf-policy-copy h2:first-child {
        margin-top: 0;
    }

    .wf-policy-copy h3 {
        margin-top: 1.15rem;
        color: var(--wf-navy);
        font-size: 1rem;
        font-weight: 700;
    }

    .wf-policy-copy p,
    .wf-policy-copy ul,
    .wf-policy-copy ol,
    .wf-policy-copy table {
        margin-top: 0.75rem;
    }

    .wf-policy-copy ul,
    .wf-policy-copy ol {
        padding-left: 1.25rem;
    }

    .wf-policy-copy ul {
        list-style: disc;
    }

    .wf-policy-copy ol {
        list-style: decimal;
    }

    .wf-policy-copy li + li {
        margin-top: 0.35rem;
    }

    .wf-policy-copy a {
        color: var(--wf-navy);
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .wf-policy-table-wrap {
        margin-top: 0.75rem;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        border: 1px solid var(--wf-line);
        border-radius: 0.75rem;
        background: #fff;
    }

    .wf-policy-copy .wf-policy-table-wrap table {
        margin-top: 0;
        width: 100%;
        border-collapse: collapse;
        font-size: 0.88rem;
        table-layout: auto;
    }

    .wf-policy-copy .wf-policy-plan-table table {
        width: max(100%, 40rem);
        min-width: 40rem;
    }

    .wf-policy-copy .wf-policy-table-wrap th,
    .wf-policy-copy .wf-policy-table-wrap td {
        border: 1px solid var(--wf-line);
        padding: 0.65rem 0.75rem;
        text-align: left;
        vertical-align: top;
        overflow-wrap: normal;
        word-break: normal;
        white-space: normal;
        hyphens: none;
    }

    .wf-policy-copy .wf-policy-table-wrap th {
        background: var(--wf-cream);
        color: var(--wf-navy);
        font-weight: 700;
    }

    .wf-policy-copy .wf-policy-plan-table th {
        white-space: nowrap;
    }

    .wf-policy-copy .wf-policy-plan-table td:nth-child(1),
    .wf-policy-copy .wf-policy-plan-table td:nth-child(2),
    .wf-policy-copy .wf-policy-plan-table td:nth-child(3) {
        white-space: nowrap;
    }

    .wf-policy-copy .wf-policy-plan-table td:nth-child(4) {
        min-width: 14rem;
    }

    /* Kartu paket di layar sempit — lebih mudah dibaca daripada tabel 4 kolom */
    .wf-policy-plan-cards {
        display: none;
        margin-top: 0.75rem;
        gap: 0.75rem;
    }

    .wf-policy-plan-card {
        border: 1px solid var(--wf-line);
        border-radius: 0.85rem;
        padding: 0.9rem 1rem;
        background: #fff;
        min-width: 0;
    }

    .wf-policy-plan-card strong {
        display: block;
        color: var(--wf-navy);
        font-size: 1rem;
        margin-bottom: 0.45rem;
    }

    .wf-policy-plan-card dl {
        margin: 0;
        display: grid;
        gap: 0.45rem;
    }

    .wf-policy-plan-card dt {
        margin: 0;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--wf-navy);
    }

    .wf-policy-plan-card dd {
        margin: 0.1rem 0 0;
        font-size: 0.9rem;
        line-height: 1.55;
        color: var(--wf-muted);
        overflow-wrap: break-word;
    }

    @media (max-width: 639px) {
        .wf-policy-plan-table {
            display: none;
        }

        .wf-policy-plan-cards {
            display: grid;
        }
    }

    @media (min-width: 640px) {
        .wf-policy-plan-cards {
            display: none !important;
        }
    }

    .wf-sign-line {
        border-bottom: 1px solid #cfc9bc;
        min-height: 1.6rem;
        margin-top: 0.35rem;
    }

    .wf-print-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: center;
        margin-top: 1.25rem;
    }

    .wf-print-actions .wf-btn-navy,
    .wf-print-actions .wf-btn-ghost {
        width: 100%;
        text-align: center;
        padding: 0.7rem 1.1rem;
        font-size: 0.875rem;
    }

    @media (min-width: 480px) {
        .wf-print-actions .wf-btn-navy,
        .wf-print-actions .wf-btn-ghost {
            width: auto;
        }
    }

    @media print {
        .wf-nav,
        .wf-footer,
        .wf-print-actions,
        .wf-hero {
            display: none !important;
        }

        .wf-page {
            background: #fff;
        }

        .wf-policy-card {
            box-shadow: none;
            border: none;
            padding: 0;
        }

        .wf-policy-copy h2 {
            break-after: avoid;
        }

        .wf-policy-plan-cards {
            display: none !important;
        }

        .wf-policy-plan-table {
            display: block !important;
        }

        .wf-policy-copy .wf-policy-table-wrap table {
            min-width: 0;
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    <div class="wf-page">
        @include('front.partials.wf-nav')

        <main>
            <section class="wf-hero bg-gradient-to-b from-white to-[var(--wf-cream)] py-12 sm:py-16">
                @include('front.partials.wf-deco-shapes')
                <div class="wf-hero-inner mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-[var(--wf-gold)]">
                        Perjanjian Layanan
                    </p>
                    <h1 class="break-words text-3xl font-bold leading-tight text-[var(--wf-navy)] sm:text-4xl">
                        Syarat &amp; Ketentuan Berlangganan WOFINS
                    </h1>
                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-[var(--wf-muted)] sm:text-base">
                        Perjanjian ini mengatur penggunaan perangkat lunak WOFINS
                        (Wedding Organizer Financial Information System) oleh pelanggan bisnis.
                    </p>
                    <p class="mt-4 text-xs font-semibold text-[var(--wf-muted)]">
                        Berlaku mulai: 19 September 2026
                    </p>
                    <div class="wf-print-actions">
                        <button type="button" onclick="window.print()" class="wf-btn-navy">
                            Cetak / simpan PDF
                        </button>
                        <a href="{{ route('kebijakan-privasi') }}" class="wf-btn-ghost">
                            Kebijakan Privasi
                        </a>
                    </div>
                </div>
            </section>

            <section class="bg-white py-10 sm:py-14">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <article class="wf-policy-card wf-policy-copy">
                        <h2>Pendahuluan</h2>
                        <p>
                            Perjanjian Berlangganan ini (“Perjanjian”) dibuat antara:
                        </p>
                        <ol>
                            <li>
                                <strong>Makna Kreatif Indonesia</strong>, berkedudukan di
                                Jl. Sintraman Jaya I No. 2148, 20 Ilir D II, Kec. Kemuning,
                                Kota Palembang, Sumatera Selatan 30137, pengelola merek dan layanan
                                <strong>WOFINS</strong> melalui situs
                                <a href="https://wofins.id">wofins.id</a> dan
                                <a href="https://app.wofins.id">app.wofins.id</a>
                                (“Penyedia”); dan
                            </li>
                            <li>
                                Orang perseorangan atau badan usaha yang mendaftar, membayar, dan/atau
                                memakai WOFINS untuk kepentingan bisnis Wedding Organizer
                                (“Pelanggan”).
                            </li>
                        </ol>
                        <p>
                            Dengan membuat akun, mencentang persetujuan, menyelesaikan pembayaran,
                            atau memakai layanan, Pelanggan menyatakan telah membaca, memahami, dan
                            terikat pada Perjanjian ini beserta
                            <a href="{{ route('kebijakan-privasi') }}">Kebijakan Privasi</a>
                            dan daftar harga di
                            <a href="{{ route('harga') }}">wofins.id/harga</a>.
                        </p>

                        <h2>Pasal 1 — Definisi</h2>
                        <ul>
                            <li><strong>Layanan</strong> adalah perangkat lunak WOFINS yang disediakan secara berlangganan (SaaS), termasuk aplikasi web, aplikasi iPhone (jika diaktifkan), API yang disediakan Penyedia, serta pembaruan yang dirilis dari waktu ke waktu.</li>
                            <li><strong>Paket</strong> adalah tingkatan layanan Starter, Professional, Business, atau Enterprise, beserta kuota, fitur, dan harga yang berlaku pada saat pemesanan.</li>
                            <li><strong>Masa Berlangganan</strong> adalah jangka waktu akses yang dibayar Pelanggan (1, 12, 24, atau 48 bulan, atau jangka lain yang disepakati tertulis).</li>
                            <li><strong>Data Pelanggan</strong> adalah data bisnis yang dimasukkan Pelanggan atau penggunanya, termasuk prospek, klien, proyek, keuangan, dokumen, dan file unggahan.</li>
                            <li><strong>Pengguna</strong> adalah individu yang diberi akses oleh Pelanggan (pemilik paket, staf, atau pihak yang diundang).</li>
                            <li><strong>Domain Khusus</strong> adalah nama domain dan/atau instalasi terpisah yang disediakan pada Paket Enterprise atau kesepakatan tertulis lain, terikat satu kode lisensi untuk satu domain.</li>
                        </ul>

                        <h2>Pasal 2 — Objek dan sifat layanan</h2>
                        <p>
                            Penyedia memberikan kepada Pelanggan hak akses non-eksklusif, tidak dapat
                            dipindahtangankan, dan terbatas untuk memakai Layanan selama Masa
                            Berlangganan aktif, sesuai Paket yang dibayar. Perjanjian ini
                            <strong>bukan</strong> jual beli kode sumber, bukan pengalihan hak cipta,
                            dan bukan perjanjian kerja sama operasional wedding.
                        </p>
                        <p>WOFINS disediakan untuk:</p>
                        <ul>
                            <li>pengelolaan prospek, vendor, produk, proyek, invoice, dan simulasi (sesuai Paket);</li>
                            <li>pencatatan keuangan, kas/bank, nota dinas, rekonsiliasi, aset, dan payroll (sesuai Paket);</li>
                            <li>dokumen, SOP, undangan crew freelance, dan laporan (sesuai Paket);</li>
                            <li>dukungan teknis sesuai tingkat Paket.</li>
                        </ul>
                        <p>
                            Fitur yang tidak termasuk dalam Paket tidak menjadi kewajiban Penyedia
                            hingga Pelanggan melakukan peningkatan Paket atau kesepakatan tertulis.
                        </p>

                        <h2>Pasal 3 — Paket, kuota, dan harga</h2>
                        <div class="wf-policy-table-wrap wf-policy-plan-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Paket</th>
                                        <th>Harga / bulan</th>
                                        <th>Pengguna</th>
                                        <th>Cakupan utama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Starter</td>
                                        <td>Rp 110.000</td>
                                        <td>1 (pemilik)</td>
                                        <td>Proyek, invoice, kas, nota dinas, laporan dasar</td>
                                    </tr>
                                    <tr>
                                        <td>Professional</td>
                                        <td>Rp 180.000</td>
                                        <td>1 (pemilik)</td>
                                        <td>Semua Starter + simulasi, draf kontrak kerja, aset, rekonsiliasi, payroll</td>
                                    </tr>
                                    <tr>
                                        <td>Business</td>
                                        <td>Rp 295.000</td>
                                        <td>Hingga 3</td>
                                        <td>Semua Professional + crew freelance, dokumen &amp; SOP, laporan AM, onboarding tim</td>
                                    </tr>
                                    <tr>
                                        <td>Enterprise</td>
                                        <td>Rp 333.333</td>
                                        <td>Tidak dibatasi kuota paket</td>
                                        <td>Semua Business + domain, hosting, SSL, cadangan, kustomisasi, support pengembang. Minimal 24 bulan (Rp 8.000.000)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="wf-policy-plan-cards" aria-label="Daftar paket langganan">
                            <article class="wf-policy-plan-card">
                                <strong>Starter</strong>
                                <dl>
                                    <div>
                                        <dt>Harga / bulan</dt>
                                        <dd>Rp 110.000</dd>
                                    </div>
                                    <div>
                                        <dt>Pengguna</dt>
                                        <dd>1 (pemilik)</dd>
                                    </div>
                                    <div>
                                        <dt>Cakupan utama</dt>
                                        <dd>Proyek, invoice, kas, nota dinas, laporan dasar</dd>
                                    </div>
                                </dl>
                            </article>
                            <article class="wf-policy-plan-card">
                                <strong>Professional</strong>
                                <dl>
                                    <div>
                                        <dt>Harga / bulan</dt>
                                        <dd>Rp 180.000</dd>
                                    </div>
                                    <div>
                                        <dt>Pengguna</dt>
                                        <dd>1 (pemilik)</dd>
                                    </div>
                                    <div>
                                        <dt>Cakupan utama</dt>
                                        <dd>Semua Starter + simulasi, draf kontrak kerja, aset, rekonsiliasi, payroll</dd>
                                    </div>
                                </dl>
                            </article>
                            <article class="wf-policy-plan-card">
                                <strong>Business</strong>
                                <dl>
                                    <div>
                                        <dt>Harga / bulan</dt>
                                        <dd>Rp 295.000</dd>
                                    </div>
                                    <div>
                                        <dt>Pengguna</dt>
                                        <dd>Hingga 3</dd>
                                    </div>
                                    <div>
                                        <dt>Cakupan utama</dt>
                                        <dd>Semua Professional + crew freelance, dokumen &amp; SOP, laporan AM, onboarding tim</dd>
                                    </div>
                                </dl>
                            </article>
                            <article class="wf-policy-plan-card">
                                <strong>Enterprise</strong>
                                <dl>
                                    <div>
                                        <dt>Harga / bulan</dt>
                                        <dd>Rp 333.333</dd>
                                    </div>
                                    <div>
                                        <dt>Pengguna</dt>
                                        <dd>Tidak dibatasi kuota paket</dd>
                                    </div>
                                    <div>
                                        <dt>Cakupan utama</dt>
                                        <dd>Semua Business + domain, hosting, SSL, cadangan, kustomisasi, support pengembang. Minimal 24 bulan (Rp 8.000.000)</dd>
                                    </div>
                                </dl>
                            </article>
                        </div>
                        <p>
                            Nilai yang dibayar di muka mengikuti durasi yang dipilih
                            (1 / 12 / 24 / 48 bulan) sebagaimana tertera pada halaman Harga dan
                            bukti pesanan. Tidak ada biaya instalasi untuk Paket Starter,
                            Professional, dan Business pada platform bersama.
                        </p>
                        <p>
                            Harga di situs belum termasuk pajak yang diwajibkan peraturan
                            perundang-undangan, kecuali dinyatakan lain pada invoice. Perubahan harga
                            hanya berlaku pada perpanjangan berikutnya, kecuali disepakati lain secara tertulis.
                        </p>

                        <h2>Pasal 4 — Pendaftaran, aktivasi, dan akun</h2>
                        <ol>
                            <li>Pelanggan wajib memberikan data yang benar, termasuk nama penanggung jawab yang berwenang mengambil keputusan pembelian perangkat lunak.</li>
                            <li>Akses diberikan setelah pembayaran dikonfirmasi Penyedia dan, jika berlaku, lisensi diaktifkan.</li>
                            <li>Untuk instalasi Domain Khusus, satu Item Purchase Code hanya berlaku untuk satu domain produksi. Pemindahan domain memerlukan penerbitan ulang sesuai prosedur Penyedia.</li>
                            <li>Pelanggan bertanggung jawab atas seluruh aktivitas pada akun perusahaan, pengelolaan peran, dan kerahasiaan kata sandi atau token perangkat.</li>
                            <li>WOFINS ditujukan untuk pengguna bisnis berusia 18 tahun atau lebih.</li>
                        </ol>

                        <h2>Pasal 5 — Pembayaran</h2>
                        <ol>
                            <li>Pembayaran dilakukan dengan transfer bank sesuai instruksi checkout, dilampiri bukti, atau cara lain yang disetujui Penyedia.</li>
                            <li>Paket aktif setelah status pesanan disetujui. Keterlambatan konfirmasi karena bukti tidak jelas menjadi tanggung jawab Pelanggan.</li>
                            <li>Perpanjangan dilakukan sebelum tanggal berakhir. Jika masa aktif habis, akses dashboard dapat ditangguhkan; Data Pelanggan tidak dihapus semata-mata karena kedaluwarsa, kecuali Pasal 12 berlaku.</li>
                            <li>
                                Pembayaran yang telah diterima bersifat <strong>tidak dapat dikembalikan</strong>
                                (non-refundable), termasuk sisa masa yang tidak terpakai karena
                                pengakhiran oleh Pelanggan, kecuali:
                                <ul>
                                    <li>Pembayaran ganda yang terbukti; atau</li>
                                    <li>Layanan tidak dapat disediakan oleh Penyedia tanpa kesalahan Pelanggan, dan para pihak tidak dapat menyediakan pengganti yang wajar dalam 14 hari kerja.</li>
                                </ul>
                            </li>
                            <li>Peningkatan Paket dapat dilakukan setiap saat; selisih biaya dihitung secara proporsional menurut kebijakan operasional Penyedia pada saat pengajuan.</li>
                        </ol>

                        <h2>Pasal 6 — Kewajiban Penyedia</h2>
                        <p>Penyedia akan, dengan upaya wajar secara komersial:</p>
                        <ul>
                            <li>menyediakan Layanan sesuai Paket;</li>
                            <li>menerapkan kontrol akses berbasis peran, koneksi terenkripsi, dan pengamanan yang wajar;</li>
                            <li>melakukan pemeliharaan, pembaruan, dan perbaikan gangguan;</li>
                            <li>memberikan dukungan sesuai tingkat Paket:
                                Starter pada jam kerja; Professional dengan target respons 1 hari kerja;
                                Business melalui saluran prioritas termasuk WhatsApp;
                                Enterprise melalui dukungan langsung pengembang;</li>
                            <li>pada Paket Enterprise, membantu setup domain, hosting, SSL, dan cadangan selama Masa Berlangganan aktif, sesuai ketersediaan nama domain.</li>
                        </ul>
                        <p>
                            Layanan tidak dijamin bebas gangguan, bebas kesalahan, atau tersedia 100%
                            setiap saat. Pemeliharaan terjadwal atau keadaan di luar kendali wajar
                            Penyedia tidak dianggap wanprestasi semata-mata karena adanya jeda akses.
                        </p>

                        <h2>Pasal 7 — Kewajiban dan larangan Pelanggan</h2>
                        <p>Pelanggan wajib:</p>
                        <ul>
                            <li>memakai Layanan hanya untuk kegiatan usaha yang sah;</li>
                            <li>memastikan kebenaran data yang diunggah dan memiliki dasar hukum untuk memproses data klien, vendor, dan karyawan;</li>
                            <li>tidak melebihi kuota Paket, kecuali disepakati tertulis;</li>
                            <li>tidak membagikan akun secara tidak sah, tidak melakukan reverse engineering, scraping berlebihan, atau merusak keamanan sistem;</li>
                            <li>tidak menempatkan malware, konten melanggar hukum, atau data yang Pelanggan tidak berhak memproses;</li>
                            <li>mematuhi hukum Indonesia, termasuk perlindungan data pribadi.</li>
                        </ul>

                        <h2>Pasal 8 — Data Pelanggan dan privasi</h2>
                        <ol>
                            <li>Data Pelanggan tetap milik Pelanggan. Penyedia memproses data tersebut untuk menyediakan Layanan, keamanan, dukungan, dan kewajiban hukum.</li>
                            <li>Pelanggan adalah pengendali data atas data klien/vendor/karyawannya. Penyedia bertindak sebagai pemroses sebatas keperluan Layanan.</li>
                            <li>Penyedia tidak menjual Data Pelanggan dan tidak memakai data bisnis untuk iklan lintas aplikasi.</li>
                            <li>Rincian pemrosesan data mengikuti Kebijakan Privasi yang merupakan bagian tidak terpisahkan dari Perjanjian ini.</li>
                            <li>Pelanggan dapat meminta ekspor atau penghapusan data yang memenuhi syarat melalui <a href="mailto:support@wofins.id">support@wofins.id</a>, dengan verifikasi identitas dan persetujuan administrator perusahaan.</li>
                        </ol>

                        <h2>Pasal 9 — Kekayaan intelektual</h2>
                        <p>
                            Nama, merek, desain, kode, dokumentasi, dan seluruh kekayaan intelektual
                            WOFINS milik Penyedia atau pemberi lisensinya. Pelanggan hanya memperoleh
                            hak pakai terbatas selama Masa Berlangganan. Dilarang menyalin, menyewakan,
                            mensublisensikan, atau membuat karya turunan dari perangkat lunak tanpa
                            izin tertulis.
                        </p>
                        <p>
                            Template dokumen yang dihasilkan Layanan (invoice, simulasi, draf kontrak
                            kerja wedding, dan sejenisnya) boleh dipakai Pelanggan untuk operasional
                            usahanya sendiri, tanpa mengalihkan hak atas perangkat lunak.
                        </p>

                        <h2>Pasal 10 — Kerahasiaan</h2>
                        <p>
                            Para pihak menjaga kerahasiaan informasi non-publik yang diperoleh karena
                            Perjanjian ini, kecuali informasi yang sudah umum diketahui, wajib
                            diungkapkan hukum, atau diizinkan pihak pemilik informasi.
                        </p>

                        <h2>Pasal 11 — Batasan tanggung jawab</h2>
                        <ol>
                            <li>Layanan disediakan “sebagaimana adanya” dan “sebagaimana tersedia”.</li>
                            <li>Penyedia tidak bertanggung jawab atas keputusan bisnis Pelanggan, keakuratan data yang diinput Pengguna, sengketa Pelanggan dengan klien/vendornya, atau kerugian karena kata sandi yang bocor di sisi Pelanggan.</li>
                            <li>Sejauh diizinkan hukum, tanggung jawab kumulatif Penyedia atas klaim yang timbul dari Perjanjian ini dibatasi sebesar biaya langganan yang benar-benar dibayar Pelanggan kepada Penyedia untuk 12 (dua belas) bulan terakhir sebelum klaim.</li>
                            <li>Penyedia tidak bertanggung jawab atas kerugian tidak langsung, kehilangan keuntungan, kehilangan data yang dapat dicegah dengan cadangan wajar di sisi Pelanggan, atau kerusakan reputasi.</li>
                        </ol>

                        <h2>Pasal 12 — Jangka waktu dan pengakhiran</h2>
                        <ol>
                            <li>Perjanjian berlaku sejak aktivasi hingga akhir Masa Berlangganan, dan berlanjut jika diperpanjang.</li>
                            <li>Pelanggan dapat berhenti memakai Layanan kapan saja; sisa iuran tidak dikembalikan sesuai Pasal 5.</li>
                            <li>Penyedia dapat menangguhkan atau mengakhiri akses jika Pelanggan wanprestasi, menyalahgunakan Layanan, atau pembayaran tidak sah, setelah pemberitahuan yang wajar kecuali ada risiko keamanan mendesak.</li>
                            <li>Setelah pengakhiran, Pelanggan dapat meminta salinan Data Pelanggan dalam format yang wajar dalam 14 hari kalender. Setelah itu Penyedia boleh menghapus data dari sistem produksi sesuai siklus cadangan.</li>
                        </ol>

                        <h2>Pasal 13 — Perubahan layanan dan ketentuan</h2>
                        <p>
                            Penyedia berhak memperbaiki, menambah, atau menonaktifkan fitur sepanjang
                            fungsi utama Paket tetap tersedia secara wajar, atau menawarkan alternatif.
                            Perubahan materiil atas Perjanjian ini akan diumumkan di situs atau melalui
                            email. Pemakaian Layanan setelah tanggal berlaku perubahan merupakan
                            persetujuan, kecuali Pelanggan mengakhiri langganan sebelum tanggal tersebut.
                        </p>

                        <h2>Pasal 14 — Keadaan kahar</h2>
                        <p>
                            Tidak ada pihak yang wanprestasi semata-mata karena kegagalan memenuhi
                            kewajiban akibat peristiwa di luar kendali wajar, termasuk bencana,
                            gangguan listrik atau jaringan nasional, tindakan pemerintah, atau
                            serangan siber yang tidak dapat dicegah dengan pengamanan yang wajar.
                        </p>

                        <h2>Pasal 15 — Hukum yang berlaku dan sengketa</h2>
                        <p>
                            Perjanjian ini tunduk pada hukum Republik Indonesia. Sengketa diselesaikan
                            terlebih dahulu secara musyawarah dalam 30 hari kalender. Apabila tidak
                            tercapai kesepakatan, sengketa diajukan ke pengadilan di wilayah hukum
                            Kota Palembang, kecuali peraturan memaksa menentukan lain.
                        </p>

                        <h2>Pasal 16 — Ketentuan lain</h2>
                        <ul>
                            <li>Apabila suatu pasal tidak sah, pasal lainnya tetap berlaku.</li>
                            <li>Kegagalan menegakkan suatu hak tidak berarti pengesampingan hak tersebut.</li>
                            <li>Pelanggan tidak boleh mengalihkan Perjanjian tanpa persetujuan tertulis Penyedia. Penyedia boleh mengalihkan kepada afiliasi atau penerus usaha dengan pemberitahuan.</li>
                            <li>Perjanjian ini, Kebijakan Privasi, invoice/pesanan yang disetujui, dan lampiran tertulis merupakan kesepakatan lengkap para pihak mengenai Layanan.</li>
                        </ul>

                        <h2>Pasal 17 — Kontak resmi</h2>
                        <ul>
                            <li>Email: <a href="mailto:support@wofins.id">support@wofins.id</a> · <a href="mailto:office@wofins.id">office@wofins.id</a></li>
                            <li>WhatsApp: <a href="https://wa.me/6281373183794" target="_blank" rel="noopener noreferrer">+62 813-7318-3794</a></li>
                            <li>Situs: <a href="https://wofins.id">https://wofins.id</a></li>
                        </ul>

                        <h2>Lampiran — Lembar identitas untuk penandatanganan</h2>
                        <p>
                            Salinan bernama (identitas pemilik company, paket, dan tanggal langganan)
                            diunduh dari menu <strong>Administrasi → Perusahaan → Kontrak</strong>
                            setelah akun perusahaan aktif. Lampiran kosong di bawah dipakai jika
                            para pihak menandatangani salinan tercetak tanpa mengisi sistem.
                        </p>
                        <div class="wf-policy-table-wrap">
                            <table>
                                <tbody>
                                    <tr>
                                        <th style="width: 38%;">Nama badan / usaha Pelanggan</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Nama penanggung jawab / jabatan</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Alamat</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Email / telepon</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Paket &amp; durasi</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Nilai berlangganan</th>
                                        <td>Rp <div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Domain (jika Enterprise)</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal mulai / berakhir</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                    <tr>
                                        <th>Nomor pesanan / invoice</th>
                                        <td><div class="wf-sign-line"></div></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-8 grid grid-cols-1 gap-10 sm:grid-cols-2">
                            <div>
                                <p class="font-bold text-[var(--wf-navy)]">Pihak Pertama</p>
                                <p>Makna Kreatif Indonesia</p>
                                <p>Pengelola WOFINS</p>
                                <div class="mt-16 wf-sign-line"></div>
                                <p class="mt-2 text-sm">Nama, jabatan, dan tanda tangan</p>
                                <p class="mt-4 text-sm">Materai Rp 10.000 (jika diwajibkan)</p>
                            </div>
                            <div>
                                <p class="font-bold text-[var(--wf-navy)]">Pihak Kedua</p>
                                <p>Pelanggan / kuasa yang berwenang</p>
                                <p>&nbsp;</p>
                                <div class="mt-16 wf-sign-line"></div>
                                <p class="mt-2 text-sm">Nama, jabatan, dan tanda tangan</p>
                                <p class="mt-4 text-sm">Materai Rp 10.000 (jika diwajibkan)</p>
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </main>

        @include('front.partials.wf-footer')
    </div>
@endsection
