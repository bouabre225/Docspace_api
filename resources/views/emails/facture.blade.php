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
        .header { background: linear-gradient(135deg, #1DBF73, #09B1BA); padding: 32px 32px 24px; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .logo { color: #fff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .logo-sub { color: rgba(255,255,255,0.75); font-size: 11px; margin-top: 2px; }
        .facture-badge { background: rgba(255,255,255,0.2); color: #fff; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .facture-num { color: #fff; font-size: 28px; font-weight: 800; margin-bottom: 4px; }
        .facture-date { color: rgba(255,255,255,0.8); font-size: 13px; }

        /* Body */
        .body { padding: 32px; }

        /* Section */
        .section { margin-bottom: 24px; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f3f4f6; }

        /* Parties grid */
        .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
        .partie-card { background: #f9fafb; border-radius: 12px; padding: 16px; }
        .partie-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 8px; }
        .partie-name { font-size: 15px; font-weight: 700; color: #111827; margin-bottom: 3px; }
        .partie-email { font-size: 12px; color: #6b7280; }

        /* Table produit */
        .table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .table thead tr { background: #f9fafb; }
        .table th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; }
        .table td { padding: 14px 12px; font-size: 14px; color: #374151; border-bottom: 1px solid #f3f4f6; }
        .table .product-name { font-weight: 600; color: #111827; }
        .table .product-cat { font-size: 11px; color: #9ca3af; margin-top: 2px; }
        .text-right { text-align: right; }

        /* Totaux */
        .totaux { background: #f9fafb; border-radius: 12px; padding: 16px; }
        .total-row { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; font-size: 14px; }
        .total-row.border-top { border-top: 1px solid #e5e7eb; margin-top: 8px; padding-top: 12px; }
        .total-label { color: #6b7280; }
        .total-value { font-weight: 600; color: #374151; }
        .total-final { font-size: 18px; font-weight: 800; color: #1DBF73; }
        .protection-label { display: flex; align-items: center; gap: 6px; color: #09B1BA; }
        .protection-badge { background: #e0f7f8; color: #09B1BA; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 700; }
        .protection-value { color: #09B1BA; font-weight: 600; }

        /* Statut */
        .statut-badge { display: inline-flex; align-items: center; gap: 6px; background: #d1fae5; color: #065f46; padding: 8px 16px; border-radius: 999px; font-size: 13px; font-weight: 700; margin-bottom: 24px; }
        .statut-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; }

        /* Info commande */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .info-item { background: #f9fafb; border-radius: 10px; padding: 12px; }
        .info-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 4px; }
        .info-value { font-size: 13px; font-weight: 600; color: #374151; }

        /* CTA */
        .cta-section { text-align: center; margin: 28px 0; }
        .cta { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #1DBF73, #09B1BA); color: #fff; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 15px; }

        /* Footer */
        .footer { background: #f9fafb; padding: 20px 32px; border-top: 1px solid #f3f4f6; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.6; text-align: center; }
        .footer a { color: #1DBF73; text-decoration: none; }

        .divider { border: none; border-top: 1px solid #f3f4f6; margin: 20px 0; }
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
        <div class="parties">
            <div class="partie-card">
                <div class="partie-label">Acheteur</div>
                <div class="partie-name">{{ $commande->acheteur->nom }}</div>
                <div class="partie-email">{{ $commande->acheteur->email }}</div>
                @if($commande->acheteur->telephone)
                <div class="partie-email" style="margin-top:3px;">{{ $commande->acheteur->telephone }}</div>
                @endif
            </div>
            <div class="partie-card">
                <div class="partie-label">Vendeur</div>
                <div class="partie-name">{{ $commande->vendeur->nom }}</div>
                <div class="partie-email">{{ $commande->vendeur->email }}</div>
            </div>
        </div>

        {{-- Infos commande --}}
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Référence commande</div>
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

        {{-- Produit --}}
        <div class="section">
            <div class="section-title">Détail de la commande</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Équipement</th>
                        <th class="text-right">Qté</th>
                        <th class="text-right">Prix unit.</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="product-name">{{ $commande->annonce->titre ?? 'Équipement médical' }}</div>
                            @if($commande->annonce->categorie)
                            <div class="product-cat">{{ $commande->annonce->categorie }}</div>
                            @endif
                        </td>
                        <td class="text-right">{{ $commande->quantite }}</td>
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
                <span class="total-label">Sous-total</span>
                <span class="total-value">{{ number_format($sousTotal, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="total-row">
                <span class="protection-label">
                    🛡️ Protection acheteur
                    <span class="protection-badge">8%</span>
                </span>
                <span class="protection-value">+ {{ number_format($protection, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="total-row border-top">
                <span style="font-weight: 700; color: #111827; font-size: 15px;">Total payé</span>
                <span class="total-final">{{ number_format($commande->montant, 0, ',', ' ') }} FCFA</span>
            </div>
        </div>

        {{-- CTA --}}
        <div class="cta-section">
            <a class="cta" href="{{ config('app.frontend_url') }}/commandes/{{ $commande->id }}">
                Suivre ma commande →
            </a>
        </div>

        <hr class="divider">

        <p style="font-size: 13px; color: #6b7280; line-height: 1.7; text-align: center;">
            Cette facture confirme votre paiement sur DocSpace.<br>
            Pour toute question, contactez-nous à <a href="mailto:docspaceafrica@gmail.com" style="color: #1DBF73;">docspaceafrica@gmail.com</a>
        </p>

    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>© {{ date('Y') }} DocSpace · Marketplace équipements médicaux d'occasion · Bénin</p>
        <p style="margin-top: 4px;">
            <a href="{{ config('app.frontend_url') }}">docspace.bj</a> ·
            <a href="{{ config('app.frontend_url') }}/contact">Support</a> ·
            <a href="{{ config('app.frontend_url') }}/privacy">Confidentialité</a>
        </p>
    </div>

</div>
</body>
</html>