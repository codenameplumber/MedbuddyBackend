<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MedBuddy core schema.
 *
 * Design note carried over from the prototype: doses are DERIVED from
 * prescriptions.times, not materialised. Only deviations (taken / skipped)
 * get a dose_events row. Keeps the table small and makes adherence a pure
 * function over events rather than a second source of truth to keep in sync.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organisations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('kind')->default('hospital');   // hospital | pharmacy
            $t->string('city')->nullable();
            $t->string('emergency_number')->default('112');
            $t->timestamps();
        });

        Schema::create('patients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('organisation_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable()->index();
            $t->string('phone')->nullable();
            $t->unsignedSmallInteger('age')->nullable();
            $t->string('condition')->nullable();
            $t->string('lang', 5)->default('en');       // en | yo | ha | ig
            $t->string('caregiver_name')->nullable();
            $t->string('caregiver_email')->nullable();
            $t->timestamp('enrolled_at')->nullable();
            $t->timestamp('last_response_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('prescriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('prescribed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('name');
            $t->string('generic')->nullable();
            $t->string('strength')->nullable();
            $t->string('form')->default('tablet');
            $t->string('purpose')->nullable();
            $t->json('times');                           // ["08:00","20:00"]
            $t->json('days')->nullable();                // [0..6], null = every day
            $t->date('starts_on')->nullable();
            $t->date('ends_on')->nullable();
            $t->json('ai_summary')->nullable();          // cached explanation
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->index(['patient_id', 'active']);
        });

        Schema::create('dose_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $t->date('dose_date');
            $t->string('dose_time', 5);                  // "08:00"
            $t->string('status')->default('taken');      // taken | skipped
            // Provenance matters: a dose confirmed from a reminder link is a
            // stronger signal than one ticked from memory three days later.
            $t->string('source')->default('app');        // app | link | staff
            $t->timestamp('recorded_at');
            $t->timestamps();
            $t->unique(['prescription_id', 'dose_date', 'dose_time'], 'dose_unique');
            $t->index(['patient_id', 'dose_date']);
        });

        Schema::create('reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->date('dose_date');
            $t->string('dose_time', 5);
            $t->string('slot');                          // morning | afternoon | evening
            $t->string('channel')->default('mail');      // mail | sms | whatsapp
            $t->string('to')->nullable();
            $t->text('body')->nullable();                // exactly what was sent
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('opened_at')->nullable();
            $t->timestamp('responded_at')->nullable();
            $t->string('error')->nullable();
            $t->timestamps();
            // One message per patient per dose window. Four separate emails at
            // 08:00 would train people to ignore all of them.
            $t->unique(['patient_id', 'dose_date', 'dose_time'], 'reminder_unique');
        });

        Schema::create('patient_reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $t->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('reminder_id')->nullable()->constrained()->nullOnDelete();
            $t->json('symptoms');                        // ["dizzy","rash"]
            $t->unsignedTinyInteger('severity')->default(0);  // 0..3
            $t->text('note')->nullable();
            $t->string('audio_path')->nullable();
            $t->string('lang', 5)->default('en');
            $t->string('source')->default('app');        // app | link
            $t->boolean('took_dose')->nullable();
            $t->string('triage_category')->nullable();   // set if the note tripped triage
            $t->timestamp('reviewed_at')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['patient_id', 'created_at']);
        });

        Schema::create('consents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->cascadeOnDelete();
            // Separate decisions on purpose: someone may accept reminders and
            // still keep their reports out of the cohort.
            $t->string('scope');                         // messaging | research
            $t->string('channel')->nullable();           // mail | sms | whatsapp
            $t->boolean('granted')->default(false);
            $t->timestamp('granted_at')->nullable();
            $t->timestamp('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['patient_id', 'scope', 'channel'], 'consent_unique');
        });

        Schema::create('audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('meta')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['subject_type', 'subject_id']);
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('organisation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $t->string('role')->default('nurse')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('organisation_id');
            $t->dropColumn('role');
        });

        foreach ([
            'audits', 'consents', 'patient_reports', 'reminders',
            'dose_events', 'prescriptions', 'patients', 'organisations',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
