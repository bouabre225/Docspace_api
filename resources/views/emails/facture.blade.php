<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Reset & Base */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: #f4f7f9; padding: 20px 10px; color: #374151; -webkit-font-smoothing: antialiased; }
        .container { max-width: 650px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

        /* Header */
        .header { background: linear-gradient(135deg, #1DBF73, #09B1BA); padding: 40px 35px; color: #ffffff; }
        .header-table { width: 100%; border-collapse: collapse; }
        .logo { font-size: 28px; font-weight: 800; letter-spacing: -1px; }
        .logo-sub { font-size: 12px; opacity: 0.85; margin-top: 4px; font-weight: 500; }
        .facture-badge { background: rgba(255,255,255,0.25); color: #ffffff; padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
        .facture-num { font-size: 32px; font-weight: 900; margin-top: 25px; }
        .facture-date { font-size: 14px; opacity: 0.9; margin-top: 5px; }

        /* Body & Sections */
        .content { padding: 35px; }
        .section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; margin-bottom: 15px; border-bottom: 2px solid #f3f4f6; padding-bottom: 8px; }
        
        /* Badges */
        .statut-badge { display: inline-block; background: #d1fae5; color: #065f46; padding: 8px 16px; border-radius: 50px; font-size: 13px; font-weight: 700; margin-bottom: 30px; }

        /* Layout Tables (Remplacement Grids pour Email Compatibility) */
        .layout-table { width: 100%; border-collapse: separate; border-spacing: 12px 0; margin: 0 -12px 25px; }
        .layout-td { width: 50%; vertical-align: top; }
        
        .card { background: #f9fafb; border: 1px solid #f1f5f9; border-radius: 14px; padding: 18px; }
        .label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #9ca3af; margin-bottom: 8px; }
        .value-bold { font-size: 15px; font-weight: 700; color: #111827; }
        .value-text { font-size: 13px; color: #6b7280; margin-top: 4px; word-break: break-all; }

        /* Product Table */
        .product-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .product-table th { text-align: left; font-size: 11px; color: #9ca3af; text-transform: uppercase; padding: 12px; border-bottom: 2px solid #f3f4f6; }
        .product-table td { padding: 18px 12px; font-size: 14px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .product-name { font-weight: 700; color: #111827; }
        
        /* Totaux */
        .totaux-box { background: #f9fafb; border-radius: 16px; padding: 25px; margin-top: 20px; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
        .total-final { display: flex; justify-content: space-between; margin-top: 15px; padding-top: 15px; border-top: 2px solid #e5e7eb; }
        .total-final-label { font-size: 18px; font-weight: 800; color: #111827; }
        .total-final-price { font-size: 24px; font-weight: 800; color: #1DBF73; }

        /* Footer & CTA */
        .cta-container { text-align: center; margin: 40px 0; }
        .btn { background: linear-gradient(135deg, #1DBF73, #09B1BA); color: #ffffff !important; padding: 16px 40px; border-radius: 14px; text-decoration: none; font-weight: 700; font-size: 16px; display: inline-block; box-shadow: 0 5px 15px rgba(29, 191, 115, 0.2); }
        .footer { background: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #f3f4f6; }
        .footer-text { font-size: 12px; color: #9ca3af; line-height: 1.6; }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .layout-td { display: block; width: 100% !important; margin-bottom: 12px; }
            .header { padding: 30px 20px; }
            .content { padding: 20px; }
            .facture-num { font-size: 26px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td>
                        <div class="logo">DocSpace</div>
                        <div class="logo-sub">Marketplace équipements médicaux</div>
                    </td>
                    <td style="text-align: right; vertical-align: top;">
                        <span class="facture-badge">Facture</span>
                    </td>
                </tr>
            </table>
            <div class="facture-num">#{{ strtoupper(substr($commande->id, 0, 8)) }}</div>
            <div class="facture-date">Émise le {{ now()->format('d/m/Y à H:i') }}</div>
        </div>

        <div class="content">
            <div class="statut-badge">
                <span style="display:inline-block; width:8px; height:8px; background:#10b981; border-radius:50%; margin-right:5px;"></span>
                Paiement confirmé
            </div>

            <div class="section-title">Parties impliquées</div>
            <table class="layout-table">
                <tr>
                    <td class="layout-td">
                        <div class="card">
                            <div class="label">Acheteur</div>
                            <div class="value-bold">{{ $commande->acheteur->nom }}</div>
                            <div class="value-text">{{ $commande->acheteur->email }}</div>
                            @if($commande->acheteur->telephone)
                                <div class="value-text">{{ $commande->acheteur->telephone }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="layout-td">
                        <div class="card">
                            <div class="label">Vendeur</div>
                            <div class="value-bold">{{ $commande->vendeur->nom }}</div>
                            <div class="value-text">{{ $commande->vendeur->email }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="section-title">Informations de la commande</div>
            <table class="layout-table">
                <tr>
                    <td class="layout-td">
                        <div class="card">
                            <div class="label">Référence</div>
                            <div class="value-bold">#{{ strtoupper(substr($commande->id, 0, 8)) }}</div>
                        </div>
                    </td>
                    <td class="layout-td">
                        <div class="card">
                            <div class="label">Date</div>
                            <div class="value-bold">{{ $commande->created_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="layout-td" style="padding-top:12px;">
                        <div class="card">
                            <div class="label">Paiement</div>
                            <div class="value-bold">FedaPay — Mobile Money</div>
                        </div>
                    </td>
                    <td class="layout-td" style="padding-top:12px;">
                        <div class="card">
                            <div class="label">Statut</div>
                            <div class="value-bold" style="color:#10b981;">✓ Payée</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="section-title">Détail des équipements</div>
            <table class="product-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Équipement</th>
                        <th style="text-align: center;">Qté</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="product-name">{{ $commande->annonce->titre ?? 'Équipement médical' }}</div>
                        </td>
                        <td style="text-align: center;">{{ $commande->quantite }}</td>
                        <td style="text-align: right; font-weight: 700;">
                            @php $sousTotal = round($commande->montant / 1.08); @endphp
                            {{ number_format($sousTotal, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="totaux-box">
                @php $protection = $commande->montant - $sousTotal; @endphp
                <div class="total-row">
                    <span style="color:#6b7280;">Sous-total HT</span>
                    <span style="font-weight:600;">{{ number_format($sousTotal, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="total-row">
                    <span style="color:#09B1BA; font-weight:600;">🛡️ Protection acheteur (+8%)</span>
                    <span style="color:#09B1BA; font-weight:600;">+ {{ number_format($protection, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="total-final">
                    <span class="total-final-label">Total payé</span>
                    <span class="total-final-price">{{ number_format($commande->montant, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>

            <div class="cta-container">
                <a href="{{ config('app.frontend_url') }}/commandes/{{ $commande->id }}" class="btn">
                    Suivre ma commande →
                </a>
            </div>

            <p style="text-align: center; font-size: 13px; color: #9ca3af; margin-top: 30px;">
                Besoin d'aide ? Contactez-nous sur <a href="mailto:docspaceafrica@gmail.com" style="color:#1DBF73; text-decoration:none;">docspaceafrica@gmail.com</a>
            </p>
        </div>

        <div class="footer">
            <p class="footer-text">
                © {{ date('Y') }} <strong>DocSpace</strong> · Bénin<br>
                Vous disposez de 48h après réception pour ouvrir un litige en cas de non-conformité.
            </p>
        </div>
    </div>
</body>
</html>