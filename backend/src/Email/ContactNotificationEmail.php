<?php

namespace App\Email;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class ContactNotificationEmail
{
    private const LOGO_PATH = __DIR__ . '/../../public/images/logo.png';

    public static function create(
        string $senderName,
        string $senderEmail,
        string $subject,
        string $message,
        string $inboxEmail,
        string $fromName,
    ): Email {
        $safeName    = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
        $safeEmail   = htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8');
        $safeSubject = htmlspecialchars($subject ?: '(no subject)', ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $logoPart = (new DataPart(fopen(self::LOGO_PATH, 'rb'), 'logo', 'image/png'))->asInline();
        $cid      = $logoPart->getContentId();

        return (new Email())
            ->from(new Address($inboxEmail, $fromName))
            ->to(new Address($inboxEmail, $fromName))
            ->replyTo(new Address($senderEmail, $senderName))
            ->subject("📬 New contact message" . ($subject ? ": {$subject}" : ''))
            ->html(self::buildHtml($safeName, $safeEmail, $safeSubject, $safeMessage, $cid))
            ->addPart($logoPart);
    }

    private static function buildHtml(
        string $name,
        string $email,
        string $subject,
        string $message,
        string $cid,
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>New contact message — HealthMate</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F4F8;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#F0F4F8;padding:48px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;">

          <!-- ── Logo header ───────────────────────────────────────────────── -->
          <tr>
            <td style="background:#ffffff;padding:32px 40px 24px;
                        border-radius:18px 18px 0 0;text-align:center;">
              <img src="cid:{$cid}"
                   alt="HealthMate"
                   width="200"
                   style="display:block;margin:0 auto;max-width:200px;height:auto;" />
            </td>
          </tr>

          <!-- ── Blue accent bar ───────────────────────────────────────────── -->
          <tr>
            <td style="background:linear-gradient(135deg,#00c6ff 0%,#0072ff 100%);
                        height:4px;font-size:0;line-height:0;">&nbsp;</td>
          </tr>

          <!-- ── Body ─────────────────────────────────────────────────────── -->
          <tr>
            <td style="background:#ffffff;padding:36px 40px 32px;">

              <!-- Title -->
              <h1 style="margin:0 0 6px;font-size:24px;font-weight:800;color:#1A2138;
                          letter-spacing:-0.3px;">
                📬 New contact form message
              </h1>
              <p style="margin:0 0 28px;font-size:15px;color:#6B7A99;line-height:1.65;">
                Someone submitted the HealthMate contact form.
                Hit <strong style="color:#1A2138;">Reply</strong> to respond directly to them.
              </p>

              <!-- Sender info -->
              <p style="margin:0 0 12px;font-size:11px;font-weight:700;color:#9AA5B4;
                          text-transform:uppercase;letter-spacing:0.7px;">
                Sender
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:20px;">
                <tr>
                  <td style="padding:14px 18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <table cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-size:20px;padding-right:12px;vertical-align:top;
                                    line-height:1.4;">👤</td>
                        <td>
                          <span style="font-size:15px;font-weight:700;color:#1A2138;">{$name}</span><br/>
                          <a href="mailto:{$email}"
                             style="font-size:13px;color:#0072ff;text-decoration:none;">{$email}</a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- Subject -->
              <p style="margin:0 0 12px;font-size:11px;font-weight:700;color:#9AA5B4;
                          text-transform:uppercase;letter-spacing:0.7px;">
                Subject
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:20px;">
                <tr>
                  <td style="padding:14px 18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <span style="font-size:14px;font-weight:600;color:#1A2138;">{$subject}</span>
                  </td>
                </tr>
              </table>

              <!-- Message -->
              <p style="margin:0 0 12px;font-size:11px;font-weight:700;color:#9AA5B4;
                          text-transform:uppercase;letter-spacing:0.7px;">
                Message
              </p>
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:32px;">
                <tr>
                  <td style="padding:18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <span style="font-size:14px;color:#1A2138;line-height:1.7;">{$message}</span>
                  </td>
                </tr>
              </table>

              <!-- Reply CTA -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center">
                    <a href="mailto:{$email}?subject=Re: {$subject}"
                       style="display:inline-block;
                              background:linear-gradient(135deg,#00c6ff,#0072ff);
                              color:#ffffff;text-decoration:none;font-size:15px;
                              font-weight:700;padding:15px 40px;border-radius:10px;
                              letter-spacing:0.2px;">
                      Reply to {$name} →
                    </a>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- ── Footer ───────────────────────────────────────────────────── -->
          <tr>
            <td style="background:#F8FAFF;padding:24px 40px;
                        border-radius:0 0 18px 18px;
                        border-top:1px solid #E8EEF7;">
              <p style="margin:0;font-size:12px;color:#9AA5B4;
                          text-align:center;line-height:1.7;">
                <strong style="color:#6B7A99;">HealthMate</strong> ·
                Calea București 32, Craiova, Dolj<br/>
                This is an internal notification from the HealthMate contact form.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;
    }
}
