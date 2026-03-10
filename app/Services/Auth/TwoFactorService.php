<?php
namespace App\Services\Auth;

use App\Models\User;
use App\Mail\AdminOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class TwoFactorService
{
    // Génère et envoie le code OTP par mail
    public function sendOtpEmail(User $user): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("2fa_otp:{$user->id}", $code, now()->addMinutes(10));
        Mail::to($user->email)->send(new AdminOtpMail($code));
    }

    // Vérifie le code OTP
    public function verifyOtp(User $user, string $code): bool
    {
        $cached = Cache::get("2fa_otp:{$user->id}");
        if (!$cached || $cached !== $code) return false;
        Cache::forget("2fa_otp:{$user->id}");
        return true;
    }

    // Garde ces méthodes pour ne pas casser le reste du code existant
    public function generatePendingSecret(User $user): array { return []; }
    public function confirmEnable(User $user, string $code): void {}
    public function verifyActiveSecret(User $user, string $code): bool
    {
        return $this->verifyOtp($user, $code);
    }
    public function disable(User $user, string $code): void {}
}