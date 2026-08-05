<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ContactInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'need' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'paket' => ['nullable', 'string', 'max:255'],
        ]);

        $redirectParams = array_filter(['paket' => $request->input('paket_slug')]);
        $supportEmail = config('mail.support_email', 'support@wofins.id');

        try {
            $inquiry = DB::transaction(function () use ($data) {
                return ContactInquiry::query()->create([
                    ...$data,
                    'status' => 'new',
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Failed to save contact inquiry', [
                'message' => $e->getMessage(),
                'email' => $data['email'],
            ]);

            return redirect()
                ->route('kontak', $redirectParams)
                ->withInput()
                ->with('error', 'Gagal menyimpan pesan. Silakan coba lagi atau hubungi kami via WhatsApp.');
        }

        // Notifikasi ke admin
        try {
            Mail::send('emails.contact-inquiry', ['data' => $data, 'inquiry' => $inquiry], function ($message) use ($data, $supportEmail) {
                $message->to($supportEmail)
                    ->replyTo($data['email'], $data['name'])
                    ->subject('Pesan Kontak WOFINS — '.$data['name']);
            });
        } catch (Throwable $e) {
            Log::warning('Failed to send contact admin notification', [
                'message' => $e->getMessage(),
                'inquiry_id' => $inquiry->id,
            ]);
        }

        // Email balasan ke pengirim
        try {
            Mail::send('emails.contact-confirmation', ['inquiry' => $inquiry], function ($message) use ($inquiry) {
                $message->to($inquiry->email, $inquiry->name)
                    ->subject('Pesan Anda sudah kami terima — WOFINS');
            });
        } catch (Throwable $e) {
            Log::warning('Failed to send contact confirmation email', [
                'message' => $e->getMessage(),
                'inquiry_id' => $inquiry->id,
            ]);
        }

        return redirect()
            ->route('kontak', $redirectParams)
            ->with('success', 'Terima kasih. Pesan Anda sudah kami terima dan akan segera ditindaklanjuti oleh tim admin.');
    }
}
