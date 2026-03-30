<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f0f4f8; padding: 32px 16px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        /* Header */
        .header { background: linear-gradient(135deg, #1DBF73, #09B1BA); padding: 32px 24px; text-align: center; }
        .header img { height: 48px; margin-bottom: 8px; }
        .header h1 { color: #fff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .header p { color: rgba(255,255,255,0.85); font-size: 13px; margin-top: 4px; }

        /* Body */
        .body { padding: 36px 32px; color: #374151; }
        .greeting { font-size: 17px; font-weight: 600; color: #111827; margin-bottom: 16px; }
        .content { font-size: 15px; line-height: 1.7; color: #4B5563; margin-bottom: 16px; }
        .commentaire { background: #f9fafb; border-left: 3px solid #1DBF73; padding: 12px 16px; border-radius: 0 8px 8px 0; font-size: 14px; color: #374151; margin-bottom: 16px; }

        /* Badge type */
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; margin-bottom: 20px; }
        .badge-commande { background: #d1fae5; color: #065f46; }
        .badge-litige   { background: #fee2e2; color: #991b1b; }
        .badge-message  { background: #dbeafe; color: #1e40af; }
        .badge-systeme  { background: #ede9fe; color: #5b21b6; }

        /* CTA */
        .cta-wrapper { text-align: center; margin-top: 28px; }
        .cta {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #1DBF73, #09B1BA);
            color: #fff !important;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            letter-spacing: 0.2px;
        }

        /* Divider */
        .divider { border: none; border-top: 1px solid #f3f4f6; margin: 28px 0; }

        /* Footer */
        .footer { background: #f9fafb; padding: 20px 24px; text-align: center; border-top: 1px solid #f3f4f6; }
        .footer p { font-size: 12px; color: #9ca3af; line-height: 1.6; }
        .footer a { color: #1DBF73; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">

    {{-- Header --}}
    <div class="header">
        <h1>DocSpace</h1>
        <p>Marketplace d'équipements médicaux</p>
    </div>

    {{-- Body --}}
    <div class="body">

        {{-- Badge type --}}
        @php
            $badgeClass = match($type ?? '') {
                'commande' => 'badge-commande',
                'litige'   => 'badge-litige',
                'message'  => 'badge-message',
                default    => 'badge-systeme',
            };
            $badgeLabel = match($type ?? '') {
                'commande' => '📦 Commande',
                'litige'   => '⚠️ Litige',
                'message'  => '💬 Message',
                default    => 'ℹ️ Information',
            };
        @endphp
        <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>

        <p class="greeting">Bonjour {{ $user->nom ?? $user->email }},</p>
        <p class="content">{{ $contenu }}</p>

        @if(!empty($commentaire ?? null))
            <div class="commentaire">{{ $commentaire }}</div>
        @endif

        {{-- Instruction livraison pour le vendeur --}}
        @if(($type ?? '') === 'commande' && str_contains($contenu ?? '', 'payée'))
        <div style="margin: 20px 0; padding: 16px 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px;">
            <p style="font-size: 14px; font-weight: 700; color: #166534; margin-bottom: 8px;">
                📋 Action requise de votre part
            </p>
            <p style="font-size: 13px; color: #15803d; line-height: 1.6;">
                Une fois l'équipement remis à l'acheteur, pensez à <strong>marquer la commande comme livrée</strong>
                depuis votre profil dans la section <strong>"Commandes reçues"</strong>.
                Cela permet de valider la transaction et de rassurer l'acheteur.
            </p>
            <p style="margin-top: 10px; font-size: 13px; color: #15803d;">
                👉 Profil → Commandes reçues → Bouton <strong>"Marquer livrée"</strong>
            </p>
        </div>
        @endif

        {{-- CTA --}}
        @if(!empty($reference ?? null) && !empty($reference_id ?? null))
        @php
            $routeMap = [
                'commande' => 'commandes',
                'litige'   => 'litiges',
                'message'  => 'messages',
            ];
            $route = $routeMap[$reference] ?? $reference;
            $url = config('app.frontend_url') . '/' . $route . '/' . $reference_id;

            $ctaLabel = match($reference ?? '') {
                'commande' => 'Voir la commande →',
                'litige'   => 'Voir le litige →',
                'message'  => 'Voir le message →',
                default    => 'Voir les détails →',
            };
        @endphp
        <div class="cta-wrapper">
            <a class="cta" href="{{ $url }}">{{ $ctaLabel }}</a>
        </div>
        @endif

        <hr class="divider">
        <p style="font-size:13px; color:#9ca3af;">
            Si vous n'êtes pas à l'origine de cette action, ignorez cet email ou
            <a href="{{ config('app.frontend_url') }}/contact" style="color:#1DBF73;">contactez-nous</a>.
        </p>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>© {{ date('Y') }} DocSpace · Marketplace équipements médicaux d'occasion</p>
        <p style="margin-top:4px;">
            <a href="{{ config('app.frontend_url') }}">docspace.bj</a> ·
            <a href="{{ config('app.frontend_url') }}/contact">Support</a> ·
            <a href="{{ config('app.frontend_url') }}/privacy">Confidentialité</a>
        </p>
    </div>

</div>
</body>
</html>