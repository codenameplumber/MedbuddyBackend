{{--
  Caregiver summary.

  This one MAY name medicines, unlike a dose reminder. The difference is the
  audience: an explicit, consented disclosure to one nominated person, sent
  after a staff member read the draft and pressed send.
--}}
<!DOCTYPE html>
<html lang="{{ $patient->lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>How {{ $patient->firstName() }} is doing</title>
</head>
<body style="margin:0;padding:0;background:#F4F1E9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1E9;padding:28px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
             style="max-width:560px;background:#FFFFFF;border:1px solid #E3DED2;border-radius:12px;overflow:hidden;">

        <tr><td style="background:#0F5F5B;height:8px;line-height:8px;font-size:0;">&nbsp;</td></tr>

        <tr><td style="padding:26px 28px 6px;">
          <div style="font-size:13px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:#7D7770;">
            MedBuddy
          </div>
          <h1 style="margin:14px 0 0;font-size:24px;line-height:1.25;color:#16150F;font-weight:700;">
            How {{ $patient->firstName() }} is doing with their medicines
          </h1>
          <p style="margin:8px 0 0;font-size:14px;color:#7D7770;">
            {{ $patient->organisation?->name }} &middot; last 30 days
          </p>
        </td></tr>

        <tr><td style="padding:20px 28px 0;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                 style="background:#F7F5EF;border-radius:10px;">
            <tr><td style="padding:16px 18px;">
              <div style="font-size:30px;font-weight:700;color:#16150F;line-height:1;">{{ $adherence }}%</div>
              <div style="font-size:13px;color:#7D7770;margin-top:6px;">of scheduled doses taken</div>
            </td></tr>
          </table>
        </td></tr>

        <tr><td style="padding:20px 28px 4px;">
          <p style="margin:0;font-size:17px;line-height:1.65;color:#16150F;">
            {{ $summary['forCaregiver'] }}
          </p>
        </td></tr>

        @if ($summary['concerns'])
          <tr><td style="padding:16px 28px 0;">
            <div style="font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#7D7770;">
              Worth watching
            </div>
            <ul style="margin:10px 0 0;padding-left:20px;font-size:15px;line-height:1.6;color:#16150F;">
              @foreach ($summary['concerns'] as $c)<li style="margin-bottom:6px;">{{ $c }}</li>@endforeach
            </ul>
          </td></tr>
        @endif

        <tr><td style="padding:22px 28px 26px;">
          <p style="margin:0;font-size:13px;line-height:1.6;color:#7D7770;">
            This summary was written from {{ $patient->firstName() }}'s own dose records and
            checked by a member of clinic staff before it was sent. It is not medical advice.
            If you are worried about something, call the clinic.
          </p>
        </td></tr>

      </table>

      <p style="max-width:560px;margin:16px auto 0;font-size:12px;color:#8A8279;line-height:1.6;text-align:center;">
        You are receiving this because {{ $patient->firstName() }} named you as their caregiver.
        Reply to this email to stop these summaries.
      </p>
    </td></tr>
  </table>
</body>
</html>
