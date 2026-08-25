<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start">

  <div class="label"><div class="label-body">
    <div class="section-title" style="margin-top:0">Staff</div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Role</th><th>Can</th></tr></thead>
      <tbody>
        @foreach ($users as $u)
          <tr wire:key="u-{{ $u->id }}">
            <td><strong>{{ $u->name }}</strong><div class="hint mono">{{ $u->email }}</div></td>
            <td>
              @can('update', $u)
                <select class="select" style="max-width:11rem"
                        wire:change="changeRole({{ $u->id }}, $event.target.value)">
                  @foreach ($roles as $r)
                    <option value="{{ $r }}" @selected($u->role === $r)>{{ ucfirst($r) }}</option>
                  @endforeach
                </select>
              @else
                <span class="badge">{{ $u->role }}</span>
              @endcan
            </td>
            <td class="hint">
              {{ $u->canPrescribe() ? 'Prescribe · ' : '' }}{{ in_array($u->role, ['admin','physician','pharmacist'], true) ? 'Enrol · ' : '' }}Read · Send reminders
            </td>
          </tr>
        @endforeach
      </tbody>
    </table></div>

    <p class="hint" style="margin-top:var(--s3)">
      Roles are enforced by policies, not by hiding buttons: a nurse who types the
      prescription URL directly still gets a 403.
    </p>
  </div></div>

  <div class="label"><div class="label-body stack">
    <div class="section-title" style="margin-top:0">Add a staff member</div>

    @if ($canAdd)
      <form wire:submit="save" class="stack">
        <label class="field">
          <span class="field-label">Name</span>
          <input class="input" type="text" wire:model="name">
          @error('name')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        </label>
        <label class="field">
          <span class="field-label">Work email</span>
          <input class="input" type="email" wire:model="email">
          @error('email')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        </label>
        <label class="field">
          <span class="field-label">Role</span>
          <select class="select" wire:model="role">
            @foreach ($roles as $r)<option value="{{ $r }}">{{ ucfirst($r) }}</option>@endforeach
          </select>
        </label>
        <label class="field">
          <span class="field-label">Temporary password</span>
          <input class="input" type="text" wire:model="password">
          @error('password')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
        </label>
        <button class="btn" type="submit">Add</button>
      </form>
    @else
      <div class="band band-info" style="border-radius:var(--radius)">
        <x-icon name="lock" class="band-icon" />
        <div><span class="band-title">Administrators only</span>
          You are signed in as a {{ auth()->user()->role }}. Ask an administrator to add staff.</div>
      </div>
    @endif
  </div></div>

  <div class="label" style="grid-column:1/-1"><div class="label-body">
    <div class="section-title" style="margin-top:0">Audit trail · last 30 entries</div>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Subject</th><th>IP</th></tr></thead>
      <tbody>
        @forelse ($audits as $a)
          <tr>
            <td class="hint">{{ $a->created_at?->format('j M H:i') }}</td>
            <td>{{ $a->user?->name ?? 'system' }}</td>
            <td><span class="badge badge-{{ str_contains($a->action, 'revoked') ? 'caution' : 'info' }}">{{ $a->action }}</span></td>
            <td class="hint mono">{{ class_basename($a->subject_type ?? '') }}{{ $a->subject_id ? ' #'.$a->subject_id : '' }}</td>
            <td class="hint mono">{{ $a->ip }}</td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty">Nothing recorded yet.</div></td></tr>
        @endforelse
      </tbody>
    </table></div>
    <p class="hint" style="margin-top:var(--s3)">
      Append-only. There is deliberately no delete path — {{ \App\Models\Audit::count() }} entries recorded.
    </p>
  </div></div>
</div>
