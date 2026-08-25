<div>
  <div class="band band-{{ $live ? 'info' : 'caution' }}" style="border-radius:var(--radius);margin-bottom:var(--s5)">
    <x-icon name="{{ $live ? 'info' : 'alert' }}" class="band-icon" />
    <div>
      <span class="band-title">{{ $live ? 'Live model' : 'Demo responses' }}</span>
      @if ($live)
        Requests go to OpenAI from this server. The key is in <span class="mono">.env</span> and
        never reaches a browser. Label text is patient-adjacent data leaving Nigeria &mdash; a
        question to answer under the NDPA before real patients.
      @else
        No API key is set, so this returns canned answers. Type <span class="mono">blurry</span> or
        <span class="mono">handwritten</span> to exercise the low-confidence branch on purpose.
      @endif
    </div>
  </div>

  <div class="grid" style="grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);align-items:start">

    <div class="label"><div class="label-body stack">
      <div class="row" role="group" aria-label="Task">
        <button class="btn btn-sm {{ $mode === 'explain' ? '' : 'btn-secondary' }}" type="button"
                wire:click="$set('mode', 'explain')">Explain a label</button>
        <button class="btn btn-sm {{ $mode === 'identify' ? '' : 'btn-secondary' }}" type="button"
                wire:click="$set('mode', 'identify')">Identify a pill</button>
      </div>

      <p class="hint">
        @if ($mode === 'explain')
          Reads what is on a pack, a pharmacy note or a doctor's handwriting and rewrites it in
          plain language, in the patient's own language. Handwriting that could be read two ways
          comes back as low confidence rather than a guess.
        @else
          Describes what is visible in a photo of a tablet. Pill identification from a photo is
          genuinely unreliable, so this screen is built to say so: a medium-confidence answer can
          never render as one certain name.
        @endif
      </p>

      <label class="field">
        <span class="field-label">Patient</span>
        <select class="select" wire:model.live="patientId">
          <option value="">Not for a specific patient (English)</option>
          @foreach ($patients as $p)
            <option value="{{ $p->id }}">{{ $p->name }} &middot; {{ $p->langName() }}</option>
          @endforeach
        </select>
        <span class="field-hint">Sets the language the answer is written in.</span>
      </label>

      <label class="field">
        <span class="field-label">What the label says</span>
        <textarea class="textarea" rows="5" wire:model="text"
                  placeholder="Type or paste the text on the pack…"></textarea>
        @error('text')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
      </label>

      <label class="field">
        <span class="field-label">Or a photo</span>
        <input class="input" type="file" wire:model="photo" accept="image/*" capture="environment">
        @error('photo')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        <span class="field-hint" wire:loading wire:target="photo">Uploading…</span>
      </label>

      @if ($photo)
        <img src="{{ $photo->temporaryUrl() }}" alt="Uploaded label"
             style="max-width:100%;border-radius:var(--radius);border:1px solid var(--line)">
      @endif

      <button class="btn btn-block" type="button" wire:click="run" wire:loading.attr="disabled" wire:target="run,photo">
        <span wire:loading.remove wire:target="run">Read it</span>
        <span wire:loading wire:target="run">Reading…</span>
      </button>

      @if ($error)
        <div class="band band-alert" style="border-radius:var(--radius)">
          <x-icon name="alert" class="band-icon" />
          <div><span class="band-title">That did not work</span>{{ $error }}</div>
        </div>
      @endif
    </div></div>

    <div class="stack">
      @if (! $result)
        <div class="label"><div class="empty">
          Nothing read yet. The answer appears here, in the patient's language.
        </div></div>

      @elseif ($mode === 'explain')
        @php $shaky = in_array($result['readingConfidence'], ['low', 'cannot_read'], true) || $result['askPharmacist']; @endphp

        <div class="label"><div class="label-body stack">
          <div class="row-between wrap">
            <div>
              <div class="section-title" style="margin-top:0">{{ $result['medicineName'] ?: 'Could not name it' }}</div>
              <span class="hint">Reading confidence: {{ str_replace('_', ' ', $result['readingConfidence']) }}</span>
            </div>
            <span class="badge badge-{{ $shaky ? 'caution' : 'safe' }}">
              {{ $shaky ? 'Ask a pharmacist' : 'Confident reading' }}</span>
          </div>

          @if ($shaky)
            <div class="band band-caution" style="border-radius:var(--radius)">
              <x-icon name="alert" class="band-icon" />
              <div>
                <span class="band-title">Not certain enough to act on</span>
                Reading handwriting wrong could mean the wrong medicine. This one is deliberately
                refusing to guess, which is the correct answer rather than a failure.
              </div>
            </div>
          @endif

          <div><strong>What it is for</strong><p>{{ $result['purpose'] }}</p></div>
          <div><strong>How to take it</strong><p>{{ $result['dosing'] }}</p></div>

          @if ($result['sideEffects'])
            <div><strong>What you might notice</strong>
              <ul style="margin:var(--s2) 0 0;padding-left:1.1rem">
                @foreach ($result['sideEffects'] as $s)<li>{{ $s }}</li>@endforeach
              </ul>
            </div>
          @endif

          @if ($result['warnings'])
            <div><strong>Be careful about</strong>
              <ul style="margin:var(--s2) 0 0;padding-left:1.1rem">
                @foreach ($result['warnings'] as $w)<li>{{ $w }}</li>@endforeach
              </ul>
            </div>
          @endif

          @if ($result['storage'])
            <div><strong>Keeping it</strong><p>{{ $result['storage'] }}</p></div>
          @endif
        </div></div>

        @if ($prescriptions->isNotEmpty())
          <div class="label"><div class="label-body stack">
            <div class="section-title" style="margin-top:0">Save this to a prescription</div>
            <p class="hint">
              Attaching it means the patient sees this explanation in their own app, in their own
              language, without another model call.
            </p>
            <div class="row wrap" style="gap:var(--s3)">
              <select class="select" wire:model="prescriptionId" style="max-width:22rem">
                <option value="">Choose a medicine…</option>
                @foreach ($prescriptions as $rx)
                  <option value="{{ $rx->id }}">{{ $rx->name }} {{ $rx->strength }}</option>
                @endforeach
              </select>
              <button class="btn btn-sm" type="button" wire:click="attachToPrescription"
                      @disabled(! $prescriptionId || $shaky)>Attach</button>
            </div>
            @if ($shaky)
              <span class="hint">A low-confidence reading cannot be attached.</span>
            @endif
          </div></div>
        @endif

      @else
        <div class="label"><div class="label-body stack">
          <div class="row-between wrap">
            <div class="section-title" style="margin-top:0">What I can see</div>
            <span class="badge badge-{{ $displayRule['tone'] === 'info' ? 'info' : 'caution' }}">
              {{ str_replace('_', ' ', $result['confidence']) }}</span>
          </div>

          <p>{{ $result['visualFeatures'] }}</p>

          <div class="band band-caution" style="border-radius:var(--radius)">
            <x-icon name="alert" class="band-icon" />
            <div><span class="band-title">Honest limit</span>{{ $displayRule['note'] }}</div>
          </div>

          @if ($displayRule['showSingleName'] && $result['candidates'])
            <div>
              <strong style="font-size:var(--step-1)">{{ $result['candidates'][0]['name'] }}</strong>
              <p class="hint">{{ $result['candidates'][0]['reasoning'] }}</p>
            </div>
          @elseif ($displayRule['showAllCandidates'] && $result['candidates'])
            <div class="stack-s">
              <strong>Possibilities &mdash; not an answer</strong>
              @foreach ($result['candidates'] as $c)
                <div class="label" style="background:var(--surface-2)"><div class="label-body">
                  <strong>{{ $c['name'] }}</strong>
                  <p class="hint">{{ $c['reasoning'] }}</p>
                </div></div>
              @endforeach
            </div>
          @else
            <div class="empty">No candidate is listed on purpose. Guessing here would be worse than nothing.</div>
          @endif

          <p class="hint">
            The model supplies a confidence; <span class="mono">AiService::displayRule()</span> decides
            what this screen is allowed to render. That is why a medium-confidence result cannot
            appear as one certain name.
          </p>
        </div></div>
      @endif
    </div>
  </div>
</div>
