<div>
  <form wire:submit="save" class="grid" style="grid-template-columns:1fr 1fr;align-items:start">

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">Who they are</div>

      <label class="field">
        <span class="field-label">Full name</span>
        <input class="input" type="text" wire:model="name" autofocus>
        @error('name')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
      </label>

      <div class="row" style="gap:var(--s4);align-items:flex-start">
        <label class="field grow">
          <span class="field-label">Age</span>
          <input class="input" type="number" min="0" max="120" wire:model="age">
          @error('age')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        </label>
        <label class="field grow">
          <span class="field-label">Language</span>
          <select class="select" wire:model="lang">
            @foreach ($languages as $code => $label)
              <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
          </select>
          <span class="field-hint">Every reminder, question and answer is written in this language.</span>
        </label>
      </div>

      <label class="field">
        <span class="field-label">Condition</span>
        <input class="input" type="text" wire:model="condition" placeholder="Hypertension, Type 2 diabetes…">
        <span class="field-hint">Used for cohort views only. It never appears in a message.</span>
      </label>
    </div></div>

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">How to reach them</div>

      <label class="field">
        <span class="field-label">Email</span>
        <input class="input" type="email" wire:model="email" placeholder="name@example.com">
        @error('email')
          <span class="field-hint" style="color:var(--alert)">{{ $message }}</span>
        @else
          <span class="field-hint">Email is the only channel currently enabled.</span>
        @enderror
      </label>

      <label class="field">
        <span class="field-label">Phone</span>
        <input class="input" type="tel" wire:model="phone" placeholder="+234…">
        <span class="field-hint">Stored for the clinic to call. Nothing is sent to it yet.</span>
      </label>

      <div class="row" style="gap:var(--s4);align-items:flex-start">
        <label class="field grow">
          <span class="field-label">Caregiver name</span>
          <input class="input" type="text" wire:model="caregiver_name">
        </label>
        <label class="field grow">
          <span class="field-label">Caregiver email</span>
          <input class="input" type="email" wire:model="caregiver_email">
          @error('caregiver_email')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        </label>
      </div>
      <span class="field-hint">
        A caregiver summary names medicines, so it goes only here — never to the
        patient's own lock screen.
      </span>
    </div></div>

    <div class="label" style="grid-column:1/-1"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">Consent</div>
      <p class="hint">
        Recorded per purpose and per channel, and revocable. Every outbound message
        checks this first, so turning messaging off here genuinely stops the mail.
      </p>

      <label class="row" style="gap:var(--s3);align-items:flex-start">
        <input type="checkbox" wire:model.live="consentMessaging" style="margin-top:4px">
        <span>
          <strong>Dose reminders by email</strong>
          <span class="hint" style="display:block">
            A reminder names the dose window and nothing else — never the medicine,
            the dose or the condition.
          </span>
        </span>
      </label>

      <label class="row" style="gap:var(--s3);align-items:flex-start">
        <input type="checkbox" wire:model="consentResearch" style="margin-top:4px">
        <span>
          <strong>Include their reports in cohort analysis</strong>
          <span class="hint" style="display:block">
            Separate decision on purpose: someone may want reminders and still keep
            their reports out of the outcomes and safety-signal views.
          </span>
        </span>
      </label>

      @if ($consentMessaging && blank($email))
        <div class="band band-caution" style="border-radius:var(--radius)">
          <x-icon name="alert" class="band-icon" />
          <div>
            <span class="band-title">No address to send to</span>
            Messaging consent needs an email address, or the system is recording a
            promise it cannot keep.
          </div>
        </div>
      @endif
    </div></div>

    <div class="row" style="grid-column:1/-1;gap:var(--s3)">
      <button class="btn" type="submit" wire:loading.attr="disabled">
        {{ $patient ? 'Save changes' : 'Enrol patient' }}
      </button>
      <a class="btn btn-secondary" wire:navigate
         href="{{ $patient ? route('clinic.patient', $patient) : route('clinic.patients') }}">Cancel</a>
      <span class="grow"></span>
      @if ($patient)
        <span class="hint">Enrolled {{ $patient->enrolled_at?->format('j M Y') }}</span>
      @endif
    </div>
  </form>
</div>
