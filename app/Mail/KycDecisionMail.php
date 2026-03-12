<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class KycDecisionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nomVendeur,
        public string $decision,
        public ?string $commentaire = null,
    ) {}

    public function build()
    {
        $isValide  = $this->decision === 'valide';
        $subject   = $isValide
            ? '✅ Votre compte vendeur est validé — DocSpace'
            : '❌ Votre vérification KYC a été refusée — DocSpace';

        $couleur   = $isValide ? '#1DBF73' : '#e53e3e';
        $icon      = $isValide ? '✅' : '❌';
        $titre     = $isValide
            ? 'Félicitations, votre compte est vérifié !'
            : 'Votre dossier KYC a été refusé';

        $message   = $isValide
            ? 'Votre identité a été vérifiée avec succès. Vous pouvez dès maintenant publier vos annonces d\'équipements médicaux sur DocSpace.'
            : 'Après examen de votre dossier, votre demande de vérification KYC n\'a pas pu être approuvée.';

        $commentaireHtml = '';
        if (!$isValide && $this->commentaire) {
            $commentaireHtml = "
                <div style='margin-top:16px;padding:16px;background:#fff5f5;border-left:4px solid #e53e3e;border-radius:6px'>
                    <p style='color:#c53030;font-size:13px;font-weight:600;margin:0 0 4px'>Motif du refus :</p>
                    <p style='color:#742a2a;font-size:14px;margin:0'>{$this->commentaire}</p>
                </div>
            ";
        }

        $ctaHtml = $isValide
            ? "<a href='http://localhost:5173' style='display:inline-block;margin-top:24px;padding:12px 28px;background:#1DBF73;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;font-size:15px'>Publier une annonce</a>"
            : "<a href='http://localhost:5173/kyc' style='display:inline-block;margin-top:24px;padding:12px 28px;background:#e53e3e;color:#fff;text-decoration:none;border-radius:8px;font-weight:700;font-size:15px'>Soumettre un nouveau dossier</a>";

        return $this->subject($subject)->html("
            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#f9f9f9;border-radius:12px'>

                <div style='display:flex;align-items:center;gap:10px;margin-bottom:24px'>
                    <span style='font-size:28px;font-weight:900;color:{$couleur}'>DocSpace</span>
                </div>

                <div style='background:#fff;border-radius:10px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,0.06)'>
                    <p style='font-size:22px;font-weight:800;color:#111;margin:0 0 8px'>{$icon} {$titre}</p>
                    <p style='color:#555;font-size:15px;margin:0 0 4px'>Bonjour <strong>{$this->nomVendeur}</strong>,</p>
                    <p style='color:#555;font-size:15px;margin:8px 0'>{$message}</p>

                    {$commentaireHtml}

                    {$ctaHtml}
                </div>

                <p style='color:#aaa;font-size:12px;margin-top:24px;text-align:center'>
                    © DocSpace — Plateforme d'équipements médicaux<br>
                    Cet email est envoyé automatiquement, merci de ne pas y répondre.
                </p>
            </div>
        ");
    }
}