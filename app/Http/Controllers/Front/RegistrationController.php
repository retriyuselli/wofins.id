<?php

namespace App\Http\Controllers\Front;

use App\Enums\ProspectAppStatus;
use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\ProspectApp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RegistrationController extends Controller
{
    public function pendaftaran()
    {
        $industries = Cache::remember('front.industries', 3600, function () {
            return Industry::query()->get();
        });

        $prospect = null;
        $user = Auth::user();

        if ($user) {
            $prospect = ProspectApp::query()
                ->with('industry')
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhere('email', $user->email);
                })
                ->latest('submitted_at')
                ->latest('id')
                ->first();

            // Sudah mengirim & menunggu tinjauan → jangan tampilkan form lagi
            if ($prospect && $prospect->status === ProspectAppStatus::Pending) {
                return redirect()
                    ->route('account.pending')
                    ->with('info', 'Pendaftaran Anda sedang ditinjau admin. Mohon menunggu aktivasi akun.');
            }
        }

        return view('front.pendaftaran', [
            'industries' => $industries,
            'prospect' => $prospect,
        ]);
    }
}
