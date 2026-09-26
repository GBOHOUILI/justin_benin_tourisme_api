<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titreNotif }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f5f5f5; padding:32px; color:#0D0D0D;">
    <table role="presentation" style="max-width:480px; margin:0 auto; background:#ffffff; padding:32px;">
        <tr>
            <td>
                <h1 style="font-size:20px; margin:0 0 16px;">{{ config('app.name') }}</h1>
                <h2 style="font-size:16px; margin:0 0 16px;">{{ $titreNotif }}</h2>
                <p style="margin:0 0 24px; line-height:1.6;">{{ $messageNotif }}</p>
                @if($lienComplet)
                    <p style="margin:0;">
                        <a href="{{ $lienComplet }}"
                           style="display:inline-block; background:#E63946; color:#ffffff; text-decoration:none; padding:12px 24px; font-weight:bold;">
                            Voir sur {{ config('app.name') }}
                        </a>
                    </p>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
