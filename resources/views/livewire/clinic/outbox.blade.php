@php use App\Support\Chart; @endphp
<div>
  <div class="grid grid-3">
    <div class="stat"><div class="stat-label">Scheduled today</div>
      <div class="stat-value">{{ $today }}</div><div class="stat-note">one per patient per dose window</div></div>
    <div class="stat"><div class="stat-label">Sent · 7 days</div>
      <div class="stat-value">{{ $funnel[0]['value'] }}</div><div class="stat-note">reminder emails</div></div>
    <div class="stat"><div class="stat-label">Responded</div>
      <div class="stat-value" style="color:var(--safe)">{{ $funnel[3]['value'] }}</div>
      <div class="stat-note">replied within the window</div></div>
    <div class="stat"><div class="stat-label">Queued</div>
      <div class="stat-value">{{ $queued->count() }}</div><div class="stat-note">not yet sent</div></div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr;margin-top:var(--s5)">
    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Delivery funnel</span>
        <span class="chart-note">last 7 days</span></div>
      {!! Chart::bars($funnel, ['title' => 'Delivery funnel']) !!}
      <p class="hint" style="margin-top:var(--s3)">
        Response rate is the only number here that matters. Delivered-but-ignored is a message that
        trained someone to ignore the next one.
      </p>
    </div>

    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Run the dispatcher</span></div>
      <p class="hint">
        In production this fires from cron every 15 minutes. Run it now to send any dose window that
        opened in the last 20 minutes.
      </p>
      <button class="btn btn-block" type="button" wire:click="runNow" wire:loading.attr="disabled"
              style="margin-top:var(--s4)">
        <span wire:loading.remove wire:target="runNow"><x-icon name="send" class="i-sm" /> Dispatch due reminders now</span>
        <span wire:loading wire:target="runNow">Dispatching…</span>
      </button>

      <div class="foot-note" style="margin-top:var(--s4)">
        <strong style="color:var(--ink)">Mailer: <span class="mono">{{ config('mail.default') }}</span></strong><br>
        @if (config('mail.default') === 'log')
          Emails are written to <span class="mono">storage/logs/laravel.log</span> rather than sent.
          Open the log to find the signed reminder link and follow it — the loop closes for real.
          Point <span class="mono">MAIL_MAILER</span> at SMTP to send properly.
        @else
          Sending over SMTP.
        @endif
      </div>
    </div>
  </div>

  <div class="band band-info" style="border-radius:var(--radius);margin-top:var(--s5)">
    <x-icon name="lock" class="band-icon" />
    <div>
      <span class="band-title">Why these messages say so little</span>
      The subject and body name the <strong>dose window</strong> — never the condition, the drug, or
      the dose. An email sits in an inbox, often on a shared computer, and its subject shows in a
      lock-screen preview: a message naming an antiretroviral can disclose a diagnosis to whoever is
      holding the phone. <span class="mono">ReminderComposer::assertSafe()</span> enforces this on
      every send, and a test fails the build if it is ever broken.
    </div>
  </div>

  <div class="section-title">Recently sent</div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Patient</th><th>Window</th><th>Slot</th><th>To</th><th>Sent</th><th>Opened</th><th>Responded</th></tr></thead>
    <tbody>
      @forelse ($recent as $r)
        <tr>
          <td><a href="{{ route('clinic.patient', $r->patient) }}" wire:navigate><strong>{{ $r->patient?->name }}</strong></a></td>
          <td class="mono">{{ $r->dose_time }}</td>
          <td><span class="badge">{{ $r->slot }}</span></td>
          <td class="hint mono">{{ $r->to }}</td>
          <td class="hint">{{ $r->sent_at?->diffForHumans() }}</td>
          <td>@if ($r->opened_at)<span class="badge badge-info">opened</span>@else<span class="hint">—</span>@endif</td>
          <td>@if ($r->responded_at)<span class="badge badge-safe">replied</span>@else<span class="hint">—</span>@endif</td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty">Nothing sent yet. Run the dispatcher above.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>
</div>
