# Chunk A part 2 — routes, nav, buttons, flash toasts, an admin to seed.
import io

def patch(path, old, new, count=1):
    s = io.open(path, encoding='utf-8').read()
    assert s.count(old) >= 1, 'NOT FOUND in %s:\n%s' % (path, old[:200])
    io.open(path, 'w', encoding='utf-8').write(s.replace(old, new, count))
    print('patched', path)

# ── routes ───────────────────────────────────────────────────────────
patch('routes/web.php', """    Route::get('/patients',    Clinic\\Patients::class)->name('patients');
    Route::get('/patients/{patient}', Clinic\\PatientDetail::class)->name('patient');""",
"""    Route::get('/patients',    Clinic\\Patients::class)->name('patients');

    // Literal segments before the bound one, or "new" is looked up as an id.
    Route::get('/patients/new', Clinic\\PatientForm::class)->name('patient.create');
    Route::get('/patients/{patient}', Clinic\\PatientDetail::class)->name('patient');
    Route::get('/patients/{patient}/edit', Clinic\\PatientForm::class)->name('patient.edit');
    Route::get('/patients/{patient}/prescribe/{prescription?}', Clinic\\PrescriptionForm::class)->name('prescription.form');
    Route::post('/patients/{patient}/summary', [CaregiverController::class, 'send'])->name('patient.summary');""")

patch('routes/web.php', """    Route::get('/settings',    Clinic\\Settings::class)->name('settings');""",
"""    Route::get('/explain',     Clinic\\Explain::class)->name('explain');
    Route::get('/team',        Clinic\\Team::class)->name('team');
    Route::get('/settings',    Clinic\\Settings::class)->name('settings');""")

patch('routes/web.php', "use App\\Http\\Controllers\\AuthController;",
      "use App\\Http\\Controllers\\AuthController;\nuse App\\Http\\Controllers\\CaregiverController;")

# ── clinic layout: Explain + Team in the nav, flashed toasts ─────────
patch('resources/views/components/layouts/clinic.blade.php',
"""      ['outcomes',   'clinic.outcomes',   'clipboard', 'Outcomes',       null],
    ]],""",
"""      ['outcomes',   'clinic.outcomes',   'clipboard', 'Outcomes',       null],
      ['explain',    'clinic.explain',    'search',    'Explain a label', null],
    ]],""")

patch('resources/views/components/layouts/clinic.blade.php',
"""    <a class="nav-item" href="{{ route('clinic.settings') }}" wire:navigate>
      <x-icon name="lock" class="i-sm" /><span>Settings</span>
    </a>""",
"""    <a class="nav-item {{ $current === 'clinic.team' ? 'is-active' : '' }}" href="{{ route('clinic.team') }}" wire:navigate>
      <x-icon name="users" class="i-sm" /><span>Team &amp; audit</span>
    </a>
    <a class="nav-item {{ $current === 'clinic.settings' ? 'is-active' : '' }}" href="{{ route('clinic.settings') }}" wire:navigate>
      <x-icon name="lock" class="i-sm" /><span>Settings</span>
    </a>""")

# A redirect after save loses a dispatched browser event, so flashed
# messages get replayed into the same toast stack.
patch('resources/views/components/layouts/clinic.blade.php',
"""<div class="toast-stack" id="toasts" role="status" aria-live="polite"></div>""",
"""<div class="toast-stack" id="toasts" role="status" aria-live="polite"></div>

@if (session('toast'))
  <script>window.addEventListener('load', function () {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: @json(session('toast')), tone: 'safe' } }));
  });</script>
@endif""")

# ── patients list: enrol button ──────────────────────────────────────
patch('resources/views/livewire/clinic/patients.blade.php',
"""      <span class="grow"></span>
      <span class="hint dt-count">{{ $rows->count() }} shown</span>""",
"""      <span class="grow"></span>
      <span class="hint dt-count">{{ $rows->count() }} shown</span>
      @can('create', \\App\\Models\\Patient::class)
        <a class="btn btn-sm" href="{{ route('clinic.patient.create') }}" wire:navigate>
          <x-icon name="plus" class="i-sm" /> Enrol a patient
        </a>
      @endcan""")

# ── patient detail: edit, prescribe, caregiver summary ───────────────
patch('resources/views/livewire/clinic/patient-detail.blade.php',
"""    <span class="grow"></span>
    <button class="btn" type="button" wire:click="sendReminderNow" wire:loading.attr="disabled">""",
"""    <span class="grow"></span>
    @can('update', $patient)
      <a class="btn btn-secondary btn-sm" href="{{ route('clinic.patient.edit', $patient) }}" wire:navigate>Edit details</a>
    @endcan
    @can('create', \\App\\Models\\Prescription::class)
      <a class="btn btn-secondary btn-sm" href="{{ route('clinic.prescription.form', $patient) }}" wire:navigate>
        <x-icon name="plus" class="i-sm" /> Add medicine
      </a>
    @endcan
    <button class="btn" type="button" wire:click="sendReminderNow" wire:loading.attr="disabled">""")

patch('resources/views/livewire/clinic/patient-detail.blade.php',
"""      <thead><tr><th>Medicine</th><th>Strength</th><th>For</th><th>Schedule</th></tr></thead>
    <tbody>
      @foreach ($patient->activePrescriptions as $rx)
        <tr>
          <td><strong>{{ $rx->name }}</strong></td>
          <td class="mono">{{ $rx->strength }}</td>
          <td class="hint">{{ $rx->purpose }}</td>
          <td class="mono">{{ implode(' · ', $rx->times) }}</td>
        </tr>
      @endforeach
    </tbody>""",
"""      <thead><tr><th>Medicine</th><th>Strength</th><th>For</th><th>Schedule</th><th>Explanation</th><th></th></tr></thead>
    <tbody>
      @forelse ($patient->activePrescriptions as $rx)
        <tr>
          <td><strong>{{ $rx->name }}</strong></td>
          <td class="mono">{{ $rx->strength }}</td>
          <td class="hint">{{ $rx->purpose }}</td>
          <td class="mono">{{ implode(' · ', $rx->times) }}</td>
          <td>
            @if ($rx->ai_summary)
              <span class="badge badge-safe">in {{ $patient->langName() }}</span>
            @else
              <span class="hint">none yet</span>
            @endif
          </td>
          <td class="num">
            @can('update', $rx)
              <a class="btn btn-quiet btn-sm" href="{{ route('clinic.prescription.form', [$patient, $rx]) }}" wire:navigate>Edit</a>
            @endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty">No medicines recorded. Nothing will be reminded about.</div></td></tr>
      @endforelse
    </tbody>""")

patch('resources/views/livewire/clinic/patient-detail.blade.php',
"""    <p class="hint">
      Revocable per channel at any time. Every view of this record is written to the audit log.
    </p>
  </div></div>
</div>""",
"""    @if ($patient->consents->isEmpty())
      <div class="empty">No consent on record. Nothing can be sent to this patient.</div>
    @endif
    <div class="row wrap" style="gap:var(--s3)">
      @can('manageConsent', $patient)
        <a class="btn btn-sm btn-secondary" href="{{ route('clinic.patient.edit', $patient) }}" wire:navigate>
          Change consent
        </a>
      @endcan
    </div>
    <p class="hint">
      Revocable per channel at any time. Every view of this record is written to the audit log.
    </p>
  </div></div>

  <div class="section-title">Caregiver</div>
  <div class="label"><div class="label-body stack">
    @if ($patient->caregiver_email)
      <div class="row-between wrap">
        <div>
          <strong>{{ $patient->caregiver_name ?: 'Caregiver' }}</strong>
          <div class="hint mono">{{ $patient->caregiver_email }}</div>
        </div>
        <form method="POST" action="{{ route('clinic.patient.summary', $patient) }}">@csrf
          <button class="btn btn-sm" type="submit">Write and send a summary</button>
        </form>
      </div>
      <p class="hint">
        A caregiver summary is generated from this patient's own dose history and
        reports, written in {{ $patient->langName() }}, and shown to you before
        it is sent. Unlike a dose reminder it <em>may</em> name medicines — it is a
        consented disclosure to one nominated person, not a lock-screen notification.
      </p>
    @else
      <div class="empty">
        No caregiver on record.
        @can('update', $patient)
          <a href="{{ route('clinic.patient.edit', $patient) }}" wire:navigate>Add one</a>.
        @endcan
      </div>
    @endif
  </div></div>
</div>""")

# ── seeder: somebody has to be able to add staff ─────────────────────
patch('database/seeders/DatabaseSeeder.php',
"""        foreach ([
            ['Dr. A. Adeyemi', 'a.adeyemi@ikejagh.ng', 'physician'],""",
"""        foreach ([
            ['K. Adesina',     'admin@ikejagh.ng',     'admin'],
            ['Dr. A. Adeyemi', 'a.adeyemi@ikejagh.ng', 'physician'],""")

patch('resources/views/auth/login.blade.php',
"""          <span class="mono">a.adeyemi@ikejagh.ng</span> (physician) ·""",
"""          <span class="mono">admin@ikejagh.ng</span> (admin) ·
          <span class="mono">a.adeyemi@ikejagh.ng</span> (physician) ·""")
