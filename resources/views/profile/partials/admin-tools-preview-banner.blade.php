@if ($adminToolsReadonly ?? false)
    <div class="wf-profile-card mb-6 overflow-hidden border border-[var(--wf-gold)]/35">
        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 bg-[rgba(201,162,39,0.10)]">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--wf-gold)] text-[var(--wf-navy-deep)]">
                <i class="fa-solid fa-eye text-sm"></i>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-[var(--wf-navy)]">Mode pratinjau Admin Tools</p>
                <p class="mt-0.5 text-xs text-[var(--wf-muted)] leading-relaxed">
                    Anda dapat melihat tampilan menu ini. Interaksi di area konten (klik, cari, unggah, ubah data) dinonaktifkan.
                    Gunakan menu di sidebar untuk berpindah halaman.
                </p>
            </div>
        </div>
    </div>
@endif
