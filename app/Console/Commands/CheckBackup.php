<?php

namespace App\Console\Commands;

use App\Mail\BackupAlertMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckBackup extends Command
{
    protected $signature = 'backup:check
        {--test : Envoie un mail de test à l\'admin}
        {--log= : Chemin du log backup}';

    protected $description = 'Vérifie le dernier run de sauvegarde et alerte l\'admin en cas d\'échec';

    public function handle(): int
    {
        $to = config('mail.admin_address', config('mail.from.address'));

        if ($this->option('test')) {
            Mail::to($to)->queue(new BackupAlertMail(['Test manuel de l\'alerte sauvegarde.'], now()->format('d/m/Y H:i')));
            $this->info("Mail de test mis en file pour $to.");
            return self::SUCCESS;
        }

        $logFile = $this->option('log') ?: '/var/log/docspace-backup.log';
        if (!is_readable($logFile)) {
            Mail::to($to)->queue(new BackupAlertMail(["Log illisible : $logFile"], now()->format('d/m/Y H:i')));
            $this->error('Log illisible, alerte envoyée.');
            return self::SUCCESS;
        }

        $content = file_get_contents($logFile);
        $blocks = preg_split('/={10,}/', $content);
        $blocks = array_values(array_filter(array_map('trim', $blocks)));
        $last = end($blocks);

        if (!$last || !str_contains($last, 'Début backup')) {
            Mail::to($to)->queue(new BackupAlertMail(['Aucun run de sauvegarde trouvé dans le log.'], now()->format('d/m/Y H:i')));
            $this->error('Aucun run trouvé, alerte envoyée.');
            return self::SUCCESS;
        }

        $erreurs = [];
        foreach (explode("\n", $last) as $line) {
            if (str_contains($line, '❌')) {
                $erreurs[] = trim(preg_replace('/^\[.*?\] /', '', $line));
            }
        }

        // Date du run (1re ligne horodatée du bloc)
        $dateRun = now()->format('d/m/Y');
        if (preg_match('/\[(\d{4}-\d{2}-\d{2}_\d{2}-\d{2})\]/', $last, $m)) {
            $dateRun = str_replace('_', ' ', $m[1]);
        }

        if (empty($erreurs)) {
            $this->info("Dernier run ($dateRun) : OK, pas d'alerte.");
            return self::SUCCESS;
        }

        Mail::to($to)->queue(new BackupAlertMail($erreurs, $dateRun));
        $this->error('Échec détecté (' . count($erreurs) . '), alerte envoyée à ' . $to . '.');

        return self::SUCCESS;
    }
}
