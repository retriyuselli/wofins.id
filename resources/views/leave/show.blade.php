@extends('profile.layout')

@section('title', ($editRequest ? 'Edit Permohonan Cuti' : 'Ajukan Cuti') . ' - ' . (($user ?? null)?->name ?? Auth::user()->name))
@section('profile-page-title', $editRequest ? 'Edit Permohonan Cuti' : 'Ajukan Permohonan Cuti')
@section('profile-page-subtitle', $editRequest
    ? 'Perbarui detail permohonan cuti yang masih menunggu persetujuan'
    : 'Lengkapi formulir untuk mengajukan cuti. Pastikan data sudah benar sebelum dikirim.')

@section('profile-content')
@php
    $usagePercent = $annualLeaveAllowance > 0 ? ($usedLeave / $annualLeaveAllowance) * 100 : 0;
    $proLocked = $proFeatureLocked ?? \App\Support\ProFeatures::locked(\App\Support\PricingPlans::FEATURE_EMPLOYEE_PORTAL);
@endphp

@include('profile.partials.pro-preview-banner')

@if ($errors->any())
    <div class="wf-alert-err">
        <p class="text-sm font-semibold mb-1">Terdapat kesalahan dalam form:</p>
        <ul class="text-sm list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div id="draftRestoreSlot"></div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 {{ $proLocked ? 'wf-pro-readonly' : '' }}">
    <div class="xl:col-span-2 space-y-6">
        <div class="wf-profile-card">
            <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-gradient-to-r from-[var(--wf-navy)] to-[#14335a]">
                <h2 class="text-lg font-bold text-white">
                    {{ $editRequest ? 'Edit Formulir Cuti' : 'Formulir Permohonan Cuti' }}
                </h2>
                <p class="text-sm text-white/70 mt-1">
                    {{ $editRequest ? 'Ubah data lalu kirim ulang untuk diproses' : 'Field bertanda * wajib diisi' }}
                </p>
            </div>

            <div class="p-6">
                <form id="leaveRequestForm"
                      action="{{ $editRequest ? route('leave.update', $editRequest->id) : route('leave.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="space-y-5">
                    @csrf
                    @if ($editRequest)
                        @method('PUT')
                    @endif

                    <div>
                        <label for="leave_type_id" class="block text-sm font-semibold text-[var(--wf-navy)]">
                            Jenis Cuti <span class="text-red-500">*</span>
                        </label>
                        <select id="leave_type_id" name="leave_type_id" required class="wf-field">
                            <option value="" disabled {{ old('leave_type_id', $editRequest?->leave_type_id) ? '' : 'selected' }}>
                                Pilih jenis cuti...
                            </option>
                            @foreach ($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('leave_type_id', $editRequest?->leave_type_id) == $type->id)>
                                    {{ $type->name }} ({{ $type->max_days_per_year }} hari/tahun)
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="start_date" class="block text-sm font-semibold text-[var(--wf-navy)]">
                                Tanggal Mulai <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="start_date" name="start_date" required
                                   value="{{ old('start_date', $editRequest?->start_date?->format('Y-m-d')) }}"
                                   min="{{ date('Y-m-d') }}"
                                   class="wf-field">
                            @error('start_date')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-semibold text-[var(--wf-navy)]">
                                Tanggal Selesai <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date" required
                                   value="{{ old('end_date', $editRequest?->end_date?->format('Y-m-d')) }}"
                                   min="{{ date('Y-m-d') }}"
                                   class="wf-field">
                            @error('end_date')
                                <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="rounded-xl border border-[var(--wf-gold)]/30 bg-[rgba(201,162,39,0.08)] px-4 py-3.5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-sm font-semibold text-[var(--wf-navy)]">
                            <i class="fa-solid fa-calendar-day text-[var(--wf-gold)]"></i>
                            Total Hari Cuti
                        </div>
                        <span id="totalDays" class="text-xl font-bold text-[var(--wf-navy)]">0 hari</span>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-semibold text-[var(--wf-navy)]">
                            Alasan Cuti <span class="text-red-500">*</span>
                        </label>
                        <textarea id="reason" name="reason" rows="4" required maxlength="500"
                                  placeholder="Jelaskan alasan mengajukan cuti (minimal 10 karakter)..."
                                  class="wf-field">{{ old('reason', $editRequest?->reason) }}</textarea>
                        @error('reason')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-1.5 text-xs text-[var(--wf-muted)] flex justify-between">
                            <span>Minimal 10 karakter</span>
                            <span id="reasonCount">0/500 karakter</span>
                        </div>
                    </div>

                    <div>
                        <label for="emergency_contact" class="block text-sm font-semibold text-[var(--wf-navy)]">
                            Kontak Darurat <span class="font-normal text-[var(--wf-muted)]">(opsional)</span>
                        </label>
                        <input type="text" id="emergency_contact" name="emergency_contact"
                               value="{{ old('emergency_contact', $editRequest?->emergency_contact) }}"
                               placeholder="Nama dan nomor telepon yang dapat dihubungi"
                               class="wf-field">
                        @error('emergency_contact')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="replacement_employee_id" class="block text-sm font-semibold text-[var(--wf-navy)]">
                            Karyawan Pengganti <span class="font-normal text-[var(--wf-muted)]">(opsional)</span>
                        </label>
                        <p class="mt-1 text-xs text-[var(--wf-muted)]">Pilih karyawan yang menggantikan tugas Anda selama cuti.</p>
                        <select id="replacement_employee_id" name="replacement_employee_id" class="wf-field">
                            <option value="" {{ old('replacement_employee_id', $editRequest?->replacement_employee_id) ? '' : 'selected' }}>
                                Tidak ada pengganti
                            </option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected(old('replacement_employee_id', $editRequest?->replacement_employee_id) == $employee->id)>
                                    {{ $employee->name }}
                                    @if ($employee->department)
                                        — {{ $employee->department }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('replacement_employee_id')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="documents" class="block text-sm font-semibold text-[var(--wf-navy)]">
                            Dokumen Pendukung <span class="font-normal text-[var(--wf-muted)]">(opsional)</span>
                        </label>
                        <div id="documentsDropzone"
                             class="mt-1.5 rounded-xl border-2 border-dashed border-[var(--wf-line)] bg-[var(--wf-cream)]/50 px-5 py-6 text-center cursor-pointer transition hover:border-[var(--wf-gold)]"
                             onclick="document.getElementById('documents').click()">
                            <div class="mx-auto mb-2 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white border border-[var(--wf-line)] text-[var(--wf-gold)]">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <input type="file" id="documents" name="documents[]" multiple
                                   accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                            <p id="documentsLabel" class="text-sm font-medium text-[var(--wf-navy)]">
                                Klik untuk unggah dokumen
                            </p>
                            <p class="mt-1 text-xs text-[var(--wf-muted)]">PDF, JPG, PNG — maks. 2MB per file</p>
                            <p id="documentsList" class="file-list mt-2 text-xs text-[var(--wf-muted)] hidden"></p>
                        </div>
                        @error('documents.*')
                            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row gap-3 pt-4 border-t border-[var(--wf-line)]">
                        <button type="button" onclick="saveDraft()"
                                class="wf-btn-ghost inline-flex items-center justify-center px-5 py-2.5 text-sm">
                            Simpan Draft
                        </button>
                        <a href="{{ route('leave.status') }}"
                           class="wf-pro-allow wf-btn-ghost inline-flex items-center justify-center px-5 py-2.5 text-sm">
                            Lihat Status
                        </a>
                        <button type="submit" id="submitBtn"
                                class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm sm:ml-auto disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="submit-text">{{ $editRequest ? 'Perbarui Permohonan' : 'Kirim Permohonan' }}</span>
                            <span class="submit-loading hidden items-center gap-2">
                                <i class="fa-solid fa-spinner fa-spin"></i>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="wf-profile-card p-5">
            <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)]">
                    <i class="fa-solid fa-wallet text-sm"></i>
                </span>
                Saldo Cuti Anda
            </h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-[var(--wf-muted)]">Total jatah</span>
                    <span class="font-semibold text-[var(--wf-navy)]">{{ $annualLeaveAllowance }} hari</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-[var(--wf-muted)]">Telah digunakan</span>
                    <span class="font-semibold text-red-600">{{ $usedLeave }} hari</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-[var(--wf-muted)]">Sisa tersedia</span>
                    <span class="font-semibold text-emerald-700">{{ $remainingLeave }} hari</span>
                </div>
                <div class="pt-1">
                    <div class="w-full bg-[var(--wf-cream)] rounded-full h-2.5 border border-[var(--wf-line)] overflow-hidden">
                        <div class="h-full rounded-full bg-[var(--wf-navy)] transition-all duration-300"
                             style="width: {{ min($usagePercent, 100) }}%"></div>
                    </div>
                    <p class="mt-2 text-center text-xs text-[var(--wf-muted)]">
                        {{ number_format($usagePercent, 1) }}% terpakai
                    </p>
                </div>
            </div>
        </div>

        <div class="wf-profile-card p-5">
            <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-2">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)]">
                    <i class="fa-solid fa-lightbulb text-sm"></i>
                </span>
                Tips Pengajuan
            </h3>
            <ul class="space-y-2.5 text-sm text-[var(--wf-muted)]">
                @foreach ([
                    'Ajukan minimal 3 hari sebelumnya',
                    'Sertakan alasan yang jelas',
                    'Periksa saldo cuti sebelum mengajukan',
                    'Unggah dokumen jika diperlukan',
                    'Tentukan karyawan pengganti bila perlu',
                    'Hubungi HR untuk situasi darurat',
                ] as $tip)
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-[var(--wf-gold)] mt-0.5 text-xs"></i>
                        <span>{{ $tip }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="wf-profile-card p-5">
            <h3 class="text-base font-bold text-[var(--wf-navy)] mb-3">Aksi Cepat</h3>
            <div class="space-y-2.5">
                <a href="{{ route('leave.status') }}"
                   class="wf-pro-allow wf-btn-ghost w-full inline-flex items-center justify-center px-4 py-2.5 text-sm">
                    Riwayat & Status Cuti
                </a>
                <a href="{{ route('profile.compensation') }}"
                   class="wf-pro-allow wf-btn-ghost w-full inline-flex items-center justify-center px-4 py-2.5 text-sm">
                    Kompensasi & Saldo
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function calculateTotalDays() {
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const el = document.getElementById('totalDays');

        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            if (end >= start) {
                const diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
                el.textContent = diffDays + ' hari';
                return;
            }
        }
        el.textContent = '0 hari';
    }

    document.getElementById('reason').addEventListener('input', function () {
        document.getElementById('reasonCount').textContent = this.value.length + '/500 karakter';
    });

    document.getElementById('start_date').addEventListener('change', function () {
        const startDate = this.value;
        const endDateInput = document.getElementById('end_date');
        endDateInput.min = startDate;
        if (endDateInput.value && endDateInput.value < startDate) {
            endDateInput.value = '';
        }
        calculateTotalDays();
    });
    document.getElementById('end_date').addEventListener('change', calculateTotalDays);

    function showToast(message, type = 'ok') {
        const el = document.createElement('div');
        el.className = 'fixed top-4 right-4 z-[110] px-5 py-3 rounded-xl shadow-lg text-sm font-semibold text-white transition-all duration-300 ' +
            (type === 'ok' ? 'bg-[var(--wf-navy)]' : 'bg-red-600');
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateX(12px)';
            setTimeout(() => el.remove(), 250);
        }, 2800);
    }

    function saveDraft() {
        try {
            const formData = new FormData(document.getElementById('leaveRequestForm'));
            const hasData = formData.get('leave_type_id') ||
                formData.get('start_date') ||
                formData.get('end_date') ||
                formData.get('reason') ||
                formData.get('emergency_contact') ||
                formData.get('replacement_employee_id');

            if (!hasData) {
                showToast('Tidak ada data untuk disimpan sebagai draft.', 'err');
                return;
            }

            localStorage.setItem('leaveRequestDraft', JSON.stringify({
                leave_type_id: formData.get('leave_type_id'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                reason: formData.get('reason'),
                emergency_contact: formData.get('emergency_contact'),
                replacement_employee_id: formData.get('replacement_employee_id'),
                timestamp: new Date().toISOString()
            }));

            showToast('Draft berhasil disimpan.');
        } catch (error) {
            console.error('Error saving draft:', error);
            showToast('Gagal menyimpan draft.', 'err');
        }
    }

    document.getElementById('leaveRequestForm').addEventListener('submit', function () {
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.querySelector('.submit-text').classList.add('hidden');
        submitBtn.querySelector('.submit-loading').classList.remove('hidden');
        submitBtn.querySelector('.submit-loading').classList.add('inline-flex');
        submitBtn.disabled = true;
        localStorage.removeItem('leaveRequestDraft');
    });

    document.getElementById('documents').addEventListener('change', function (e) {
        const files = e.target.files;
        const zone = document.getElementById('documentsDropzone');
        const label = document.getElementById('documentsLabel');
        const list = document.getElementById('documentsList');
        const maxSize = 2 * 1024 * 1024;
        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

        if (!files.length) {
            label.textContent = 'Klik untuk unggah dokumen';
            list.classList.add('hidden');
            list.textContent = '';
            zone.classList.remove('border-[var(--wf-gold)]', 'bg-[rgba(201,162,39,0.08)]');
            return;
        }

        let oversized = false;
        let invalid = false;
        Array.from(files).forEach((file) => {
            if (file.size > maxSize) oversized = true;
            if (!allowedTypes.includes(file.type)) invalid = true;
        });

        if (oversized) {
            showToast('Beberapa file melebihi 2MB.', 'err');
            e.target.value = '';
            return;
        }
        if (invalid) {
            showToast('Format harus PDF, JPG, atau PNG.', 'err');
            e.target.value = '';
            return;
        }

        label.textContent = files.length + ' file dipilih';
        list.textContent = Array.from(files).map((f) => f.name.length > 28 ? f.name.slice(0, 25) + '...' : f.name).join(', ');
        list.classList.remove('hidden');
        zone.classList.add('border-[var(--wf-gold)]', 'bg-[rgba(201,162,39,0.08)]');
    });

    function restoreDraft() {
        try {
            const savedDraft = localStorage.getItem('leaveRequestDraft');
            if (!savedDraft) return;
            const draftData = JSON.parse(savedDraft);

            if (draftData.leave_type_id) document.getElementById('leave_type_id').value = draftData.leave_type_id;
            if (draftData.start_date) document.getElementById('start_date').value = draftData.start_date;
            if (draftData.end_date) document.getElementById('end_date').value = draftData.end_date;
            if (draftData.reason) {
                document.getElementById('reason').value = draftData.reason;
                document.getElementById('reasonCount').textContent = draftData.reason.length + '/500 karakter';
            }
            if (draftData.emergency_contact) document.getElementById('emergency_contact').value = draftData.emergency_contact;
            if (draftData.replacement_employee_id) document.getElementById('replacement_employee_id').value = draftData.replacement_employee_id;

            if (draftData.start_date && draftData.end_date) calculateTotalDays();

            const slot = document.getElementById('draftRestoreSlot');
            if (slot) slot.innerHTML = '';
            showToast('Draft berhasil dipulihkan.');
        } catch (error) {
            console.error('Error restoring draft:', error);
            clearDraft();
            showToast('Gagal memulihkan draft.', 'err');
        }
    }

    function clearDraft() {
        localStorage.removeItem('leaveRequestDraft');
        const slot = document.getElementById('draftRestoreSlot');
        if (slot) slot.innerHTML = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const reason = document.getElementById('reason');
        if (reason.value) {
            document.getElementById('reasonCount').textContent = reason.value.length + '/500 karakter';
        }
        calculateTotalDays();

        try {
            const savedDraft = localStorage.getItem('leaveRequestDraft');
            if (!savedDraft) return;
            const draftData = JSON.parse(savedDraft);
            if (!draftData.timestamp) {
                localStorage.removeItem('leaveRequestDraft');
                return;
            }

            const draftAge = new Date() - new Date(draftData.timestamp);
            if (draftAge > 7 * 24 * 60 * 60 * 1000) {
                localStorage.removeItem('leaveRequestDraft');
                return;
            }

            const slot = document.getElementById('draftRestoreSlot');
            slot.innerHTML = `
                <div class="wf-profile-card mb-6 p-4 border border-[var(--wf-gold)]/35 bg-[rgba(201,162,39,0.08)]">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-bold text-[var(--wf-navy)]">Draft tersimpan</h4>
                            <p class="text-xs text-[var(--wf-muted)] mt-0.5">
                                Terakhir disimpan ${new Date(draftData.timestamp).toLocaleString('id-ID')}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="restoreDraft()" class="wf-btn-navy px-4 py-2 text-xs">Pulihkan</button>
                            <button type="button" onclick="clearDraft()" class="wf-btn-ghost px-4 py-2 text-xs">Hapus</button>
                        </div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading draft:', error);
            localStorage.removeItem('leaveRequestDraft');
        }
    });
</script>
@endpush
@endsection
