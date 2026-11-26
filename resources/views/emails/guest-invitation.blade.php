<?php

/**
 * @empresa Bigbitbyte
 *
 * @autor Ivan Hernandez Hinojosa
 *
 * @creado 25/noviembre/2026
 *
 * @modificado 25/noviembre/2026
 *
 * @descripción Plantilla del correo con la invitación personal y el QR del invitado.
 */

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $eventName }}</title>
</head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="padding:28px 32px;background:#2563eb;">
                            <p style="margin:0;font-size:12px;letter-spacing:.16em;text-transform:uppercase;color:#bfdbfe;font-weight:bold;">Invitación personal</p>
                            <h1 style="margin:8px 0 0;font-size:26px;color:#ffffff;">{{ $eventName }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px;font-size:16px;">Hola {{ $guestName }}:</p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#475569;">Tienes una invitación a nuestro evento. Presenta el código QR de abajo en el acceso o abre el enlace para consultar todos los detalles.</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;">
                                <tr>
                                    <td style="padding:18px 20px;font-size:14px;"><strong>Fecha:</strong> {{ $eventDate }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:0 20px 18px;font-size:14px;"><strong>Lugar:</strong> {{ $venueName }} — {{ $venueAddress }}</td>
                                </tr>
                                @if ($rules)
                                    <tr>
                                        <td style="padding:0 20px 18px;font-size:14px;line-height:1.5;"><strong>Reglamento:</strong> {{ $rules }}</td>
                                    </tr>
                                @endif
                            </table>

                            <div style="margin:28px 0;text-align:center;">
                                <img src="{{ $qrDataUrl }}" alt="Código QR de {{ $guestName }}" width="220" height="220" style="display:inline-block;border:1px solid #cbd5e1;border-radius:12px;">
                                <p style="margin:10px 0 0;font-size:13px;color:#64748b;">Escanea este QR con la app de invitados.</p>
                            </div>

                            <p style="margin:0 0 12px;text-align:center;">
                                <a href="{{ $invitationUrl }}" style="display:inline-block;padding:12px 22px;border-radius:10px;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;">Ver detalles del evento</a>
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;word-break:break-all;text-align:center;">{{ $invitationUrl }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
