{{--
  Reminder email.

  The subject and body name the DOSE WINDOW and nothing else. Never the
  condition, never the drug, never the dose. An email sits in an inbox, often
  on a shared family computer, and its subject shows in a lock-screen preview:
  a message naming an antiretroviral can disclose a diagnosis to whoever is
  holding the phone. ReminderComposer::assertSafe() enforces this and there is
  a test that fails the build if it is ever broken.
--}}
<!DOCTYPE html>
<html lang="{{ $patient->lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ __('reminder.subject', ['slot' => __('reminder.slot_'.$reminder->slot, [], $patient->lang)], $patient->lang) }}</title>
</head>
<body style="margin:0;padding:0;background:#F4F1E9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1E9;padding:28px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
             style="max-width:520px;background:#FFFFFF;border:1px solid #E3DED2;border-radius:12px;overflow:hidden;">

        <tr><td style="background:#B45309;height:8px;line-height:8px;font-size:0;">&nbsp;</td></tr>

        <tr><td style="padding:26px 28px 8px;">
          <div style="font-size:13px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#7D7770;">
            MedBuddy
          </div>
          <div style="font-size:20px;color:#55504A;margin-top:14px;">
            {{ __('reminder.greeting', ['name' => $patient->firstName()], $patient->lang) }}
          </div>
          <h1 style="margin:10px 0 0;font-size:27px;line-height:1.2;color:#16150F;font-weight:700;">
            {{ $body }}
          </h1>
        </td></tr>

        <tr><td style="padding:24px 28px 8px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr>
            <td align="center" style="background:#16150F;border-radius:10px;">
              <a href="{{ $url }}"
                 style="display:block;padding:20px 24px;color:#FCFBF8;text-decoration:none;font-size:19px;font-weight:700;">
                {{ __('reminder.cta', [], $patient->lang) }}
              </a>
            </td>
          </tr></table>
        </td></tr>

        <tr><td style="padding:18px 28px 26px;">
          <p style="margin:0;font-size:13px;line-height:1.6;color:#7D7770;">
            {{ __('reminder.footer', [], $patient->lang) }}
          </p>
        </td></tr>

      </table>

      <p style="max-width:520px;margin:16px auto 0;font-size:12px;color:#8A8279;line-height:1.6;text-align:center;">
        {{ __('reminder.optout', [], $patient->lang) }}
      </p>
    </td></tr>
  </table>
</body>
</html>
