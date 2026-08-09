# Perbedaan Paket WOFINS

> Sumber kebenaran di kode: `App\Support\PricingPlans` + `PlanResourceGate` + `CompanySubscription::quotaResources()`.  
> Halaman publik: `/harga` memakai `PricingPlans::compareRows()`.  
> PDF: [`analisa-paket/paket.pdf`](./paket.pdf) (generate ulang dari view `resources/views/pdf/paket-perbandingan.blade.php`).  
> Update: **8 Agustus 2026**

**Model:** 1 WO = 1 Company = beberapa pengguna. Paket menempel di `companies.subscription_plan` (`starter` / `professional` / `business`), **bukan** role Spatie.

---

## Ringkasan cepat

| | Starter | Professional | Business |
|---|:---:|:---:|:---:|
| Harga bulanan | Rp 99.000 | Rp 180.000 | Rp 295.000 |
| Harga tahunan (11 bln) | Rp 1.089.000 | Rp 1.980.000 | Rp 3.245.000 |
| Fokus | Proyek + keuangan dasar | + Nota dinas, rekonsiliasi, payroll dasar | + HRIS, dokumen, laporan, role |
| Cocok untuk | WO baru merapikan operasional | WO aktif harian | WO multi-fungsi / tim besar |

---

## Kuota resource (enforce di aplikasi)

| Resource | Starter | Professional | Business |
|---|---:|---:|---:|
| Pengguna (seat) | 3 | 10 | 25 |
| Vendor | 10 | 50 | 200 |
| Produk | 10 | 50 | 200 |
| Proyek wedding | 10 | 50 | 200 |
| Prospek | 30 | 150 | 500 |
| Simulasi | 20 | 100 | 400 |
| Rekening bank/kas | 2 | 5 | 15 |
| Aset tetap | 5 | 25 | 100 |
| Piutang | 20 | 100 | 500 |
| Pembayaran piutang | 50 | 300 | 2.000 |
| Pendapatan wedding | 100 | 500 | 2.000 |
| Pengeluaran wedding | 100 | 500 | 2.000 |
| Pengeluaran operasional | 50 | 300 | 1.000 |
| Pendapatan lain | 30 | 150 | 500 |
| Pengeluaran lain | 30 | 150 | 500 |

### Tanpa kuota paket

| Item | Ketentuan |
|---|---|
| **Kategori** | Tidak ada kuota. Create / update / delete **hanya super admin**. Tim WO bisa melihat & memakai. |
| **Crew freelance** | Termasuk fitur proyek (semua paket). Bukan akun user — tidak makan seat. Bisa diisi admin atau lewat **link undangan publik** `/crew/{token}`. |

---

## Fitur (feature gate)

| Fitur | Starter | Professional | Business |
|---|:---:|:---:|:---:|
| Manajemen proyek (order, prospek, simulasi, produk, vendor, crew) | ✅ | ✅ | ✅ |
| Keuangan dasar (kas, piutang, aset, rekening) | ✅ | ✅ | ✅ |
| Nota dinas digital | — | ✅ | ✅ |
| Rekonsiliasi rekening koran | — | ✅ | ✅ |
| Payroll | — | Dasar | Lengkap |
| Dokumen & SOP | — | — | ✅ |
| Domain gratis | — | — | ✅ |
| HRIS & absensi GPS | — | — | ✅ |
| Status karyawan (master) | — | — | ✅* |
| Portal karyawan (ESS) | — | — | ✅ |
| Laporan lanjutan (target AM, dll.) | — | — | ✅ |
| Multi-approval workflow | — | — | ✅ |
| Manajemen role & permission | — | — | ✅ |
| Onboarding & training | — | — | ✅ |
| Support | Standar | Prioritas | WhatsApp |

\*Status karyawan: mutate hanya **super admin**; menu digating paket Business (HRIS).

`feature_keys` di `PricingPlans`:

- **Starter:** `projects`, `basic_finance`
- **Professional:** + `nota_dinas`, `reconciliation`, `payroll`
- **Business:** + `documents`, `hris`, `employee_portal`, `advanced_reports`, `multi_approval`, `role_management`

---

## Perbedaan inti (narasi)

### Starter
Merapikan proyek wedding dan kas dasar. Kuota kecil, tanpa rekonsiliasi/nota dinas/HRIS.

### Professional
Volume lebih besar + alur kas harian lebih rapi: **nota dinas**, **rekonsiliasi rekening**, **payroll dasar**, support prioritas.

### Business
Untuk tim lintas fungsi: **HRIS/absensi**, portal karyawan, dokumen/SOP, laporan lanjutan, multi-approval, manajemen role, domain, onboarding, support WA.

---

## Catatan teknis

1. Override kuota per company bisa di-set super admin (kecuali kategori yang sudah tidak di-quota).
2. Super admin platform bypass gate paket.
3. Role Spatie (`pengunjung`, `finance`, `employee`, …) = jabatan kerja, bukan tier paket.
4. Setelah ubah kuota/fitur di `PricingPlans`, refresh `/harga` — tabel banding ikut `compareRows()`.
