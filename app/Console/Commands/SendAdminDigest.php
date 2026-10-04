<?php

namespace App\Console\Commands;

use App\Mail\AdminDigestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendAdminDigest extends Command
{
    protected $signature = 'admin:digest
        {--date= : Date de référence (Y-m-d), défaut hier -> aujourd\'hui}
        {--to= : Destinataire de test (défaut MAIL_ADMIN_ADDRESS)}
        {--dry-run : Affiche le récap sans envoyer}';

    protected $description = 'Envoie le récap quotidien admin (commandes, litiges, KYC, users)';

    public function handle(): int
    {
        $ref = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::yesterday()->startOfDay();
        $fin = (clone $ref)->addDay();

        $commandes = DB::table('commandes as c')
            ->leftJoin('users as a', 'a.id', '=', 'c.acheteur_id')
            ->leftJoin('users as v', 'v.id', '=', 'c.vendeur_id')
            ->select('c.id', 'c.montant', 'c.statut', 'c.created_at', 'a.email as acheteur', 'v.email as vendeur')
            ->whereBetween('c.created_at', [$ref, $fin])
            ->orderBy('c.created_at')
            ->get();

        $data = [
            'ca' => (float) $commandes->whereIn('statut', ['payee', 'livree', 'cloturee'])->sum('montant'),
            'commandes' => $commandes->count(),
            'payees' => $commandes->whereIn('statut', ['payee', 'livree', 'cloturee'])->count(),
            'litiges' => DB::table('litiges')->whereBetween('date_signalement', [$ref, $fin])->count(),
            'commandes_list' => $commandes->take(50)->map(fn($c) => [
                'id' => $c->id, 'montant' => $c->montant, 'statut' => $c->statut,
                'acheteur' => $c->acheteur, 'vendeur' => $c->vendeur,
                'date' => Carbon::parse($c->created_at)->format('d/m H:i'),
            ])->all(),
            'litiges_list' => DB::table('litiges')->whereBetween('date_signalement', [$ref, $fin])
                ->orderBy('date_signalement')->limit(20)
                ->get(['id', 'commande_id', 'motif', 'statut', 'date_signalement'])
                ->map(fn($l) => [
                    'id' => $l->id, 'commande_id' => $l->commande_id,
                    'motif' => $l->motif, 'statut' => $l->statut,
                    'date' => Carbon::parse($l->date_signalement)->format('d/m H:i'),
                ])->all(),
            'kyc_soumis' => DB::table('kyc_documents')->whereBetween('created_at', [$ref, $fin])->count(),
            'kyc_attente' => DB::table('kyc_documents')->where('statut', 'en_attente')->count(),
            'nouveaux_users' => DB::table('users')->whereBetween('created_at', [$ref, $fin])->count(),
            'nouveaux_vendeurs' => DB::table('users')->where('role', 'vendeur')->whereBetween('created_at', [$ref, $fin])->count(),
            'nouvelles_annonces' => DB::table('annonces')->whereBetween('created_at', [$ref, $fin])->count(),
            'paiements_echoues' => DB::table('paiements')->whereIn('statut', ['annule', 'echoue'])->whereBetween('created_at', [$ref, $fin])->count(),
            'avis' => DB::table('avis')->whereBetween('created_at', [$ref, $fin])->count(),
        ];

        $periode = 'du ' . $ref->format('d/m/Y');

        if ($this->option('dry-run')) {
            $this->info("Période : $periode");
            $this->table(['Métrique', 'Valeur'], collect($data)->except(['commandes_list', 'litiges_list'])->map(fn($v, $k) => [$k, $v])->all());
            $this->info('Dry-run : mail non envoyé.');
            return self::SUCCESS;
        }

        $to = $this->option('to') ?: config('mail.admin_address', config('mail.from.address'));
        Mail::to($to)->queue(new AdminDigestMail($data, $periode));
        $this->info("Digest mis en file pour $to ($periode).");

        return self::SUCCESS;
    }
}
