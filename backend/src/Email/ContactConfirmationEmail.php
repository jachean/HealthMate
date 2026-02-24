<?php

namespace App\Email;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class ContactConfirmationEmail
{
    private const LOGO_PATH = __DIR__ . '/../../public/images/logo.png';

    public static function create(
        string $toEmail,
        string $toName,
        string $subject,
        string $message,
        string $fromEmail,
        string $fromName,
    ): Email {
        $safeName    = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $logoPart = (new DataPart(fopen(self::LOGO_PATH, 'rb'), 'logo', 'image/png'))->asInline();
        $cid      = $logoPart->getContentId();

        return (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($toEmail, $toName))
            ->subject('We received your message — HealthMate Support')
            ->html(self::buildHtml($safeName, $safeSubject, $safeMessage, $cid))
            ->addPart($logoPart);
    }

    private static function buildHtml(
        string $name,
        string $subject,
        string $message,
        string $cid,
    ): string {
        $subjectBlock = $subject
            ? "<tr>
                 <td style=\"padding:10px 18px;background:#F8FAFF;border-radius:10px 10px 0 0;
                              border-left:4px solid #0072ff;\">
                   <span style=\"font-size:11px;font-weight:700;color:#9AA5B4;
                                 text-transform:uppercase;letter-spacing:0.6px;\">Subject</span><br/>
                   <span style=\"font-size:14px;font-weight:600;color:#1A2138;\">{$subject}</span>
                 </td>
               </tr>
               <tr><td style=\"height:2px;\"></td></tr>"
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>We received your message — HealthMate</title>
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

              <!-- Greeting -->
              <h1 style="margin:0 0 6px;font-size:24px;font-weight:800;color:#1A2138;
                          letter-spacing:-0.3px;">
                Thank you, {$name}! 🙏
              </h1>
              <p style="margin:0 0 28px;font-size:15px;color:#6B7A99;line-height:1.65;">
                We've received your message and our team will get back to you
                within <strong style="color:#1A2138;">24 hours</strong>.
                In the meantime, here's a copy of what you sent us.
              </p>

              <!-- Message recap -->
              <p style="margin:0 0 12px;font-size:11px;font-weight:700;color:#9AA5B4;
                          text-transform:uppercase;letter-spacing:0.7px;">
                Your message
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:32px;">
                {$subjectBlock}
                <tr>
                  <td style="padding:14px 18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <span style="font-size:11px;font-weight:700;color:#9AA5B4;
                                 text-transform:uppercase;letter-spacing:0.6px;">Message</span><br/>
                    <span style="font-size:14px;color:#1A2138;line-height:1.6;">{$message}</span>
                  </td>
                </tr>
              </table>

              <!-- Divider -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:28px;">
                <tr>
                  <td style="border-top:1px solid #E8EEF7;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
              </table>

              <p style="margin:0;font-size:14px;color:#6B7A99;line-height:1.65;">
                If you have anything to add, simply reply to this email.<br/>
                We look forward to helping you.
              </p>

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
                <a href="mailto:j234mediplant@gmail.com"
                   style="color:#0072ff;text-decoration:none;">
                  Contact support
                </a>
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
