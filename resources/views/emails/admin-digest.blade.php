<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; background: #f4f7f9; padding: 20px 10px; color: #374151; }
        .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1DBF73, #09B1BA); padding: 30px; color: #ffffff; }
        .header h1 { font-size: 22px; }
        .header p { font-size: 13px; opacity: 0.9; margin-top: 4px; }
        .content { padding: 25px 30px; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .kpis td { background: #f9fafb; border: 1px solid #f1f5f9; border-radius: 10px; padding: 12px; text-align: center; width: 25%; }
        .kpi-v { font-size: 20px; font-weight: 800; color: #111827; }
        .kpi-l { font-size: 10px; text-transform: uppercase; color: #9ca3af; }
        h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: #1DBF73; margin: 22px 0 8px; border-bottom: 2px solid #f3f4f6; padding-bottom: 6px; }
        table.list { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.list td { padding: 8px 4px; border-bottom: 1px solid #f3f4f6; }
        .muted { color: #9ca3af; font-size: 12px; }
        .btn { display: inline-block; background: #1DBF73; color: #ffffff !important; padding: 10px 22px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; margin-top: 18px; }
        .footer { padding: 18px 30px; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Récap quotidien DocSpace</h1>
        <p>{{ $periode }} — généré le {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <div class="content">
        <table class="kpis" cellpadding="0" cellspacing="6">
            <tr>
                <td><div class="kpi-v">{{ number_format($data['ca'], 0, ',', ' ') }}</div><div class="kpi-l">CA (FCFA)</div></td>
                <td><div class="kpi-v">{{ $data['commandes'] }}</div><div class="kpi-l">Commandes</div></td>
                <td><div class="kpi-v">{{ $data['payees'] }}</div><div class="kpi-l">Payées</div></td>
                <td><div class="kpi-v">{{ $data['litiges'] }}</div><div class="kpi-l">Litiges</div></td>
            </tr>
        </table>

        <h2>Commandes ({{ count($data['commandes_list']) }})</h2>
        @forelse($data['commandes_list'] as $c)
            <table class="list"><tr>
                <td><strong>#{{ substr($c['id'], 0, 8) }}</strong> — {{ number_format($c['montant'], 0, ',', ' ') }} FCFA<br><span class="muted">{{ $c['acheteur'] }} → {{ $c['vendeur'] }} • {{ $c['statut'] }} • {{ $c['date'] }}</span></td>
            </tr></table>
        @empty
            <p class="muted">Aucune commande sur la période.</p>
        @endforelse

        <h2>Litiges ({{ count($data['litiges_list']) }})</h2>
        @forelse($data['litiges_list'] as $l)
            <table class="list"><tr>
                <td><strong>#{{ substr($l['id'], 0, 8) }}</strong> — {{ $l['motif'] }} ({{ $l['statut'] }})<br><span class="muted">Commande #{{ substr($l['commande_id'], 0, 8) }} • {{ $l['date'] }}</span></td>
            </tr></table>
        @empty
            <p class="muted">Aucun litige sur la période.</p>
        @endforelse

        <h2>KYC ({{ $data['kyc_soumis'] }} soumis)</h2>
        <p style="font-size:13px;">En attente : <strong>{{ $data['kyc_attente'] }}</strong> — <a href="https://docspace.bj/admin/kyc">Traiter le KYC</a></p>

        <h2>Utilisateurs &amp; annonces</h2>
        <p style="font-size:13px;">Nouveaux : <strong>{{ $data['nouveaux_users'] }}</strong> ({{ $data['nouveaux_vendeurs'] }} vendeurs) • Annonces : <strong>{{ $data['nouvelles_annonces'] }}</strong> • Paiements échoués : <strong>{{ $data['paiements_echoues'] }}</strong> • Avis : <strong>{{ $data['avis'] }}</strong></p>

        <a class="btn" href="https://docspace.bj/admin/stats">Voir les statistiques</a>
    </div>
    <div class="footer">Mail automatique DocSpace — ne pas répondre.</div>
</div>
</body>
</html>
