<?php

namespace App\Services\Auth;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Cache;

class TwoFactorService
{
    /**
     * Contruct
     */
    public function __construct(private Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }


    /**
     * Génère un secret "pending" (pas encore activé).
     */
    public function generatePendingSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        Cache::put("2fa_pending_secret:{$user->id}", $secret, now()->addMinutes(10));

        // Pour QR code: otpauth://
        $appName = config('App.name', 'DocSpace');
        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret
        );

        return [
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
        ];
    }

    /**
     * Confirme l'activation 2FA avec le code.
     */
    public function confirmEnable(User $user, string $code): void
    {
        $secret = Cache::get("2fa_pending_secret:{$user->id}");

        if (!$secret) {
            throw new \Exception('Aucune activation 2FA en attente (réessaie /2fa/enable).');
        }

        $valid = $this->google2fa->verifyKey($secret, $code);

        if (!$valid) {
            throw new \Exception('Code 2FA invalide');
        }

        $user->two_factor_secret = $secret;
        $user->two_factor_enable_at = now();
        $user->save();

        Cache::forget("2fa_pending_secret:{$user->id}");
    }

    /**
     * Vérifie un code par rapport au secret déjà activé.
     */
    public function verifyActiveSecret(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        // window = 1 pour tolérer un léger décalage de temps (optionnel)
        return $this->google2fa->verifyKey($user->two_factor_secret, $code, 1);
    }


    public function disable(User $user, string $code): void
    {
        if (!$user->two_factor_secret) {
            throw new \Exception('2FA non activé.');
        }

        if (!$this->verifyActiveSecret($user, $code)) {
            throw new \Exception('Code 2FA invalide');
        }

        $user->two_factor_secret = null;
        $user->save();
    }
}
