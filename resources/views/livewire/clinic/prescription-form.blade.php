<div>
  <div class="row wrap" style="margin-bottom:var(--s5)">
    <span class="badge">{{ $patient->name }}</span>
    <span class="badge">{{ $patient->langName() }}</span>
    @if ($patient->condition)<span class="badge">{{ $patient->condition }}</span>@endif
  </div>

  <form wire:submit="save" class="grid" style="grid-template-columns:1fr 1fr;align-items:start">

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">The medicine</div>

      <label class="field">
        <span class="field-label">Name on the pack</span>
        <input class="input" type="text" wire:model="name" autofocus placeholder="Amlodipine">
        @error('name')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
      </label>

      <label class="field">
        <span class="field-label">Generic name</span>
        <input class="input" type="text" wire:model="generic">
        <span class="field-hint">Optional. Used to match interactions across brand names.</span>
      </label>

      <div class="row" style="gap:var(--s4);align-items:flex-start">
        <label class="field grow">
          <span class="field-label">Strength</span>
          <input class="input" type="text" wire:model="strength" placeholder="5 mg">
        </label>
        <label class="field grow">
          <span class="field-label">Form</span>
          <select class="select" wire:model="form">
            @foreach ($forms as $f)<option value="{{ $f }}">{{ ucfirst($f) }}</option>@endforeach
          </select>
        </label>
      </div>

      <label class="field">
        <span class="field-label">What it is for</span>
        <input class="input" type="text" wire:model="purpose" placeholder="blood pressure">
        <span class="field-hint">
          Plain words, not a diagnosis — this is what the patient reads in their own app.
        </span>
      </label>
    </div></div>

    <div class="label"><div class="label-body stack">
      <div class="section-title" style="margin-top:0">When to take it</div>
      <p class="hint">
        Each time here is a dose window. Medicines sharing a window are collapsed into
        one reminder: four separate emails at 08:00 would train someone to ignore all of them.
      </p>

      <div class="stack-s">
        @foreach ($times as $i => $t)
          @php $h = (int) substr($t ?: '08:00', 0, 2); @endphp
          <div class="row" style="gap:var(--s3)" wire:key="time-{{ $i }}">
            <input class="input" type="time" wire:model="times.{{ $i }}" style="max-width:12rem">
            <span class="badge">{{ $h < 12 ? 'morning' : ($h < 17 ? 'afternoon' : 'evening') }}</span>
            <span class="grow"></span>
            @if (count($times) > 1)
              <button class="btn btn-sm btn-secondary" type="button" wire:click="removeTime({{ $i }})">Remove</button>
            @endif
          </div>
          @error('times.'.$i)<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        @endforeach
      </div>

      <button class="btn btn-sm btn-secondary" type="button" wire:click="addTime">
        <x-icon name="plus" class="i-sm" /> Add another time
      </button>

      <label class="field">
        <span class="field-label">Stop after</span>
        <input class="input" type="date" wire:model="ends_on">
        @error('ends_on')
          <span class="field-hint" style="color:var(--alert)">{{ $message }}</span>
        @else
          <span class="field-hint">
            Leave empty for an ongoing prescription. A TB or antibiotic course should
            have an end date so reminders stop on their own.
          </span>
        @enderror
      </label>
    </div></div>

    @if ($patient->activePrescriptions->isNotEmpty())
      <div class="label" style="grid-column:1/-1"><div class="label-body">
        <div class="section-title" style="margin-top:0">Already taking</div>
        <div class="table-wrap"><table class="table">
          <thead><tr><th>Medicine</th><th>Strength</th><th>Times</th></tr></thead>
          <tbody>
            @foreach ($patient->activePrescriptions as $rx)
              <tr>
                <td><strong>{{ $rx->name }}</strong></td>
                <td class="mono">{{ $rx->strength }}</td>
                <td class="mono">{{ implode(' · ', $rx->times ?? []) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table></div>
        <p class="hint" style="margin-top:var(--s3)">
          Check the Medicines screen for interactions after saving.
        </p>
      </div></div>
    @endif

    <div class="row" style="grid-column:1/-1;gap:var(--s3)">
      <button class="btn" type="submit">{{ $prescription ? 'Save changes' : 'Add medicine' }}</button>
      <a class="btn btn-secondary" wire:navigate href="{{ route('clinic.patient', $patient) }}">Cancel</a>
    </div>
  </form>
</div>
