<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function build()
    {
        return $this->subject('Votre code de connexion DocSpace')
            ->html("
                <div style='font-family:sans-serif;max-width:480px;margin:auto;padding:32px;background:#f9f9f9;border-radius:12px'>
                    <h2 style='color:#1DBF73;margin-bottom:8px'>DocSpace Admin</h2>
                    <p style='color:#555'>Voici votre code de connexion :</p>
                    <div style='font-size:36px;font-weight:900;letter-spacing:12px;color:#111;background:#fff;padding:20px;border-radius:8px;text-align:center;margin:24px 0'>
                        {$this->code}
                    </div>
                    <p style='color:#999;font-size:13px'>Ce code expire dans <strong>10 minutes</strong>. Ne le partagez pas.</p>
                </div>
            ");
    }
}