<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 32px 16px; color: #374151; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        /* Header */
        .header { background: linear-gradient(135deg, #1DBF73, #09B1BA); padding: 32px 32px 28px; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
        .logo { color: #fff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .logo-sub { color: rgba(255,255,255,0.75); font-size: 11px; margin-top: 2px; }
        .facture-badge { background: rgba(255,255,255,0.2); color: #fff; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; }
        .facture-num { color: #fff; font-size: 30px; font-weight: 800; margin-bottom: 6px; }
        .facture-date { color: rgba(255,255,255,0.8); font-size: 13px; }

        /* Body */
        .body { padding: 32px; }

        /* Section */
        .section { margin-bottom: 28px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid #f3f4f6; }

        /* Statut */
        .statut-badge { display: inline-flex; align-items: center; gap: 8px; background: #d1fae5; color: #065f46; padding: 10px 18px; border-radius: 999px; font-size: 13px; font-weight: 700; margin-bottom: 28px; }
        .statut-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; flex-shrink: 0; }

        /* Parties grid */
        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
        .partie-card { background: #f9fafb; border-radius: 12px; padding: 16px; border: 1px solid #f3f4f6; }
        .partie-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 10px; }
        .partie-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 4px; line-height: 1.3; }
        .partie-email { font-size: 12px; color: #6b7280; margin-top: 3px; word-break: break-all; }

        /* Info commande */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 28px; }
        .info-item { background: #f9fafb; border-radius: 10px; padding: 14px; border: 1px solid #f3f4f6; }
        .info-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 6px; }
        .info-value { font-size: 13px; font-weight: 600; color: #374151; line-height: 1.4; }

        /* Table produit */
        .table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .table thead tr { background: #f9fafb; }
        .table th { padding: 12px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; border-bottom: 2px solid #f3f4f6; }
        .table td { padding: 16px 14px; font-size: 14px; color: #374151; border-bottom: 1px solid #f9fafb; vertical-align: top; }
        .table tbody tr:last-child td { border-bottom: none; }
        .product-name { font-weight: 600; color: #111827; line-height: 1.4; margin-bottom: 4px; }
        .product-cat { font-size: 11px; color: #9ca3af; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Totaux */
        .totaux { background: #f9fafb; border-radius: 12px; padding: 20px; border: 1px solid #f3f4f6; margin-bottom: 28px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 14px; gap: 16px; }
        .total-row + .total-row { border-top: 1px dashed #e5e7eb; }
        .total-final-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 0 4px; margin-top: 8px; border-top: 2px solid #e5e7eb; gap: 16px; }
        .total-label { color: #6b7280; flex-shrink: 0; }
        .total-value { font-weight: 600; color: #374151; text-align: right; }
        .total-final-label { font-size: 16px; font-weight: 800; color: #111827; flex-shrink: 0; }
        .total-final-value { font-size: 22px; font-weight: 800; color: #1DBF73; text-align: right; }
        .protection-label { display: flex; align-items: center; gap: 8px; color: #09B1BA; flex-shrink: 0; }
        .protection-badge { background: #e0f7f8; color: #09B1BA; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .protection-value { color: #09B1BA; font-weight: 600; text-align: right; }

        /* CTA */
        .cta-section { text-align: center; margin: 28px 0; }
        .cta { display: inline-block; padding: 14px 36px; background: linear-gradient(135deg, #1DBF73, #09B1BA); color: #fff; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 15px; letter-spacing: 0.3px; }

        /* Message bas */
        .message-bottom { font-size: 13px; color: #6b7280; line-height: 1.8; text-align: center; padding: 0 8px; }
        .message-bottom a { color: #1DBF73; text-decoration: none; }

        /* Footer */
        .footer { background: #f9fafb; padding: 20px 32px; border-top: 1px solid #f3f4f6; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.6; text-align: center; }
        .footer a { color: #1DBF73; text-decoration: none; }

        .divider { border: none; border-top: 1px solid #f3f4f6; margin: 24px 0; }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <div class="header">
        <div class="header-top">
            <div>
                <div class="logo">DocSpace</div>
                <div class="logo-sub">Marketplace équipements médicaux</div>
            </div>
            <div class="facture-badge">Facture</div>
        </div>
        <div class="facture-num">#{{ strtoupper(substr($commande->id, 0, 8)) }}</div>
        <div class="facture-date">Émise le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>

    <div class="body">

        {{-- Statut paiement --}}
        <div class="statut-badge">
            <span class="statut-dot"></span>
            Paiement confirmé
        </div>

        {{-- Parties --}}
        <div class="section">
            <div class="section-title">Parties impliquées</div>
            <div class="parties">
                <div class="partie-card">
                    <div class="partie-label">Acheteur</div>
                    <div class="partie-name">{{ $commande->acheteur->nom }}</div>
                    <div class="partie-email">{{ $commande->acheteur->email }}</div>
                    @if($commande->acheteur->telephone)
                    <div class="partie-email">{{ $commande->acheteur->telephone }}</div>
                    @endif
                </div>
                <div class="partie-card">
                    <div class="partie-label">Vendeur</div>
                    <div class="partie-name">{{ $commande->vendeur->nom }}</div>
                    <div class="partie-email">{{ $commande->vendeur->email }}</div>
                </div>
            </div>
        </div>

        {{-- Infos commande --}}
        <div class="section">
            <div class="section-title">Informations de la commande</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Référence</div>
                    <div class="info-value">#{{ strtoupper(substr($commande->id, 0, 8)) }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date de commande</div>
                    <div class="info-value">{{ $commande->created_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Méthode de paiement</div>
                    <div class="info-value">FedaPay — Mobile Money</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Statut</div>
                    <div class="info-value" style="color: #10b981;">✓ Payée</div>
                </div>
            </div>
        </div>

        {{-- Produit --}}
        <div class="section">
            <div class="section-title">Détail de la commande</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Équipement</th>
                        <th class="text-center">Qté</th>
                        <th class="text-right">Prix unit.</th>
                        <th class="text-right">Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="product-name">{{ $commande->annonce->titre ?? 'Équipement médical' }}</div>
                            @if($commande->annonce->categorie ?? false)
                            <div class="product-cat">{{ $commande->annonce->categorie }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $commande->quantite }}</td>
                        <td class="text-right">
                            @php $prixUnit = round($commande->montant / 1.08 / $commande->quantite); @endphp
                            {{ number_format($prixUnit, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="text-right">
                            {{ number_format($prixUnit * $commande->quantite, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Totaux --}}
        <div class="totaux">
            @php
                $sousTotal  = round($commande->montant / 1.08);
                $protection = $commande->montant - $sousTotal;
            @endphp
            <div class="total-row">
                <span class="total-label">Sous-total HT</span>
                <span class="total-value">{{ number_format($sousTotal, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="total-row">
                <span class="protection-label">
                    🛡️ Protection acheteur
                    <span class="protection-badge">+8%</span>
                </span>
                <span class="protection-value">+ {{ number_format($protection, 0, ',', ' ') }} FCFA</span>
            </div>
            {{-- ✅ Total payé séparé avec plus d'espace --}}
            <div class="total-final-row">
                <span class="total-final-label">Total payé</span>
                <span class="total-final-value">{{ number_format($commande->montant, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta-section">
            <a class="cta" href="{{ config('app.frontend_url') }}/commandes/{{ $commande->id }}">
                Suivre ma commande →
            </a>
        </div>

        <hr class="divider">

        <p class="message-bottom">
            Cette facture confirme votre paiement sur DocSpace.<br>
            Pour toute question, contactez-nous à
            <a href="mailto:docspaceafrica@gmail.com">docspaceafrica@gmail.com</a>
        </p>

    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>© {{ date('Y') }} DocSpace · Marketplace équipements médicaux · Bénin</p>
        <p style="margin-top: 6px;">
            <a href="{{ config('app.frontend_url') }}">docspace.bj</a> ·
            <a href="{{ config('app.frontend_url') }}/contact">Support</a> ·
            <a href="{{ config('app.frontend_url') }}/privacy">Confidentialité</a>
        </p>
    </div>

</div>
</body>
</html>