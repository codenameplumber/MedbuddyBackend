<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Team extends Component
{
    public string $name = '';
    public string $email = '';
    public string $role = 'nurse';
    public string $password = '';

    public const ROLES = ['admin', 'physician', 'pharmacist', 'nurse'];

    public function save(): void
    {
        $this->authorize('create', User::class);

        $data = $this->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'role'     => ['required', Rule::in(self::ROLES)],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data + [
            'organisation_id' => auth()->user()->organisation_id,
            'password' => Hash::make($data['password']),
        ]);

        Audit::record('user.created', $user, ['role' => $user->role]);
        $this->reset(['name', 'email', 'password']);
        $this->dispatch('toast', message: 'Staff member added.', tone: 'safe');
    }

    public function changeRole(int $id, string $role): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        if (! in_array($role, self::ROLES, true)) {
            return;
        }

        $user->update(['role' => $role]);
        Audit::record('user.role_changed', $user, ['role' => $role]);
        $this->dispatch('toast', message: $user->name.' is now a '.$role.'.', tone: 'safe');
    }

    public function render()
    {
        return view('livewire.clinic.team', [
            'users'  => User::where('organisation_id', auth()->user()->organisation_id)->orderBy('name')->get(),
            'roles'  => self::ROLES,
            'audits' => Audit::with('user')->latest('created_at')->limit(30)->get(),
            'canAdd' => auth()->user()->can('create', User::class),
        ])->title('Team & audit');
    }
}
