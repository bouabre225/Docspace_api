<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatsController extends Controller
{
    private const PAYES = ['payee', 'livree', 'cloturee'];

    public function index(Request $request)
    {
        $request->validate([
            'periode' => 'nullable|in:7j,30j,90j,365j,tout',
            'categorie' => 'nullable|string|max:100',
            'pays' => 'nullable|string|max:50',
        ]);

        $jours = match ($request->input('periode', '30j')) {
            '7j' => 7, '90j' => 90, '365j' => 365, 'tout' => null, default => 30,
        };
        $debut = $jours ? Carbon::now()->subDays($jours)->startOfDay() : null;

        $cmd = DB::table('commandes');
        if ($debut) $cmd->where('commandes.created_at', '>=', $debut);
        if ($request->filled('categorie')) {
            $cmd->join('annonces as a', 'a.id', '=', 'commandes.annonce_id')
                ->where('a.categorie', $request->categorie);
        }

        $usersQ = DB::table('users');
        if ($debut) $usersQ->where('created_at', '>=', $debut);

        $commandesTotal = (clone $cmd)->count();
        $payees = (clone $cmd)->whereIn('commandes.statut', self::PAYES);
        $commandesPayees = $payees->count();
        $ca = (float) (clone $payees)->sum('commandes.montant');
        $annulees = (clone $cmd)->where('commandes.statut', 'annulee')->count();
        $litigesCmd = (clone $cmd)->where('commandes.statut', 'litige')->count();

        $usersTotal = (clone $usersQ)->count();
        $vendeurs = (clone $usersQ)->where('role', 'vendeur')->count();
        $acheteurs = (clone $usersQ)->where('role', 'acheteur')->count();
        $vendeursKyc = DB::table('users')->where('role', 'vendeur')->where('verifie_kyc', true)->count();
        $vendeursTotal = DB::table('users')->where('role', 'vendeur')->count();

        $annoncesActives = DB::table('annonces')->where('statut', 'active')->count();
        $nouvellesAnnonces = DB::table('annonces')
            ->when($debut, fn($q) => $q->where('created_at', '>=', $debut))->count();
        $ruptures = DB::table('annonces')->where('statut', 'active')->where('quantite', '<=', 0)->count();
        $stockMort = DB::table('annonces')->where('annonces.statut', 'active')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('commandes')
                    ->whereColumn('commandes.annonce_id', 'annonces.id')
                    ->whereIn('commandes.statut', self::PAYES);
            })->count();

        // Série journalière CA + commandes
        $serie = DB::table('commandes')
            ->selectRaw("commandes.created_at::date as date, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupByRaw('commandes.created_at::date')
            ->orderBy('date')
            ->get();

        // Par catégorie
        $parCategorie = DB::table('commandes')
            ->join('annonces as a', 'a.id', '=', 'commandes.annonce_id')
            ->selectRaw("a.categorie, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->when($request->filled('pays'), function ($q) use ($request) {
                $q->join('users as v', 'v.id', '=', 'commandes.vendeur_id')->where('v.pays', $request->pays);
            })
            ->groupBy('a.categorie')->orderByDesc('ca')->get()
            ->map(fn($r) => [
                'categorie' => $r->categorie ?? 'Autres',
                'commandes' => (int) $r->commandes,
                'ca' => (float) $r->ca,
                'panier_moyen' => $r->commandes ? round($r->ca / $r->commandes, 2) : 0,
            ]);

        // Par état
        $parEtat = DB::table('commandes')
            ->join('annonces as a', 'a.id', '=', 'commandes.annonce_id')
            ->selectRaw("a.etat, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupBy('a.etat')->orderByDesc('ca')->get();

        // Top produits
        $topProduits = DB::table('commandes')
            ->join('annonces as a', 'a.id', '=', 'commandes.annonce_id')
            ->selectRaw("a.id, a.titre, a.categorie, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.quantite else 0 end),0) as quantites")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupBy('a.id', 'a.titre', 'a.categorie')->orderByDesc('ca')->limit(10)->get();

        // Sans vente
        $sansVente = DB::table('annonces')
            ->select('id', 'titre', 'categorie', 'prix_total', 'created_at')
            ->where('statut', 'active')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('commandes')
                    ->whereColumn('commandes.annonce_id', 'annonces.id')
                    ->whereIn('commandes.statut', self::PAYES);
            })->orderBy('created_at')->limit(10)->get();

        // Par pays (vendeur)
        $parPays = DB::table('commandes')
            ->join('users as v', 'v.id', '=', 'commandes.vendeur_id')
            ->selectRaw("v.pays, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupBy('v.pays')->orderByDesc('ca')->limit(10)->get();

        // Top vendeurs / acheteurs
        $topVendeurs = DB::table('commandes')
            ->join('users as v', 'v.id', '=', 'commandes.vendeur_id')
            ->selectRaw("v.id, v.nom, v.email, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as ca")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupBy('v.id', 'v.nom', 'v.email')->orderByDesc('ca')->limit(10)->get();
        $topAcheteurs = DB::table('commandes')
            ->join('users as a', 'a.id', '=', 'commandes.acheteur_id')
            ->selectRaw("a.id, a.nom, a.email, count(*) as commandes, coalesce(sum(case when commandes.statut in ('payee','livree','cloturee') then commandes.montant else 0 end),0) as montant")
            ->when($debut, fn($q) => $q->where('commandes.created_at', '>=', $debut))
            ->groupBy('a.id', 'a.nom', 'a.email')->orderByDesc('montant')->limit(10)->get();

        // Funnel statuts
        $funnel = DB::table('commandes')
            ->selectRaw('statut, count(*) as total')
            ->when($debut, fn($q) => $q->where('created_at', '>=', $debut))
            ->groupBy('statut')->get();

        // Litiges
        $litigesTotal = DB::table('litiges')->when($debut, fn($q) => $q->where('date_signalement', '>=', $debut))->count();
        $litigesMotifs = DB::table('litiges')
            ->selectRaw('motif, count(*) as total')
            ->when($debut, fn($q) => $q->where('date_signalement', '>=', $debut))
            ->groupBy('motif')->get();
        $delaiLitige = DB::table('litiges')
            ->when($debut, fn($q) => $q->where('date_signalement', '>=', $debut))
            ->where('statut', 'resolu')
            ->selectRaw("avg(extract(epoch from (updated_at - date_signalement))/86400) as jours")->value('jours');

        // Avis
        $avisAgg = DB::table('avis')->selectRaw('count(*) as total, avg(note_vendeur) as vendeur, avg(note_conformite) as conformite')->first();
        $avisCategories = DB::table('avis')
            ->join('commandes as c', 'c.id', '=', 'avis.commande_id')
            ->join('annonces as a', 'a.id', '=', 'c.annonce_id')
            ->selectRaw('a.categorie, count(*) as total, round(avg((note_vendeur+note_conformite)/2.0),2) as note')
            ->groupBy('a.categorie')->orderByDesc('total')->limit(8)->get();
        $commandesNotees = DB::table('avis')->distinct()->count('commande_id');

        // KYC funnel
        $kycFunnel = DB::table('kyc_documents')->selectRaw('statut, count(*) as total')->groupBy('statut')->get();

        // Paiements : délai création→paiement + échecs
        $delaiPaiement = DB::table('paiements')
            ->join('commandes as c', 'c.id', '=', 'paiements.commande_id')
            ->whereNotNull('paiements.date_paiement')
            ->when($debut, fn($q) => $q->where('c.created_at', '>=', $debut))
            ->selectRaw('avg(extract(epoch from (paiements.date_paiement - c.created_at))/3600) as heures')->value('heures');
        $paiementsEchoues = DB::table('paiements')->whereIn('statut', ['annule', 'echoue'])
            ->when($debut, fn($q) => $q->where('created_at', '>=', $debut))->count();
        $paiementsTotal = DB::table('paiements')->when($debut, fn($q) => $q->where('created_at', '>=', $debut))->count();

        return response()->json([
            'periode' => $request->input('periode', '30j'),
            'kpis' => [
                'ca_total' => round($ca, 2),
                'revenus_plateforme' => round($ca - $ca / 1.08, 2),
                'commandes_total' => $commandesTotal,
                'commandes_payees' => $commandesPayees,
                'panier_moyen' => $commandesPayees ? round($ca / $commandesPayees, 2) : 0,
                'taux_conversion' => $commandesTotal ? round($commandesPayees / $commandesTotal * 100, 1) : 0,
                'taux_annulation' => $commandesTotal ? round($annulees / $commandesTotal * 100, 1) : 0,
                'taux_litige' => $commandesTotal ? round($litigesCmd / $commandesTotal * 100, 1) : 0,
                'users_total' => $usersTotal,
                'vendeurs' => $vendeurs,
                'acheteurs' => $acheteurs,
                'kyc_valide_pct' => $vendeursTotal ? round($vendeursKyc / $vendeursTotal * 100, 1) : 0,
                'annonces_actives' => $annoncesActives,
                'nouvelles_annonces' => $nouvellesAnnonces,
                'stock_mort' => $stockMort,
                'ruptures' => $ruptures,
            ],
            'serie' => $serie,
            'par_categorie' => $parCategorie,
            'par_etat' => $parEtat,
            'top_produits' => $topProduits,
            'sans_vente' => $sansVente,
            'par_pays' => $parPays,
            'top_vendeurs' => $topVendeurs,
            'top_acheteurs' => $topAcheteurs,
            'funnel' => $funnel,
            'litiges' => [
                'total' => $litigesTotal,
                'par_motif' => $litigesMotifs,
                'delai_resolution_jours' => $delaiLitige ? round($delaiLitige, 1) : null,
            ],
            'avis' => [
                'total' => (int) ($avisAgg->total ?? 0),
                'note_vendeur' => round($avisAgg->vendeur ?? 0, 2),
                'note_conformite' => round($avisAgg->conformite ?? 0, 2),
                'commandes_notees' => $commandesNotees,
                'par_categorie' => $avisCategories,
            ],
            'kyc_funnel' => $kycFunnel,
            'paiements' => [
                'delai_moyen_heures' => $delaiPaiement ? round($delaiPaiement, 1) : null,
                'echoues' => $paiementsEchoues,
                'total' => $paiementsTotal,
            ],
        ]);
    }
}
