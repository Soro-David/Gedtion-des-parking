<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation - {{ config('app.name') }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f6f8;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1a202c;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            background: linear-gradient(135deg, #487119 0%, #3d5e15 100%);
            padding: 40px 48px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 8px 0 0;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        .body {
            padding: 40px 48px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .message {
            font-size: 15px;
            color: #4a5568;
            line-height: 1.7;
            margin-bottom: 32px;
        }
        .info-box {
            background: #f8fdf3;
            border: 1px solid #c6e6a0;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 32px;
        }
        .info-box p {
            margin: 6px 0;
            font-size: 14px;
            color: #2d3748;
        }
        .info-box strong {
            color: #487119;
        }
        .btn-wrapper {
            text-align: center;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #487119 0%, #3d5e15 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .expiry {
            font-size: 13px;
            color: #718096;
            text-align: center;
            margin-bottom: 24px;
        }
        .fallback {
            font-size: 12px;
            color: #a0aec0;
            word-break: break-all;
            text-align: center;
            margin-bottom: 32px;
        }
        .footer {
            background: #f8fafc;
            border-top: 1px solid #e8edf2;
            padding: 24px 48px;
            text-align: center;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>Système de gestion de parking</p>
        </div>

        <div class="body">
            <p class="greeting">Bonjour {{ $user->name }} {{ $user->first_name }},</p>
            <p class="message">
                Vous avez été invité(e) à rejoindre la plateforme <strong>{{ config('app.name') }}</strong>
                en tant que <strong>{{ ucfirst($user->role) }}</strong>.<br><br>
                Cliquez sur le bouton ci-dessous pour définir votre mot de passe et activer votre compte.
                Ce lien est valable pendant <strong>48 heures</strong>.
            </p>

            <div class="info-box">
                <p><strong>Nom :</strong> {{ $user->name }} {{ $user->first_name }}</p>
                <p><strong>Email :</strong> {{ $user->email }}</p>
                @if($user->phone)
                <p><strong>Téléphone :</strong> {{ $user->phone }}</p>
                @endif
            </div>

            <div class="btn-wrapper">
                <a href="{{ $invitationUrl }}" class="btn">
                    Définir mon mot de passe
                </a>
            </div>

            <p class="expiry">
                ⏱ Ce lien expirera le {{ \Carbon\Carbon::parse($user->invitation_expires_at)->format('d/m/Y à H:i') }}.
            </p>

            <p class="fallback">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                {{ $invitationUrl }}
            </p>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }} — Tous droits réservés.<br>
            Si vous n'attendiez pas cette invitation, ignorez cet email.
        </div>
    </div>
</body>
</html>
