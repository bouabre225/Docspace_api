<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <style>
    body { font-family: sans-serif; background: #f3f4f6; color: #1f2937; }
    .wrapper { max-width: 600px; margin: 40px auto; background: white; border-radius: 16px; overflow: hidden; }
    .header { background: linear-gradient(135deg, #1DBF73, #09B1BA); padding: 32px; text-align: center; }
    .header h1 { color: white; font-size: 22px; margin: 0; }
    .body { padding: 32px; }
    .field { margin-bottom: 16px; }
    .label { font-size: 12px; color: #9ca3af; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .value { font-size: 15px; color: #111827; }
    .message-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; margin-top: 8px; white-space: pre-wrap; }
    .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header"><h1>📬 Nouveau message de contact</h1></div>
    <div class="body">
      <div class="field">
        <div class="label">Expéditeur</div>
        <div class="value">{{ $nom }} — <a href="mailto:{{ $email }}">{{ $email }}</a></div>
      </div>
      @if($categorie)
      <div class="field">
        <div class="label">Catégorie</div>
        <div class="value">{{ $categorie }}</div>
      </div>
      @endif
      @if($sujet)
      <div class="field">
        <div class="label">Sujet</div>
        <div class="value">{{ $sujet }}</div>
      </div>
      @endif
      <div class="field">
        <div class="label">Message</div>
        <div class="message-box">{{ $contenu }}</div>
      </div>
    </div>
    <div class="footer">DocSpace — {{ date('d/m/Y H:i') }}</div>
  </div>
</body>
</html>