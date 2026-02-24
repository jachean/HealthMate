<?php

namespace App\Email;

use App\Entity\User;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class WelcomeEmail
{
    private const LOGO_PATH = __DIR__ . '/../../public/images/logo.png';

    public static function create(User $user, string $fromEmail, string $fromName): Email
    {
        $firstName = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
        $lastName  = htmlspecialchars($user->getLastName(), ENT_QUOTES, 'UTF-8');
        $email     = htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8');

        $logoPart = (new DataPart(fopen(self::LOGO_PATH, 'rb'), 'logo', 'image/png'))->asInline();
        $cid      = $logoPart->getContentId();

        $emailMessage = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($user->getEmail(), "$firstName $lastName"))
            ->subject('Welcome to HealthMate!')
            ->html(self::buildHtml($firstName, $lastName, $email, $cid))
            ->addPart($logoPart);

        return $emailMessage;
    }

    private static function buildHtml(string $firstName, string $lastName, string $email, string $cid): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to HealthMate</title>
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
                        border-radius:18px 18px 0 0;text-align:center;
                        border-bottom:3px solid;
                        border-image:linear-gradient(135deg,#00c6ff,#0072ff) 1;">
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

          <!-- ── Body ────────────────────────────────────────────────────── -->
          <tr>
            <td style="background:#ffffff;padding:36px 40px 32px;">

              <!-- Greeting -->
              <h1 style="margin:0 0 6px;font-size:24px;font-weight:800;color:#1A2138;
                          letter-spacing:-0.3px;">
                Welcome, {$firstName}! 👋
              </h1>
              <p style="margin:0 0 28px;font-size:15px;color:#6B7A99;line-height:1.65;">
                Your HealthMate account is ready. You can now browse doctors,
                pick a time slot, and confirm appointments in just a few clicks.
              </p>

              <!-- Account info pill -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:28px;">
                <tr>
                  <td style="background:#F0F4F8;border-radius:10px;padding:14px 18px;">
                    <span style="font-size:11px;font-weight:700;color:#9AA5B4;
                                 text-transform:uppercase;letter-spacing:0.6px;">
                      Account
                    </span><br/>
                    <span style="font-size:14px;font-weight:600;color:#1A2138;">
                      {$firstName} {$lastName}
                    </span>
                    <span style="font-size:13px;color:#9AA5B4;margin-left:8px;">
                      · {$email}
                    </span>
                  </td>
                </tr>
              </table>

              <!-- Feature rows -->
              <p style="margin:0 0 14px;font-size:11px;font-weight:700;color:#9AA5B4;
                          text-transform:uppercase;letter-spacing:0.7px;">
                What you can do
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin-bottom:32px;">
                <tr>
                  <td style="padding:14px 18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <table cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-size:20px;padding-right:12px;vertical-align:top;
                                    line-height:1.4;">🔍</td>
                        <td>
                          <strong style="font-size:14px;color:#1A2138;">Browse doctors</strong>
                          <p style="margin:3px 0 0;font-size:13px;color:#6B7A99;line-height:1.5;">
                            Filter by specialty, clinic, city, or insurance.
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr><td style="height:10px;"></td></tr>
                <tr>
                  <td style="padding:14px 18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <table cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-size:20px;padding-right:12px;vertical-align:top;
                                    line-height:1.4;">📅</td>
                        <td>
                          <strong style="font-size:14px;color:#1A2138;">Book appointments</strong>
                          <p style="margin:3px 0 0;font-size:13px;color:#6B7A99;line-height:1.5;">
                            Pick your preferred time slot and confirm instantly.
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr><td style="height:10px;"></td></tr>
                <tr>
                  <td style="padding:14px 18px;background:#F8FAFF;border-radius:10px;
                              border-left:4px solid #0072ff;">
                    <table cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="font-size:20px;padding-right:12px;vertical-align:top;
                                    line-height:1.4;">⭐</td>
                        <td>
                          <strong style="font-size:14px;color:#1A2138;">Leave reviews</strong>
                          <p style="margin:3px 0 0;font-size:13px;color:#6B7A99;line-height:1.5;">
                            Rate your experience and help other patients choose.
                          </p>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- CTA button -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center">
                    <a href="http://localhost:5173/doctors"
                       style="display:inline-block;
                              background:linear-gradient(135deg,#00c6ff,#0072ff);
                              color:#ffffff;text-decoration:none;font-size:15px;
                              font-weight:700;padding:15px 40px;border-radius:10px;
                              letter-spacing:0.2px;">
                      Browse Doctors →
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
                You received this email because you created an account on HealthMate.<br/>
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
