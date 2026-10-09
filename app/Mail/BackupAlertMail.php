<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BackupAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $erreurs, public string $dateRun) {}

    public function build()
    {
        $items = '';
        foreach ($this->erreurs as $e) {
            $items .= "<li style='font-size:14px;margin-bottom:6px'>" . e($e) . '</li>';
        }

        return $this->subject('[DocSpace] Échec sauvegarde hebdomadaire')
            ->html("
            <div style='font-family:sans-serif;max-width:520px;margin:auto;padding:32px;background:#f9f9f9;border-radius:12px'>
                <p style='font-size:22px;font-weight:900;color:#c53030'>Échec de la sauvegarde</p>
                <p style='font-size:14px'>Run du {$this->dateRun} :</p>
                <ul>{$items}</ul>
                <p style='font-size:13px;color:#666'>Vérifier : <span style='font-family:monospace'>tail /var/log/docspace-backup.log</span> puis <span style='font-family:monospace'>rclone lsd gdrive:DocSpace_Backups</span>. Si le token est expiré : <span style='font-family:monospace'>rclone config reconnect gdrive:</span></p>
            </div>");
    }
}
