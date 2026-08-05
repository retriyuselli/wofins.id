<?php

namespace App\Http\Controllers;

use App\Enums\ProspectAppStatus;
use App\Models\Industry;
use App\Models\ProspectApp;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProspectAppController extends Controller
{
    /**
     * Display the prospect application form.
     */
    public function create()
    {
        $industries = Industry::orderBy('industry_name')->get();

        return view('prospect-app.form-tailwind', compact('industries'));
    }

    /**
     * Display the comprehensive prospect registration form.
     */
    public function pendaftaran()
    {
        $industries = Industry::where('is_active', true)->orderBy('industry_name')->get();

        return view('front.pendaftaran', compact('industries'));
    }

    /**
     * Store a newly created prospect application.
     *
     * User: name, email, phone_number
     * ProspectApp: company + business registration fields
     */
    public function store(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = $request->user();

        // Email wajib sesuai akun login (abaikan perubahan dari client)
        if ($authUser) {
            $request->merge([
                'email' => $authUser->email,
                'full_name' => $request->input('full_name') ?: $authUser->name,
            ]);
        }

        $existingProspect = null;
        if ($authUser) {
            $existingProspect = ProspectApp::query()
                ->where(function ($q) use ($authUser) {
                    $q->where('user_id', $authUser->id)
                        ->orWhere('email', $authUser->email);
                })
                ->latest('id')
                ->first();
        }

        if ($existingProspect && $existingProspect->status === ProspectAppStatus::Approved) {
            return redirect()
                ->route('pendaftaran')
                ->with('error', 'Pendaftaran Anda sudah disetujui. Silakan gunakan Dashboard.');
        }

        $existingProspectId = $existingProspect?->id;

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('prospect_apps', 'email')->ignore($existingProspectId),
            ],
            'phone' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'company_name' => 'required|string|max:255',
            'industry_id' => 'required|exists:industries,id',
            'name_of_website' => 'nullable|string|max:255',
            'user_size' => 'required|in:1-10,11-50,51-200,201-500,501-1000,1000+',
            'service' => 'required|in:hastana,non_hastana',
            'reason_for_interest' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'terms' => 'accepted',
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar sebelumnya.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'industry_id.required' => 'Departemen wajib dipilih.',
            'industry_id.exists' => 'Departemen yang dipilih tidak valid.',
            'user_size.required' => 'Jumlah karyawan wajib dipilih.',
            'user_size.in' => 'Jumlah karyawan tidak valid.',
            'service.required' => 'Paket layanan wajib dipilih.',
            'service.in' => 'Paket layanan tidak valid.',
            'reason_for_interest.required' => 'Kebutuhan & tantangan bisnis wajib diisi.',
            'reason_for_interest.max' => 'Kebutuhan & tantangan bisnis maksimal 1000 karakter.',
            'notes.max' => 'Catatan tambahan maksimal 2000 karakter.',
            'terms.accepted' => 'Anda harus menyatakan sebagai pengambil keputusan.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $prospect = DB::transaction(function () use ($request, $authUser, $existingProspectId) {
                // Field milik User (akun login)
                if ($authUser) {
                    $authUser->forceFill([
                        'name' => $request->full_name,
                        'phone_number' => $request->phone,
                    ])->save();
                }

                // Field bisnis / pendaftaran → ProspectApp
                $prospectData = [
                    'user_id' => $authUser?->id,
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'position' => $request->boolean('terms') ? 'Decision Maker' : ($request->position ?: null),
                    'company_name' => $request->company_name,
                    'industry_id' => $request->industry_id,
                    'name_of_website' => $request->name_of_website,
                    'user_size' => $request->user_size,
                    'service' => $request->service,
                    'reason_for_interest' => $request->reason_for_interest,
                    'notes' => $request->notes,
                    'status' => 'pending',
                    'submitted_at' => now(),
                ];

                if ($existingProspectId) {
                    $prospect = ProspectApp::query()->findOrFail($existingProspectId);
                    $prospect->fill($prospectData)->save();

                    return $prospect->fresh(['industry']);
                }

                return ProspectApp::query()->create($prospectData)->load('industry');
            });

            try {
                $this->sendNotificationEmail($prospect);
            } catch (Exception $e) {
                Log::error('Failed to send prospect notification email: '.$e->getMessage());
            }

            return redirect()
                ->route('pendaftaran', array_filter(['plan' => $request->input('plan') ?: $request->query('plan')]))
                ->with('success', 'Pendaftaran Anda berhasil dikirim. Tim admin WOFINS akan segera menghubungi Anda.');
        } catch (Exception $e) {
            Log::error('Failed to create prospect application: '.$e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan sistem. Silakan coba lagi.'])
                ->withInput();
        }
    }

    /**
     * Send notification email to admin and prospect
     */
    private function sendNotificationEmail(ProspectApp $prospect)
    {
        $prospect->loadMissing('industry');
        $supportEmail = config('mail.support_email', 'support@wofins.id');

        // Notifikasi ke support@wofins.id
        Mail::send('emails.prospect-app.admin-notification', compact('prospect'), function ($message) use ($prospect, $supportEmail) {
            $message->to($supportEmail)
                ->replyTo($prospect->email, $prospect->full_name)
                ->subject('Pendaftaran Prospek WOFINS — '.$prospect->full_name);
        });

        // Email balasan ke pengirim
        Mail::send('emails.prospect-app.confirmation', compact('prospect'), function ($message) use ($prospect) {
            $message->to($prospect->email, $prospect->full_name)
                ->subject('Pendaftaran Anda sudah kami terima — WOFINS');
        });
    }

    /**
     * Show success page
     */
    public function success()
    {
        return view('prospect-app.success-tailwind');
    }

    /**
     * Check if email already exists (for AJAX validation)
     */
    public function checkEmail(Request $request)
    {
        $exists = ProspectApp::where('email', $request->email)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Email sudah terdaftar sebelumnya.' : 'Email tersedia.',
        ]);
    }

    /**
     * Generate PDF proposal for prospect application.
     */
    public function generatePdf($id)
    {
        $prospect = ProspectApp::findOrFail($id);
        Gate::authorize('view', $prospect);

        $pdf = Pdf::loadView('prospect-app.proposal-pdf', compact('prospect'));

        return $pdf->download('proposal_prospect_'.$prospect->id.'.pdf');
    }

    /**
     * Generate PDF proposal for prospect application
     */
    public function generateProposalPdf(ProspectApp $prospectApp)
    {
        Gate::authorize('view', $prospectApp);

        try {
            // Load prospect app with industry relationship
            $prospectApp->load('industry');

            // Generate PDF
            $pdf = Pdf::loadView('invoices.prospectapp', compact('prospectApp'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'sans-serif',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $filename = 'invoice-'.$prospectApp->company_name.'-'.$prospectApp->id.'.pdf';

            return $pdf->stream($filename);
        } catch (Exception $e) {
            Log::error('PDF Generation Error: '.$e->getMessage());

            return redirect()->back()->with('error', 'Gagal generate proposal PDF: '.$e->getMessage());
        }
    }
}
