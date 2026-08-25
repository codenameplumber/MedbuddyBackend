<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PatientAuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RespondController;
use App\Http\Controllers\VoiceNoteController;
use App\Livewire\Clinic;
use App\Livewire\Me;
use Illuminate\Support\Facades\Route;

/*
 | Reminder response.
 |
 | Protected by Laravel's signed-URL middleware rather than a login: the link
 | is scoped to one reminder and expires with its dose window. That is the
 | design decision the whole response rate depends on.
 */
Route::middleware('signed')->group(function () {
    Route::get('/r/{reminder}', [RespondController::class, 'show'])->name('respond.show');
    Route::post('/r/{reminder}', [RespondController::class, 'store'])->name('respond.store');

    // Patient sign-in. The signature IS the credential, for the same reason:
    // this audience churns hard on account creation.
    Route::get('/me/auth/{patient}', [PatientAuthController::class, 'consume'])->name('me.auth');
});

// ── Patient app ──────────────────────────────────────────────────────
Route::get('/me/login', [PatientAuthController::class, 'show'])->name('me.login');
Route::post('/me/login', [PatientAuthController::class, 'send'])->name('me.login.send');
Route::post('/me/logout', [PatientAuthController::class, 'logout'])->name('me.logout');

Route::middleware('patient')->prefix('me')->name('me.')->group(function () {
    Route::get('/',              Me\Today::class)->name('today');
    Route::get('/medicines',     Me\Medicines::class)->name('medicines');

    // Literal segment before the bound one, or "add" is looked up as an id.
    Route::get('/medicines/add', Me\AddMedicine::class)->name('medicine.add');
    Route::get('/medicine/{prescription}', Me\Medicine::class)->name('medicine');
    Route::get('/ask',           Me\Ask::class)->name('ask');
    Route::get('/scan',          Me\Scan::class)->name('scan');
    Route::get('/history',       Me\History::class)->name('history');
    Route::get('/report',        Me\Report::class)->name('report');
    Route::get('/profile',       Me\Profile::class)->name('profile');
    Route::get('/emergency',     [PatientAuthController::class, 'emergency'])->name('emergency');
});

// ── Auth ─────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'show'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

/*
 | Sign-up. One form, two outcomes: a patient or caregiver gets a Patient
 | record and a magic link, a pharmacy or hospital gets an Organisation and
 | its first admin. RegisterController explains why they differ.
 */
Route::get('/register', [RegisterController::class, 'show'])->middleware('guest')->name('register');
Route::post('/register', [RegisterController::class, 'store'])->middleware(['guest', 'throttle:10,60']);

// ── Clinic console ───────────────────────────────────────────────────
Route::middleware('auth')->prefix('clinic')->name('clinic.')->group(function () {
    Route::get('/',            Clinic\Overview::class)->name('overview');
    Route::get('/patients',    Clinic\Patients::class)->name('patients');

    // Literal segments before the bound one, or "new" is looked up as an id.
    Route::get('/patients/new', Clinic\PatientForm::class)->name('patient.create');
    Route::get('/patients/{patient}', Clinic\PatientDetail::class)->name('patient');
    Route::get('/patients/{patient}/edit', Clinic\PatientForm::class)->name('patient.edit');
    Route::get('/patients/{patient}/prescribe/{prescription?}', Clinic\PrescriptionForm::class)->name('prescription.form');
    Route::get('/adherence',   Clinic\Adherence::class)->name('adherence');
    Route::get('/medicines',   Clinic\Medicines::class)->name('medicines');
    Route::get('/complaints',  Clinic\Complaints::class)->name('complaints');
    Route::get('/outcomes',    Clinic\Outcomes::class)->name('outcomes');
    Route::get('/signals',     Clinic\Signals::class)->name('signals');
    Route::get('/outbox',      Clinic\Outbox::class)->name('outbox');
    Route::get('/explain',     Clinic\Explain::class)->name('explain');
    Route::get('/team',        Clinic\Team::class)->name('team');
    Route::get('/settings',    Clinic\Settings::class)->name('settings');
    Route::get('/voice/{report}', VoiceNoteController::class)->name('voice');
});

/*
 | The public front page. It used to redirect straight to the sign-in form,
 | which meant the product had no front door at all — someone sent the link had
 | nowhere to find out what this is before being asked for a password.
 */
Route::get('/', HomeController::class)->name('home');
