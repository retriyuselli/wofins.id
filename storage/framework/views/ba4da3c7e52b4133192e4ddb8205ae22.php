<?php $__env->startSection('title', 'Kebijakan Privasi — WOFINS'); ?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('front.partials.wf-front-base-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
    .wf-policy-card {
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        background: #fff;
        padding: clamp(1.1rem, 3vw, 2rem);
        box-shadow: 0 16px 45px -36px rgba(11, 31, 58, 0.55);
    }

    .wf-policy-copy {
        min-width: 0;
        color: var(--wf-muted);
        font-size: 0.95rem;
        line-height: 1.8;
        overflow-wrap: anywhere;
    }

    .wf-policy-copy h2 {
        margin-top: 2rem;
        color: var(--wf-navy);
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1.4;
    }

    .wf-policy-copy h2:first-child {
        margin-top: 0;
    }

    .wf-policy-copy p,
    .wf-policy-copy ul {
        margin-top: 0.75rem;
    }

    .wf-policy-copy ul {
        list-style: disc;
        padding-left: 1.25rem;
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
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="wf-page">
        <?php echo $__env->make('front.partials.wf-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main>
            <section class="wf-hero bg-gradient-to-b from-white to-[var(--wf-cream)] py-12 sm:py-16">
                <?php echo $__env->make('front.partials.wf-deco-shapes', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="wf-hero-inner mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.2em] text-[var(--wf-gold)]">
                        Privasi &amp; Data
                    </p>
                    <h1 class="break-words text-3xl font-bold leading-tight text-[var(--wf-navy)] sm:text-4xl">
                        Kebijakan Privasi WOFINS
                    </h1>
                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-[var(--wf-muted)] sm:text-base">
                        Kami menjelaskan data yang diproses saat Anda menggunakan situs web, aplikasi iPhone,
                        dan layanan WOFINS.
                    </p>
                    <p class="mt-4 text-xs font-semibold text-[var(--wf-muted)]">
                        Terakhir diperbarui: 17 September 2026
                    </p>
                </div>
            </section>

            <section class="bg-white py-10 sm:py-14">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <article class="wf-policy-card wf-policy-copy">
                        <h2>1. Tentang kebijakan ini</h2>
                        <p>
                            WOFINS adalah layanan pengelolaan proyek, operasional, dan keuangan untuk bisnis
                            Wedding Organizer. Layanan ini dioperasikan oleh Makna Kreatif Indonesia
                            (&ldquo;kami&rdquo;). Kebijakan ini berlaku untuk situs <strong>wofins.id</strong>,
                            aplikasi WOFINS di iPhone, serta layanan terkait.
                        </p>

                        <h2>2. Data yang kami proses</h2>
                        <p>Data yang diproses bergantung pada fitur yang digunakan dan dapat meliputi:</p>
                        <ul>
                            <li>Data akun dan profil, seperti nama, alamat email, nomor telepon, foto profil, perusahaan, dan peran pengguna.</li>
                            <li>Data bisnis yang Anda masukkan, termasuk prospek, klien, proyek, jadwal acara, produk, vendor, dan catatan operasional.</li>
                            <li>Data keuangan bisnis, seperti pemasukan, pengeluaran, pembayaran, piutang, rekening, bukti transaksi, dan laporan.</li>
                            <li>Dokumen atau file yang Anda pilih untuk diunggah, termasuk kontrak, PDF, foto, dan bukti pembayaran.</li>
                            <li>Data autentikasi dan teknis, seperti token sesi, nama perangkat, alamat IP, waktu akses, dan log keamanan.</li>
                            <li>Pesan atau informasi yang dikirim melalui formulir kontak dan dukungan pelanggan.</li>
                        </ul>
                        <p>
                            Aplikasi hanya mengakses foto atau file yang Anda pilih. Jika Face ID digunakan
                            untuk membuka sesi, pemeriksaan biometrik dilakukan oleh sistem iOS pada perangkat;
                            WOFINS tidak menerima atau menyimpan data biometrik Anda.
                        </p>

                        <h2>3. Cara kami memperoleh data</h2>
                        <p>
                            Data diperoleh ketika Anda atau administrator perusahaan membuat akun, mengisi
                            formulir, menggunakan fitur WOFINS, mengunggah file, menghubungi dukungan, atau
                            masuk menggunakan Google. Informasi teknis tertentu dicatat otomatis untuk
                            menjalankan dan mengamankan layanan.
                        </p>

                        <h2>4. Tujuan penggunaan data</h2>
                        <ul>
                            <li>Menyediakan fitur proyek, transaksi, laporan, dokumen, dan pengelolaan akun.</li>
                            <li>Mengautentikasi pengguna dan menerapkan hak akses berdasarkan peran.</li>
                            <li>Menyimpan, menyinkronkan, serta menampilkan data perusahaan pada perangkat pengguna yang berwenang.</li>
                            <li>Memberikan dukungan, menangani permintaan, dan menyampaikan pemberitahuan layanan.</li>
                            <li>Mendeteksi penyalahgunaan, menjaga keamanan, melakukan audit, dan memperbaiki gangguan.</li>
                            <li>Memenuhi kewajiban hukum serta menjaga keberlangsungan layanan.</li>
                        </ul>

                        <h2>5. Layanan login dan pihak ketiga</h2>
                        <p>
                            Jika Anda memilih Google Sign-In atau Sign in with Apple, kami menerima informasi
                            dasar yang Anda izinkan, seperti identitas akun, nama, dan alamat email, untuk
                            memverifikasi serta menautkan login. Sign in with Apple mendukung alamat email relay
                            privat. Penggunaan layanan Google juga tunduk pada
                            <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">Kebijakan Privasi Google</a>.
                            Sign in with Apple tunduk pada
                            <a href="https://www.apple.com/legal/privacy/" target="_blank" rel="noopener noreferrer">Kebijakan Privasi Apple</a>.
                        </p>
                        <p>
                            Kami dapat menggunakan penyedia infrastruktur, penyimpanan, email, atau layanan
                            teknis lain yang diperlukan untuk mengoperasikan WOFINS. Penyedia tersebut hanya
                            diperbolehkan memproses data sesuai kebutuhan layanan dan kewajiban perlindungan data.
                        </p>

                        <h2>6. Pembagian data</h2>
                        <p>
                            Kami tidak menjual data pribadi dan tidak menggunakan data bisnis Anda untuk
                            iklan lintas aplikasi. Data dapat dibagikan secara terbatas kepada penyedia layanan,
                            administrator perusahaan Anda, pihak yang Anda beri akses, atau otoritas yang
                            berwenang apabila diwajibkan oleh hukum.
                        </p>

                        <h2>7. Penyimpanan dan keamanan</h2>
                        <p>
                            Kami menerapkan kontrol akses berbasis peran, koneksi terenkripsi, pembatasan sesi,
                            pencatatan aktivitas, dan pengamanan teknis lain yang wajar. Token autentikasi
                            aplikasi disimpan menggunakan fasilitas aman iOS. Meskipun demikian, tidak ada
                            sistem elektronik yang dapat dijamin sepenuhnya bebas risiko.
                        </p>

                        <h2>8. Retensi data</h2>
                        <p>
                            Data disimpan selama akun atau langganan aktif dan selama diperlukan untuk tujuan
                            operasional, keamanan, penyelesaian sengketa, serta kewajiban hukum. Setelah
                            penghapusan, sebagian data dapat tetap berada dalam cadangan untuk waktu terbatas
                            sebelum terhapus melalui siklus pencadangan.
                        </p>

                        <h2 id="hak-pengguna">9. Pilihan dan hak pengguna</h2>
                        <p>Anda dapat meminta untuk:</p>
                        <ul>
                            <li>Mengakses atau memperbarui data profil Anda.</li>
                            <li>Memperbaiki data yang tidak akurat.</li>
                            <li>Memperoleh penjelasan mengenai pemrosesan data.</li>
                            <li>Menonaktifkan akun atau menghapus data yang memenuhi syarat.</li>
                        </ul>
                        <p>
                            Permintaan dapat dikirim ke
                            <a href="mailto:support@wofins.id?subject=Permintaan%20Privasi%20WOFINS">support@wofins.id</a>.
                            Untuk melindungi akun, kami dapat meminta verifikasi identitas dan persetujuan
                            administrator perusahaan. Data tertentu mungkin harus dipertahankan apabila
                            diwajibkan oleh hukum atau masih diperlukan untuk mencegah penyalahgunaan.
                        </p>

                        <h2>10. Cookie dan penyimpanan lokal</h2>
                        <p>
                            Situs web dapat menggunakan cookie yang diperlukan untuk login, keamanan, pilihan
                            pengguna, dan fungsi layanan. Aplikasi iPhone menggunakan penyimpanan lokal yang
                            diperlukan untuk sesi dan preferensi, seperti tema tampilan. Kami tidak menggunakan
                            cookie untuk menjual data pribadi.
                        </p>

                        <h2>11. Privasi anak</h2>
                        <p>
                            WOFINS ditujukan untuk pengguna bisnis dan bukan untuk anak di bawah usia 18 tahun.
                            Kami tidak secara sengaja mengumpulkan data pribadi anak melalui aplikasi.
                        </p>

                        <h2>12. Perubahan kebijakan</h2>
                        <p>
                            Kebijakan ini dapat diperbarui untuk menyesuaikan perubahan fitur, praktik keamanan,
                            atau ketentuan hukum. Tanggal pembaruan terbaru akan ditampilkan pada bagian atas halaman.
                        </p>

                        <h2>13. Hubungi kami</h2>
                        <p>
                            Pertanyaan atau permintaan terkait privasi dapat disampaikan kepada:
                        </p>
                        <ul>
                            <li>Makna Kreatif Indonesia</li>
                            <li>Email: <a href="mailto:support@wofins.id">support@wofins.id</a></li>
                            <li>WhatsApp: <a href="https://wa.me/6281373183794" target="_blank" rel="noopener noreferrer">+62 813-7318-3794</a></li>
                            <li>Lokasi: Palembang, Indonesia</li>
                        </ul>
                    </article>
                </div>
            </section>
        </main>

        <?php echo $__env->make('front.partials.wf-footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/front/kebijakan-privasi.blade.php ENDPATH**/ ?>