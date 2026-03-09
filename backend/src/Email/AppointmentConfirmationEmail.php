<?php

namespace App\Email;

use App\Entity\Appointment;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class AppointmentConfirmationEmail
{
    private const LOGO_PATH = __DIR__ . '/../../public/images/logo.png';

    public static function create(Appointment $appointment, string $fromEmail, string $fromName): Email
    {
        $user        = $appointment->getUser();
        $slot        = $appointment->getTimeSlot();
        $doctor      = $slot->getDoctor();
        $firstName   = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
        $lastName    = htmlspecialchars($user->getLastName(), ENT_QUOTES, 'UTF-8');
        $doctorName  = htmlspecialchars($doctor->getFirstName() . ' ' . $doctor->getLastName(), ENT_QUOTES, 'UTF-8');
        $clinicName  = htmlspecialchars($doctor->getClinic()->getName(), ENT_QUOTES, 'UTF-8');
        $serviceName = htmlspecialchars(
            $appointment->getDoctorService()->getMedicalService()->getName(),
            ENT_QUOTES,
            'UTF-8'
        );
        $date        = $slot->getStartAt()->format('l, d F Y');
        $timeRange   = $slot->getStartAt()->format('H:i') . ' – ' . $slot->getEndAt()->format('H:i');
        $price       = number_format((float) $appointment->getDoctorService()->getPrice(), 2) . ' RON';

        $logoPart = (new DataPart(fopen(self::LOGO_PATH, 'rb'), 'logo', 'image/png'))->asInline();
        $cid      = $logoPart->getContentId();

        return (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($user->getEmail(), "$firstName $lastName"))
            ->subject('Appointment Confirmed – HealthMate')
            ->html(self::buildHtml($firstName, $doctorName, $clinicName, $serviceName, $date, $timeRange, $price, $cid))
            ->addPart($logoPart);
    }

    private static function buildHtml(
        string $firstName,
        string $doctorName,
        string $clinicName,
        string $serviceName,
        string $date,
        string $timeRange,
        string $price,
        string $cid
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Appointment Confirmed</title>
</head>
<body style="margin:0;padding:0;background-color:#F0F4F8;font-family:'Segoe UI',Arial,sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" border="0"
         style="background-color:#F0F4F8;padding:48px 16px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" border="0"
               style="max-width:600px;width:100%;">

          <!-- Logo header -->
          <tr>
            <td style="background:#ffffff;padding:32px 40px 24px;
                        border-radius:18px 18px 0 0;text-align:center;
                        border-bottom:3px solid;
                        border-image:linear-gradient(135deg,#00c6ff,#0072ff) 1;">
              <img src="cid:{$cid}" alt="HealthMate" width="200"
                   style="display:block;margin:0 auto;max-width:200px;height:auto;" />
            </td>
          </tr>

          <!-- Accent bar -->
          <tr>
            <td style="background:linear-gradient(135deg,#00c6ff 0%,#0072ff 100%);
                        height:4px;font-size:0;line-height:0;">&nbsp;</td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="background:#ffffff;padding:36px 40px 32px;">

              <!-- Check icon + heading -->
              <div style="text-align:center;margin-bottom:24px;">
                <div style="display:inline-block;background:linear-gradient(135deg,#00c6ff,#0072ff);
                             border-radius:50%;width:56px;height:56px;line-height:56px;
                             font-size:28px;color:#fff;text-align:center;">✓</div>
              </div>

              <h1 style="margin:0 0 6px;font-size:22px;font-weight:800;color:#1A2138;
                          text-align:center;letter-spacing:-0.3px;">
                Appointment Confirmed!
              </h1>
              <p style="margin:0 0 28px;font-size:15px;color:#6B7A99;line-height:1.65;text-align:center;">
                Hi {$firstName}, your appointment has been successfully booked.
              </p>

              <!-- Details card -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background:#F8FAFF;border-radius:12px;margin-bottom:28px;">
                <tr>
                  <td style="padding:20px 24px;">
                    <p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#9AA5B4;
                                text-transform:uppercase;letter-spacing:0.6px;">
                      Appointment Details
                    </p>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0"
                           style="margin-top:14px;">
                      <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:13px;color:#9AA5B4;width:40%;">Doctor</td>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:14px;font-weight:600;color:#1A2138;">{$doctorName}</td>
                      </tr>
                      <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:13px;color:#9AA5B4;">Clinic</td>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:14px;font-weight:600;color:#1A2138;">{$clinicName}</td>
                      </tr>
                      <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:13px;color:#9AA5B4;">Service</td>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:14px;font-weight:600;color:#1A2138;">{$serviceName}</td>
                      </tr>
                      <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:13px;color:#9AA5B4;">Date</td>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:14px;font-weight:600;color:#1A2138;">{$date}</td>
                      </tr>
                      <tr>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:13px;color:#9AA5B4;">Time</td>
                        <td style="padding:7px 0;border-bottom:1px solid #E8EEF7;
                                    font-size:14px;font-weight:600;color:#1A2138;">{$timeRange}</td>
                      </tr>
                      <tr>
                        <td style="padding:7px 0;font-size:13px;color:#9AA5B4;">Price</td>
                        <td style="padding:7px 0;font-size:15px;font-weight:800;
                                    color:#0072ff;">{$price}</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td align="center">
                    <a href="http://localhost:5173/profile"
                       style="display:inline-block;
                              background:linear-gradient(135deg,#00c6ff,#0072ff);
                              color:#ffffff;text-decoration:none;font-size:15px;
                              font-weight:700;padding:15px 40px;border-radius:10px;
                              letter-spacing:0.2px;">
                      View My Appointments →
                    </a>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#F8FAFF;padding:24px 40px;
                        border-radius:0 0 18px 18px;
                        border-top:1px solid #E8EEF7;">
              <p style="margin:0;font-size:12px;color:#9AA5B4;
                          text-align:center;line-height:1.7;">
                <strong style="color:#6B7A99;">HealthMate</strong> ·
                Calea București 32, Craiova, Dolj<br/>
                <a href="mailto:j234mediplant@gmail.com"
                   style="color:#0072ff;text-decoration:none;">Contact support</a>
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
