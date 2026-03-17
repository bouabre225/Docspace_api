<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Réinitialisation mot de passe — DocSpace</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: #f3f4f6;
      color: #1f2937;
    }
    .wrapper {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0,0,0,0.08);
    }

    /* Header gradient */
    .header {
      background: linear-gradient(135deg, #1DBF73 0%, #09B1BA 100%);
      padding: 40px 32px;
      text-align: center;
    }
    .header img {
      height: 48px;
      margin-bottom: 16px;
    }
    .header-icon {
      width: 64px;
      height: 64px;
      background: rgba(255,255,255,0.2);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto;
    }
    .header-icon svg {
      width: 32px;
      height: 32px;
      stroke: white;
      fill: none;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    /* Body */
    .body {
      padding: 40px 32px;
    }
    .greeting {
      font-size: 22px;
      font-weight: 700;
      color: #111827;
      margin-bottom: 12px;
    }
    .text {
      font-size: 15px;
      color: #6b7280;
      line-height: 1.7;
      margin-bottom: 16px;
    }

    /* Button */
    .btn-wrapper {
      text-align: center;
      margin: 32px 0;
    }
    .btn {
      display: inline-block;
      padding: 16px 40px;
      background: linear-gradient(135deg, #1DBF73 0%, #09B1BA 100%);
      color: #ffffff !important;
      text-decoration: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 0.3px;
    }

    /* Info box */
    .info-box {
      background: linear-gradient(135deg, rgba(29,191,115,0.08), rgba(9,177,186,0.08));
      border: 1px solid rgba(29,191,115,0.2);
      border-radius: 12px;
      padding: 16px 20px;
      margin: 24px 0;
    }
    .info-box p {
      font-size: 13px;
      color: #374151;
      line-height: 1.6;
    }
    .info-box strong {
      color: #1DBF73;
    }

    /* URL fallback */
    .url-fallback {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 12px 16px;
      margin: 16px 0;
      word-break: break-all;
    }
    .url-fallback p {
      font-size: 12px;
      color: #9ca3af;
      margin-bottom: 6px;
    }
    .url-fallback a {
      font-size: 12px;
      color: #1DBF73;
      text-decoration: none;
    }

    /* Divider */
    .divider {
      height: 1px;
      background: #f3f4f6;
      margin: 28px 0;
    }

    /* Footer */
    .footer {
      background: #f9fafb;
      border-top: 1px solid #f3f4f6;
      padding: 24px 32px;
      text-align: center;
    }
    .footer p {
      font-size: 12px;
      color: #9ca3af;
      line-height: 1.7;
    }
    .footer a {
      color: #1DBF73;
      text-decoration: none;
    }
    .footer .brand {
      font-size: 14px;
      font-weight: 700;
      color: #1DBF73;
      margin-bottom: 8px;
    }

    /* Responsive */
    @media (max-width: 600px) {
      .wrapper { margin: 0; border-radius: 0; }
      .body, .footer { padding: 28px 20px; }
      .header { padding: 28px 20px; }
    }
  </style>
</head>
<body>
  <div class="wrapper">

    <!-- Header -->
    <div class="header">
      <div class="header-icon">
        <svg viewBox="0 0 24 24">
          <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
      </div>
    </div>

    <!-- Body -->
    <div class="body">
      <p class="greeting">Bonjour {{ $user->nom }} 👋</p>

      <p class="text">
        Vous avez demandé la réinitialisation de votre mot de passe pour votre compte DocSpace.
        Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
      </p>

      <!-- CTA Button -->
      <div class="btn-wrapper">
        <a href="{{ $url }}" class="btn">
          🔐 &nbsp; Réinitialiser mon mot de passe
        </a>
      </div>

      <!-- Info box -->
      <div class="info-box">
        <p>
          ⏱️ <strong>Ce lien expire dans 60 minutes.</strong><br/>
          Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email — votre mot de passe restera inchangé.
        </p>
      </div>

      <!-- URL fallback -->
      <div class="url-fallback">
        <p>Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :</p>
        <a href="{{ $url }}">{{ $url }}</a>
      </div>

      <div class="divider"></div>

      <p class="text" style="font-size: 13px; color: #9ca3af;">
        Pour votre sécurité, ne partagez jamais ce lien avec quelqu'un d'autre.
        L'équipe DocSpace ne vous demandera jamais votre mot de passe.
      </p>
    </div>

    <!-- Footer -->
    <div class="footer">
      <p class="brand">DocSpace</p>
      <p>
        Marketplace d'équipements médicaux certifiés<br/>
        Cotonou, Bénin &nbsp;•&nbsp;
        <a href="mailto:docspaceafrica@gmail.com">docspaceafrica@gmail.com</a>
      </p>
      <p style="margin-top: 12px; font-size: 11px;">
        © {{ date('Y') }} DocSpace. Tous droits réservés.
      </p>
    </div>

  </div>
</body>
</html>