<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Réinitialisation de mot de passe</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f5f5; padding:32px; color:#0D0D0D;">
    <table role="presentation" style="max-width:480px; margin:0 auto; background:#ffffff; padding:32px;">
        <tr>
            <td>
                <h1 style="font-size:20px; margin:0 0 16px;">{{ config('app.name') }}</h1>
                <p style="margin:0 0 16px;">Bonjour {{ $prenom }},</p>
                <p style="margin:0 0 16px;">
                    Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le
                    bouton ci-dessous pour en choisir un nouveau. Ce lien expire dans 60 minutes.
                </p>
                <p style="margin:0 0 24px;">
                    <a href="{{ $lienReinitialisation }}"
                       style="display:inline-block; background:#E63946; color:#ffffff; text-decoration:none; padding:12px 24px; font-weight:bold;">
                        Réinitialiser mon mot de passe
                    </a>
                </p>
                <p style="margin:0; font-size:13px; color:#888;">
                    Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email -
                    votre mot de passe restera inchangé.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
