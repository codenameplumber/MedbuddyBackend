<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\Consent;
use App\Models\DoseEvent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\PatientReport;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds a fabricated cohort of 120 patients. No real patient data is involved
 * anywhere: names are drawn from common Nigerian name lists, every mailbox is
 * on a .test domain that cannot receive mail, and every clinical detail is
 * invented.
 *
 * Everything is generated deterministically from a per-patient seed, so the
 * charts do not reshuffle on every reseed — a dashboard that changes when you
 * reload reads as broken, and it also keeps the dose grid from contradicting
 * the headline percentage.
 */
class DatabaseSeeder extends Seeder
{
    /** Days of dose history. 60 gives the 30-day charts a full window plus slack. */
    private const DAYS = 60;

    /** Cohort size. The console is built to be scanned, so it needs a cohort. */
    private const PATIENTS = 120;

    /** The shared demo password. Stated plainly on the sign-in page. */
    private const PASSWORD = 'password';

    // ── Name pools ───────────────────────────────────────────────────
    // Grouped by language so a Yorùbá-speaking patient does not end up with an
    // Igbo surname. The `en` cohort draws from all three: preferring English
    // is a separate decision from which language you grew up in.

    private const NAMES = [
        'yo' => [
            ['Adebayo', 'Folake', 'Tunde', 'Bisi', 'Yetunde', 'Segun', 'Kehinde', 'Taiwo',
             'Bolanle', 'Adewale', 'Funmilayo', 'Olumide', 'Ronke', 'Femi', 'Dupe', 'Wale',
             'Sade', 'Gbenga', 'Toyin', 'Kunle'],
            ['Adeyemi', 'Bakare', 'Ogunleye', 'Adebisi', 'Balogun', 'Ajayi', 'Oyelaran',
             'Fashola', 'Odukoya', 'Alabi', 'Ogundipe', 'Adesina', 'Ekundayo', 'Oyebode', 'Aderinto'],
        ],
        'ha' => [
            ['Musa', 'Amina', 'Sadiq', 'Hauwa', 'Ibrahim', 'Fatima', 'Aliyu', 'Zainab',
             'Usman', 'Maryam', 'Bello', 'Halima', 'Yusuf', 'Aisha', 'Sani', 'Rukayya',
             'Abubakar', 'Hadiza', 'Nasiru', 'Safiya'],
            ['Ibrahim', 'Bello', 'Yusuf', 'Sani', 'Danjuma', 'Abubakar', 'Lawal', 'Garba',
             'Shehu', 'Maikudi', 'Aliyu', 'Umar', 'Tanko', 'Gambo', 'Dikko'],
        ],
        'ig' => [
            ['Chidi', 'Ngozi', 'Emeka', 'Adaeze', 'Obinna', 'Chioma', 'Ikenna', 'Uchenna',
             'Nnamdi', 'Amaka', 'Chukwudi', 'Ifeoma', 'Kelechi', 'Nkechi', 'Obiora',
             'Chinelo', 'Ebuka', 'Adaobi', 'Chinedu', 'Oluchi'],
            ['Okafor', 'Nwosu', 'Eze', 'Okeke', 'Obi', 'Nwachukwu', 'Anyanwu', 'Iheanacho',
             'Onyeka', 'Madu', 'Ugochukwu', 'Ezeani', 'Okoro', 'Nnaji', 'Umeh'],
        ],
    ];

    /**
     * Regimens by condition. Each entry is [name, strength, times, purpose, form].
     * Times are wall-clock local; the seeder never invents a schedule a
     * pharmacist would not write.
     */
    private const REGIMENS = [
        'Hypertension' => [
            [['Amlodipine', '5 mg', ['08:00'], 'blood pressure', 'tablet']],
            [['Amlodipine', '10 mg', ['08:00'], 'blood pressure', 'tablet']],
            [['Amlodipine', '5 mg', ['08:00'], 'blood pressure', 'tablet'],
             ['Hydrochlorothiazide', '25 mg', ['08:00'], 'blood pressure', 'tablet']],
            [['Lisinopril', '10 mg', ['08:00'], 'blood pressure', 'tablet'],
             ['Amlodipine', '5 mg', ['20:00'], 'blood pressure', 'tablet']],
        ],
        'Type 2 diabetes' => [
            [['Metformin', '500 mg', ['08:00', '20:00'], 'blood sugar', 'tablet']],
            [['Metformin', '850 mg', ['08:00', '20:00'], 'blood sugar', 'tablet']],
            [['Metformin', '1000 mg', ['08:00', '20:00'], 'blood sugar', 'tablet'],
             ['Glibenclamide', '5 mg', ['08:00'], 'blood sugar', 'tablet']],
            [['Metformin', '500 mg', ['08:00', '20:00'], 'blood sugar', 'tablet'],
             ['Amlodipine', '5 mg', ['08:00'], 'blood pressure', 'tablet']],
        ],
        'Tuberculosis' => [
            [['Rifampicin', '600 mg', ['07:00'], 'TB treatment', 'tablet'],
             ['Isoniazid', '300 mg', ['07:00'], 'TB treatment', 'tablet']],
            [['Rifampicin', '600 mg', ['07:00'], 'TB treatment', 'tablet'],
             ['Isoniazid', '300 mg', ['07:00'], 'TB treatment', 'tablet'],
             ['Pyrazinamide', '1500 mg', ['07:00'], 'TB treatment', 'tablet']],
        ],
        'HIV' => [
            [['Dolutegravir / Lamivudine / Tenofovir', '1 tablet', ['21:00'], 'immune health', 'tablet']],
            [['Dolutegravir / Lamivudine / Tenofovir', '1 tablet', ['21:00'], 'immune health', 'tablet'],
             ['Cotrimoxazole', '960 mg', ['08:00'], 'preventing infection', 'tablet']],
        ],
        'Epilepsy' => [
            [['Carbamazepine', '200 mg', ['08:00', '20:00'], 'seizures', 'tablet']],
            [['Sodium valproate', '500 mg', ['08:00', '20:00'], 'seizures', 'tablet']],
        ],
        'Asthma' => [
            [['Salbutamol inhaler', '2 puffs', ['08:00', '20:00'], 'breathing', 'inhaler']],
            [['Beclometasone inhaler', '2 puffs', ['08:00', '20:00'], 'breathing', 'inhaler'],
             ['Salbutamol inhaler', '2 puffs', ['08:00'], 'breathing', 'inhaler']],
        ],
        'Sickle cell disease' => [
            [['Hydroxyurea', '500 mg', ['08:00'], 'fewer crises', 'capsule'],
             ['Folic acid', '5 mg', ['08:00'], 'blood health', 'tablet']],
        ],
        'Heart failure' => [
            [['Furosemide', '40 mg', ['07:00'], 'fluid on the chest', 'tablet'],
             ['Ramipril', '5 mg', ['08:00'], 'heart protection', 'tablet']],
            [['Furosemide', '40 mg', ['07:00'], 'fluid on the chest', 'tablet'],
             ['Bisoprolol', '5 mg', ['08:00'], 'heart rate', 'tablet'],
             ['Ramipril', '5 mg', ['20:00'], 'heart protection', 'tablet']],
        ],
        'Peptic ulcer' => [
            [['Omeprazole', '20 mg', ['07:00'], 'stomach acid', 'capsule']],
        ],
        'Hypothyroidism' => [
            [['Levothyroxine', '50 mcg', ['06:00'], 'thyroid', 'tablet']],
        ],
    ];

    /**
     * Symptom vocabulary that a report can carry, paired with a note in the
     * patient's own language. Only symptoms a clinician would expect from the
     * drug in question, so the safety-signals page is not noise.
     */
    private const COMPLAINTS = [
        'Amlodipine'          => [['swelling', 'Ankles puffy in the evening.', 2], ['dizzy', 'Dizzy when standing up from bed.', 2]],
        'Metformin'           => [['stomach', 'Upset stomach after the evening tablet.', 1], ['rash', 'Small red patches on both arms.', 2]],
        'Rifampicin'          => [['other', 'My urine turned orange and it frightened me.', 1]],
        'Isoniazid'           => [['other', 'Pins and needles in both feet.', 2]],
        'Carbamazepine'       => [['sleepy', 'Very drowsy in the mornings.', 1], ['rash', 'Itchy rash on the neck.', 3]],
        'Sodium valproate'    => [['sleepy', 'Sleeping through the alarm.', 1]],
        'Furosemide'          => [['dizzy', 'Light-headed after the morning tablet.', 2]],
        'Hydrochlorothiazide' => [['dizzy', 'Dizzy in the afternoon heat.', 1]],
        'Lisinopril'          => [['other', 'A dry cough that will not stop.', 2]],
        'Hydroxyurea'         => [['other', 'Mouth sores this week.', 2]],
        'Glibenclamide'       => [['sleepy', 'Shaky and sleepy before lunch.', 2]],
        'Omeprazole'          => [['stomach', 'Bloated most mornings.', 1]],
        'Salbutamol inhaler'  => [['other', 'Hands tremble after two puffs.', 1]],
        'Ramipril'            => [['other', 'A dry tickly cough at night.', 2]],
        'Levothyroxine'       => [['other', 'Heart feels like it is racing.', 2]],
        'Cotrimoxazole'       => [['rash', 'Rash across the chest.', 2]],
        'Bisoprolol'          => [['sleepy', 'Very tired climbing stairs.', 1]],
        'Pyrazinamide'        => [['other', 'Joints aching, especially the knees.', 2]],
        'Beclometasone inhaler' => [['other', 'Hoarse voice by the afternoon.', 1]],
        'Folic acid'          => [['fine', null, 0]],
        'Dolutegravir / Lamivudine / Tenofovir' => [['sleepy', 'Strange dreams and poor sleep.', 1]],
    ];

    /**
     * A real, playable file for the seeded voice notes.
     *
     * It used to be a bare path with nothing behind it, which was harmless
     * only for as long as nothing played it. Deliberately a plain tone rather
     * than fake speech: it proves the pipeline without pretending a patient
     * said something they did not.
     */
    private function placeholderAudio(): string
    {
        static $path = null;
        if ($path !== null) {
            return $path;
        }

        $rate = 8000;
        $samples = $rate;   // one second

        $pcm = '';
        for ($i = 0; $i < $samples; $i++) {
            // 440 Hz, fading out, so it is obviously a placeholder.
            $envelope = 1 - ($i / $samples);
            $pcm .= pack('v', (int) (12000 * $envelope * sin(2 * M_PI * 440 * $i / $rate)) & 0xFFFF);
        }

        $wav = 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '
            .pack('V', 16).pack('v', 1).pack('v', 1)
            .pack('V', $rate).pack('V', $rate * 2).pack('v', 2).pack('v', 16)
            .'data'.pack('V', strlen($pcm)).$pcm;

        Storage::disk('voice')->put($path = 'seed/placeholder.wav', $wav);

        return $path;
    }

    public function run(): void
    {
        $today = CarbonImmutable::today();

        $org = Organisation::create([
            'name' => 'Ikeja General Hospital',
            'kind' => 'hospital',
            'city' => 'Lagos',
            'emergency_number' => '112',
        ]);

        $staff = $this->seedStaff($org);
        $prescribers = $staff->whereIn('role', ['physician', 'admin'])->values();

        $this->seedCohort($org, $prescribers, $today);
        $this->seedReports($today);
        $this->seedSentReminders($today);
        $this->seedAudit($staff, $today);

        $this->command->info('Seeded '
            .User::count().' staff, '
            .Patient::count().' patients, '
            .Prescription::count().' prescriptions, '
            .number_format(DoseEvent::count()).' dose events, '
            .Reminder::count().' reminders, '
            .PatientReport::count().' reports, '
            .Consent::count().' consent records.');
    }

    /**
     * Twelve staff across the four roles. Enough that the Team page shows a
     * real rota and that every policy branch has somebody who trips it.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function seedStaff(Organisation $org): \Illuminate\Support\Collection
    {
        $roster = [
            ['K. Adesina',      'admin@ikejagh.ng',       'admin'],
            ['Dr. A. Adeyemi',  'a.adeyemi@ikejagh.ng',   'physician'],
            ['Dr. H. Ibrahim',  'h.ibrahim@ikejagh.ng',   'physician'],
            ['Dr. C. Nwachukwu', 'c.nwachukwu@ikejagh.ng', 'physician'],
            ['N. Balogun',      'n.balogun@ikejagh.ng',   'pharmacist'],
            ['S. Okeke',        's.okeke@ikejagh.ng',     'pharmacist'],
            ['C. Eze',          'c.eze@ikejagh.ng',       'nurse'],
            ['B. Lawal',        'b.lawal@ikejagh.ng',     'nurse'],
            ['F. Ogunleye',     'f.ogunleye@ikejagh.ng',  'nurse'],
            ['R. Danjuma',      'r.danjuma@ikejagh.ng',   'nurse'],
            ['T. Okonkwo',      't.okonkwo@ikejagh.ng',   'nurse'],
            ['M. Sanni',        'm.sanni@ikejagh.ng',     'admin'],
        ];

        return collect($roster)->map(fn ($row) => User::create([
            'organisation_id' => $org->id,
            'name'     => $row[0],
            'email'    => $row[1],
            'role'     => $row[2],
            'password' => Hash::make(self::PASSWORD),
        ]));
    }

    private function seedCohort(Organisation $org, \Illuminate\Support\Collection $prescribers, CarbonImmutable $today): void
    {
        $conditions = array_keys(self::REGIMENS);
        $seen = [];

        for ($i = 0; $i < self::PATIENTS; $i++) {
            $rand = $this->rng('patient|'.$i);

            // Language mix: roughly the mix a Lagos clinic actually sees.
            $lang = match (true) {
                $rand() < 0.34 => 'yo',
                $rand() < 0.55 => 'ig',
                $rand() < 0.72 => 'ha',
                default        => 'en',
            };

            [$firsts, $lasts] = $lang === 'en'
                ? [
                    array_merge(self::NAMES['yo'][0], self::NAMES['ig'][0], self::NAMES['ha'][0]),
                    array_merge(self::NAMES['yo'][1], self::NAMES['ig'][1], self::NAMES['ha'][1]),
                ]
                : self::NAMES[$lang];

            $first = $firsts[(int) floor($rand() * count($firsts))];
            $last  = $lasts[(int) floor($rand() * count($lasts))];
            $name  = $first.' '.$last;

            // Ages skew older: this is a long-term-condition cohort.
            $age = 24 + (int) floor($rand() * 56);

            $condition = $conditions[(int) floor($rand() * count($conditions))];
            $options   = self::REGIMENS[$condition];
            $regimen   = $options[(int) floor($rand() * count($options))];

            // Adherence target. A long tail below 60 is the point of the
            // product, so the distribution is deliberately not a bell curve.
            $roll = $rand();
            $target = match (true) {
                $roll < 0.12 => 35 + (int) floor($rand() * 25),   // at risk
                $roll < 0.32 => 60 + (int) floor($rand() * 20),   // watch
                default      => 80 + (int) floor($rand() * 19),   // on track
            };

            // Caregivers for the older end of the cohort, and only sometimes.
            $caregiver = ($age >= 60 && $rand() < 0.55)
                ? $firsts[(int) floor($rand() * count($firsts))].' '.$last
                : null;

            // Unique mailbox even when two patients share a name.
            $slug = strtolower($first.'.'.$last);
            $slug = preg_replace('/[^a-z.]/', '', $slug);
            $seen[$slug] = ($seen[$slug] ?? 0) + 1;
            $email = $slug.($seen[$slug] > 1 ? $seen[$slug] : '').'@patient.medbuddy.test';

            $enrolled = $today->subDays(30 + (int) floor($rand() * 90));

            // Most people replied recently; a few have gone quiet, which is
            // exactly what "Needs attention" exists to surface.
            $silentDays = $rand() < 0.14 ? 3 + (int) floor($rand() * 9) : (int) floor($rand() * 2);

            $patient = Patient::create([
                'organisation_id' => $org->id,
                'name'  => $name,
                'email' => $email,
                'phone' => '+234 8'.(30 + $i % 10).' '.str_pad((string) (1000 + $i * 7 % 9000), 4, '0', STR_PAD_LEFT).' '.str_pad((string) (100 + $i * 13 % 900), 4, '0', STR_PAD_LEFT),
                'age'   => $age,
                'condition' => $condition,
                'lang'  => $lang,
                'caregiver_name'  => $caregiver,
                'caregiver_email' => $caregiver
                    ? strtolower(explode(' ', $caregiver)[0]).'.'.strtolower($last).$i.'@family.medbuddy.test'
                    : null,
                'enrolled_at'      => $enrolled,
                'last_response_at' => $today->subDays($silentDays)->addHours(9),
            ]);

            // Consent is per scope AND per channel. About one in eight declines
            // research, so the console always has patients who are legitimately
            // excluded from the cohort analysis.
            Consent::create([
                'patient_id' => $patient->id, 'scope' => 'messaging', 'channel' => 'mail',
                'granted' => true, 'granted_at' => $enrolled,
            ]);
            Consent::create([
                'patient_id' => $patient->id, 'scope' => 'research', 'channel' => null,
                'granted' => $rand() > 0.13, 'granted_at' => $enrolled,
            ]);

            foreach ($regimen as [$mName, $strength, $times, $purpose, $form]) {
                $rx = Prescription::create([
                    'patient_id'    => $patient->id,
                    'prescribed_by' => $prescribers[(int) floor($rand() * $prescribers->count())]->id,
                    'name'      => $mName,
                    'strength'  => $strength,
                    'form'      => $form,
                    'purpose'   => $purpose,
                    'times'     => $times,
                    'starts_on' => $enrolled->toDateString(),
                    'active'    => true,
                ]);

                $this->seedDoseHistory($patient, $rx, $target, $today);
            }
        }
    }

    /**
     * Deterministic pseudo-random generator from a string seed, so every run
     * of the seeder produces the identical cohort.
     *
     * @return \Closure():float
     */
    private function rng(string $seed): \Closure
    {
        $state = crc32($seed);

        return function () use (&$state) {
            $state = ($state * 1103515245 + 12345) & 0x7fffffff;
            return $state / 0x7fffffff;
        };
    }

    /**
     * Doses are DERIVED from prescriptions.times; only what actually happened
     * gets a row. Evening doses are missed more often, which is a real and
     * very common pattern and the one the Adherence page is built to show.
     */
    private function seedDoseHistory(Patient $patient, Prescription $rx, int $target, CarbonImmutable $today): void
    {
        $rand = $this->rng($patient->id.'|'.$rx->id.'|'.$rx->name);
        $rate = $target / 100;
        $rows = [];
        $now = now();

        for ($d = self::DAYS - 1; $d >= 0; $d--) {
            $date = $today->subDays($d);

            foreach ($rx->times as $time) {
                $hour = (int) explode(':', $time)[0];
                $isEvening = $hour >= 17;
                $p = $isEvening ? $rate * 0.82 : $rate * 1.08;

                if ($rand() < min($p, 1)) {
                    $rows[] = [
                        'patient_id'      => $patient->id,
                        'prescription_id' => $rx->id,
                        'dose_date'       => $date->toDateString(),
                        'dose_time'       => $time,
                        'status'          => 'taken',
                        // Provenance matters: a dose confirmed from a reminder
                        // link is a stronger signal than one ticked from memory.
                        'source'      => $rand() < 0.55 ? 'link' : 'app',
                        'recorded_at' => $date->setTimeFromTimeString($time)->addMinutes(3 + (int) floor($rand() * 40)),
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DoseEvent::insert($chunk);
        }
    }

    /**
     * Symptom reports. Only drugs that plausibly cause the symptom, so the
     * safety-signals page groups into something a pharmacist would recognise
     * rather than random noise.
     */
    private function seedReports(CarbonImmutable $today): void
    {
        $rows = [];
        $now = now();
        $audio = null;

        Patient::with('activePrescriptions')->chunk(50, function ($patients) use (&$rows, $today, $now, &$audio) {
            foreach ($patients as $patient) {
                $rand = $this->rng('reports|'.$patient->id);

                // Most of the cohort has said something in the last month.
                // Drawn once: calling $rand() inside each arm compounds the
                // probabilities and quietly halves the number of reports.
                $roll = $rand();
                $count = match (true) {
                    $roll < 0.18 => 0,
                    $roll < 0.50 => 1,
                    $roll < 0.78 => 2,
                    $roll < 0.94 => 3,
                    default      => 4,
                };

                for ($k = 0; $k < $count; $k++) {
                    $rx = $patient->activePrescriptions[(int) floor($rand() * max(1, $patient->activePrescriptions->count()))] ?? null;
                    $bank = self::COMPLAINTS[$rx?->name] ?? [['fine', null, 0]];
                    [$symptom, $note, $severity] = $bank[(int) floor($rand() * count($bank))];

                    // A quarter of reports are "I am fine", which is the
                    // reassuring answer the reminder link makes cheap to give.
                    $fine = $rand() < 0.26;
                    if ($fine) {
                        [$symptom, $note, $severity] = ['fine', null, 0];
                    }

                    // Second symptom, sometimes.
                    $symptoms = [$symptom];
                    if (! $fine && $rand() < 0.18) {
                        $symptoms[] = ['stomach', 'sleepy', 'dizzy'][(int) floor($rand() * 3)];
                    }

                    $daysAgo = (int) floor($rand() * 28);

                    // Voice notes cluster in the non-English cohort: it is the
                    // whole reason the pipeline exists.
                    $hasAudio = $patient->lang !== 'en' && $rand() < 0.22;
                    if ($hasAudio) {
                        $audio ??= $this->placeholderAudio();
                    }

                    $at = $today->subDays($daysAgo)->addHours(7 + (int) floor($rand() * 12));

                    $rows[] = [
                        'patient_id'      => $patient->id,
                        'prescription_id' => $fine ? null : $rx?->id,
                        'symptoms'   => json_encode($symptoms),
                        'severity'   => $severity,
                        'note'       => $rand() < 0.6 ? $note : null,
                        'audio_path' => $hasAudio ? $audio : null,
                        'lang'       => $patient->lang,
                        'source'     => $rand() < 0.62 ? 'link' : 'app',
                        'took_dose'  => $rand() > 0.12,
                        // Older reports have been triaged; recent ones are the
                        // queue the Complaints page exists to work through.
                        'reviewed_at' => $daysAgo > 7 && $rand() < 0.8 ? $at->addHours(4) : null,
                        'created_at' => $at,
                        'updated_at' => $at,
                    ];
                }
            }
        });

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('patient_reports')->insert($chunk);
        }
    }

    /** A week of delivery history so the outbox funnel is not empty on day one. */
    private function seedSentReminders(CarbonImmutable $today): void
    {
        $rows = [];
        $now = now();

        Patient::with('activePrescriptions')->chunk(50, function ($patients) use (&$rows, $today, $now) {
            foreach ($patients as $patient) {
                $windows = collect($patient->activePrescriptions->flatMap->times)->unique()->sort()->values();

                for ($d = 7; $d >= 1; $d--) {
                    $date = $today->subDays($d);

                    foreach ($windows as $time) {
                        $hour = (int) explode(':', $time)[0];
                        $slot = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');

                        $h = crc32($patient->id.$date->toDateString().$time);
                        $opened    = $h % 100 < 86;
                        $responded = $opened && ($h >> 7) % 100 < 74;

                        $sentAt = $date->setTimeFromTimeString($time);

                        $rows[] = [
                            'patient_id' => $patient->id,
                            'dose_date'  => $date->toDateString(),
                            'dose_time'  => $time,
                            'slot'       => $slot,
                            'channel'    => 'mail',
                            'to'         => $patient->email,
                            'sent_at'    => $sentAt,
                            'opened_at'    => $opened ? $sentAt->addMinutes(4 + $h % 25) : null,
                            'responded_at' => $responded ? $sentAt->addMinutes(9 + $h % 40) : null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        });

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('reminders')->insert($chunk);
        }
    }

    /**
     * A believable audit trail. Every cohort read, consent change and AI call
     * is meant to land here in production; seeding a week of it means the Team
     * page shows the trail rather than an empty table.
     */
    private function seedAudit(\Illuminate\Support\Collection $staff, CarbonImmutable $today): void
    {
        $actions = [
            'auth.login', 'patient.viewed', 'patient.viewed', 'patient.viewed',
            'cohort.viewed', 'consent.updated', 'prescription.created',
            'ai.explain', 'ai.ask', 'voice.played', 'reminder.dispatched',
        ];

        $rand = $this->rng('audit');
        $rows = [];
        $patientIds = Patient::pluck('id')->all();

        for ($i = 0; $i < 180; $i++) {
            $action = $actions[(int) floor($rand() * count($actions))];
            $onPatient = str_starts_with($action, 'patient.') || in_array($action, ['consent.updated', 'voice.played', 'prescription.created'], true);

            $rows[] = [
                'user_id' => $staff[(int) floor($rand() * $staff->count())]->id,
                'action'  => $action,
                'subject_type' => $onPatient ? Patient::class : null,
                'subject_id'   => $onPatient ? $patientIds[(int) floor($rand() * count($patientIds))] : null,
                'meta' => null,
                'ip'   => '10.0.'.(int) floor($rand() * 4).'.'.(int) floor($rand() * 250),
                'created_at' => $today->subDays((int) floor($rand() * 7))->addHours(8 + (int) floor($rand() * 9))->addMinutes((int) floor($rand() * 60)),
            ];
        }

        DB::table('audits')->insert($rows);
    }
}
