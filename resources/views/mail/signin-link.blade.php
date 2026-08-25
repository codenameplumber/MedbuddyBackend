{{--
  Sign-in link. Same privacy rule as a dose reminder: nothing here names a
  condition, a medicine or a dose.
--}}
<!DOCTYPE html>
<html lang="{{ $patient->lang }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>MedBuddy</title></head>
<body style="margin:0;padding:0;background:#F4F1E9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1E9;padding:28px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
             style="max-width:520px;background:#FFFFFF;border:1px solid #E3DED2;border-radius:12px;overflow:hidden;">
        <tr><td style="background:#16150F;height:8px;line-height:8px;font-size:0;">&nbsp;</td></tr>
        <tr><td style="padding:26px 28px 8px;">
          <div style="font-size:13px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#7D7770;">MedBuddy</div>
          <div style="font-size:20px;color:#55504A;margin-top:14px;">
            {{ __('reminder.greeting', ['name' => $patient->firstName()], $patient->lang) }}
          </div>
          <h1 style="margin:10px 0 0;font-size:26px;line-height:1.2;color:#16150F;font-weight:700;">
            {{ __('reminder.opencta', [], $patient->lang) }}
          </h1>
        </td></tr>
        <tr><td style="padding:24px 28px 8px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"><tr>
            <td align="center" style="background:#16150F;border-radius:10px;">
              <a href="{{ $url }}" style="display:block;padding:20px 24px;color:#FCFBF8;text-decoration:none;font-size:19px;font-weight:700;">
                {{ __('reminder.opencta', [], $patient->lang) }}
              </a>
            </td>
          </tr></table>
        </td></tr>
        <tr><td style="padding:18px 28px 26px;">
          <p style="margin:0;font-size:13px;line-height:1.6;color:#7D7770;">
            This link works once and stops working after 30 minutes. If you did not ask for it,
            you can ignore this email.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
