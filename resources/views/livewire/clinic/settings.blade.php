<div>
  <div class="grid" style="grid-template-columns:1fr 1fr">

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">Attention thresholds</div>
      <p class="hint">
        These decide who appears in "Needs attention". They live in one place
        (<span class="mono">config/medbuddy.php</span>) so a clinical lead can change what the word
        means without touching a screen.
      </p>
      <div class="table-wrap"><table class="table">
        <tbody>
          <tr><td>Adherence concern</td><td class="num mono">{{ $thresholds['concern'] }}%</td></tr>
          <tr><td>Adherence critical</td><td class="num mono">{{ $thresholds['critical'] }}%</td></tr>
          <tr><td>Silent for</td><td class="num mono">{{ $thresholds['silent'] }} days</td></tr>
          <tr><td>Severity concern</td><td class="num mono">{{ $thresholds['severity'] }}</td></tr>
        </tbody>
      </table></div>
    </div></div>

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">Channels</div>
      <div class="row-between wrap">
        <div><strong>Email</strong><div class="hint">Mailer: <span class="mono">{{ $mailer }}</span></div></div>
        <span class="badge badge-safe">Enabled</span>
      </div>
      <div class="row-between wrap">
        <div><strong>SMS</strong><div class="hint">Needs a provider account and sender-ID approval</div></div>
        <span class="badge badge-caution">Not enabled</span>
      </div>
      <div class="row-between wrap">
        <div><strong>WhatsApp</strong><div class="hint">Needs Meta verification and template approval</div></div>
        <span class="badge badge-caution">Not enabled</span>
      </div>
      <div class="foot-note">
        <strong style="color:var(--ink)">Adding a channel is one line.</strong>
        <span class="mono">DoseReminder::via()</span> returns <span class="mono">['mail']</span> today.
        Adding SMS later means returning <span class="mono">['mail', 'vonage']</span> plus a provider
        key — no refactor. That is why email-only is a simplification rather than technical debt.
      </div>
    </div></div>

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">AI</div>
      <div class="row-between wrap">
        <div>
          <strong>{{ $aiLive ? 'Live' : 'Demo (mock)' }}</strong>
          <div class="hint">{{ $aiProvider }} · <span class="mono">{{ $aiModel }}</span></div>
        </div>
        <span class="badge badge-{{ $aiLive ? 'safe' : 'info' }}">{{ $aiLive ? 'Key present' : 'No key' }}</span>
      </div>
      <div class="foot-note">
        The key lives in <span class="mono">.env</span> and never reaches a browser — the single
        biggest security fix over the front-end-only prototype. Set
        <span class="mono">MEDBUDDY_AI_PROVIDER</span>, the matching API key and <span class="mono">MEDBUDDY_AI_MODE=live</span>
        to switch on. Mock mode keeps every screen demoable with no key and no network.
      </div>
      <div class="band band-info" style="border-radius:var(--radius)">
        <x-icon name="info" class="band-icon" />
        <div>
          <span class="band-title">Data residency</span>
          Live mode sends patient-adjacent text to servers outside Nigeria. Under the NDPA that is a
          question to answer before real patients, not a technical detail.
        </div>
      </div>
    </div></div>

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">Team &amp; audit</div>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Name</th><th>Role</th></tr></thead>
        <tbody>
          @foreach (\App\Models\User::orderBy('id')->get() as $u)
            <tr><td><strong>{{ $u->name }}</strong><div class="hint mono">{{ $u->email }}</div></td>
                <td><span class="badge">{{ $u->role }}</span></td></tr>
          @endforeach
        </tbody>
      </table></div>
      <p class="hint">
        {{ \App\Models\Audit::count() }} audit entries recorded. Every view of a patient record is
        written to an append-only table — a regulatory requirement, not a feature.
      </p>
    </div></div>

  </div>
</div>
