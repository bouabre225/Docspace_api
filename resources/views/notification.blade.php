<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1a73e8; padding: 24px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 22px; }
        .body { padding: 32px; color: #333; }
        .body p { font-size: 15px; line-height: 1.6; }
        .cta { display: inline-block; margin-top: 24px; padding: 12px 28px;
               background: #1a73e8; color: #fff; border-radius: 6px; text-decoration: none; }
        .footer { background: #f0f0f0; padding: 16px; text-align: center; font-size: 12px; color: #999; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>DocSpace</h1>
    </div>
    <div class="body">
        <p>Bonjour {{ $user->name ?? $user->email }},</p>
        <p>{{ $contenu }}</p>

        @if($reference && $reference_id)
        <a class="cta" href="{{ config('app.frontend_url') }}/{{ $reference }}/{{ $reference_id }}">
            Voir les détails
        </a>
        @endif
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} DocSpace · Marketplace équipements médicaux</p>
        <p>Vous recevez cet email car vous avez un compte DocSpace.</p>
    </div>
</div>
</body>
</html>