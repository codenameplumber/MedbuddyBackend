<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PatientReport;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Derived cohort views. Every clinic page asks for exactly the shape it
 * renders, so no page re-implements a calculation.
 */
class AnalyticsService
{
    public function __construct(
        private ScheduleService $schedule,
        private AdherenceService $adherence,
    ) {}

    /**
     * Memoised for the life of this instance. Livewire resolves a fresh
     * AnalyticsService per render, so a component that writes and re-renders
     * still sees its own change — but a page that asks twice (Patients wants
     * both the rows and the unfiltered total) only folds once.
     */
    private ?Collection $cohort = null;

    /** @return Collection<int, Patient> patients with an attention verdict attached */
    public function cohort(): Collection
    {
        if ($this->cohort !== null) {
            return $this->cohort;
        }

        $patients = Patient::with(['prescriptions', 'organisation'])->get();

        // One query for every patient's dose history, instead of one per
        // patient per day. Without this the fold below is ~4,500 queries.
        $this->schedule->warm($patients);

        // And one for the recent severe reports, instead of an exists() per
        // patient inside attention().
        $severe = PatientReport::where('severity', '>=', config('medbuddy.severity_concern', 2))
            ->where('created_at', '>=', now()->subDays(3))
            ->distinct()
            ->pluck('patient_id')
            ->flip();

        return $this->cohort = $patients->each(function (Patient $p) use ($severe) {
            $verdict = $this->adherence->attention($p, $severe->has($p->id));
            $p->setAttribute('attention', $verdict);
            $p->setAttribute('adherence7', $verdict['rate']);
            $p->setAttribute('adherence30', $this->adherence->rate($p, 30) ?? 0);
        });
    }

    /** Cohort adherence per day, averaged across every patient. */
    public function adherenceTrend(int $days = 30): array
    {
        $patients = Patient::with('prescriptions')->get();
        $this->schedule->warm($patients);
        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = CarbonImmutable::today()->subDays($i);
            $taken = 0;
            $total = 0;

            foreach ($patients as $patient) {
                foreach ($this->schedule->dosesForDay($patient, $date) as $dose) {
                    $total++;
                    if ($dose['status'] === 'taken') {
                        $taken++;
                    }
                }
            }

            $labels[] = $date->format('j M');
            $values[] = $total ? (int) round($taken / $total * 100) : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Adherence per dose window.
     *
     * READ WITH CARE: each bar is a different set of people. A window taken by
     * one struggling patient and a window taken by one excellent patient
     * compares PEOPLE, not times of day. For the comparison that actually
     * means something, use AdherenceService::morningVsEvening(), which pairs
     * within a patient.
     */
    public function doseTimeHeat(int $days = 30): array
    {
        $buckets = [];
        $patients = Patient::with('prescriptions')->get();
        $this->schedule->warm($patients);

        for ($i = 0; $i < $days; $i++) {
            $date = CarbonImmutable::today()->subDays($i);
            foreach ($patients as $patient) {
                foreach ($this->schedule->dosesForDay($patient, $date) as $dose) {
                    $t = $dose['time'];
                    $buckets[$t] ??= ['taken' => 0, 'total' => 0];
                    $buckets[$t]['total']++;
                    if ($dose['status'] === 'taken') {
                        $buckets[$t]['taken']++;
                    }
                }
            }
        }

        ksort($buckets);

        return collect($buckets)->map(fn ($b, $time) => [
            'label' => $time,
            'value' => $b['total'] ? $b['taken'] / $b['total'] : 0,
            'pct'   => $b['total'] ? (int) round($b['taken'] / $b['total'] * 100) : 0,
        ])->values()->all();
    }

    /** Cohort split by adherence band. */
    public function adherenceBands(?Collection $cohort = null): array
    {
        $cohort ??= $this->cohort();
        $counts = ['safe' => 0, 'caution' => 0, 'alert' => 0];

        foreach ($cohort as $p) {
            $r = $p->adherence7;
            $counts[$r < 60 ? 'alert' : ($r < 80 ? 'caution' : 'safe')]++;
        }

        return [
            ['label' => 'On track (80%+)', 'value' => $counts['safe'],    'tone' => 'safe'],
            ['label' => 'Watch (60-79%)',  'value' => $counts['caution'], 'tone' => 'caution'],
            ['label' => 'At risk (<60%)',  'value' => $counts['alert'],   'tone' => 'alert'],
        ];
    }

    /** Every medicine in the cohort, rolled up. Powers the formulary page. */
    public function byDrug(): array
    {
        $map = [];
        $cohort = $this->cohort();

        foreach ($cohort as $patient) {
            foreach ($patient->prescriptions->where('active', true) as $rx) {
                $g = &$map[$rx->name];
                $g ??= [
                    'name' => $rx->name, 'strengths' => [], 'patients' => [],
                    'conditions' => [], 'doses' => 0, 'adherenceSum' => 0,
                    'complaints' => 0, 'complaintPatients' => [], 'maxSeverity' => 0,
                ];
                $g['strengths'][$rx->strength] = true;
                $g['patients'][$patient->id] = true;
                $g['conditions'][$patient->condition] = true;
                $g['doses'] += $rx->dosesPerDay();
                $g['adherenceSum'] += $patient->adherence7;
                unset($g);
            }
        }

        foreach (PatientReport::with('prescription')->get() as $report) {
            $name = $report->prescription?->name;
            if (! $name || ! isset($map[$name]) || ! $report->isComplaint()) {
                continue;
            }
            $map[$name]['complaints']++;
            $map[$name]['complaintPatients'][$report->patient_id] = true;
            $map[$name]['maxSeverity'] = max($map[$name]['maxSeverity'], $report->severity);
        }

        $rows = [];
        foreach ($map as $g) {
            $n = count($g['patients']);
            $rows[] = [
                'name'          => $g['name'],
                'strength'      => implode(', ', array_keys($g['strengths'])),
                'patients'      => $n,
                'conditions'    => implode(', ', array_keys($g['conditions'])),
                'dosesPerDay'   => $n ? round($g['doses'] / $n, 1) : 0,
                'adherence'     => $n ? (int) round($g['adherenceSum'] / $n) : 0,
                'complaints'    => $g['complaints'],
                'complaintRate' => $n ? (int) round(count($g['complaintPatients']) / $n * 100) : 0,
                'maxSeverity'   => $g['maxSeverity'],
            ];
        }

        usort($rows, fn ($a, $b) => $b['patients'] <=> $a['patients']);

        return $rows;
    }

    /**
     * Adverse-event signal detection.
     *
     * This is a COUNT, grouped by drug and reported symptom. It is not
     * evidence of causation: the cohort is small, self-selected and unblinded,
     * and a patient reporting a symptom while on a drug is not the drug
     * causing it. The UI states this next to the table.
     */
    public function signals(): array
    {
        $exposure = [];
        foreach ($this->cohort() as $patient) {
            foreach ($patient->prescriptions->where('active', true) as $rx) {
                $exposure[$rx->name][$patient->id] = true;
            }
        }

        $groups = [];
        foreach (PatientReport::with('prescription')->get() as $report) {
            $drug = $report->prescription?->name;
            if (! $drug || ! $report->isComplaint()) {
                continue;
            }

            foreach ($report->symptoms as $symptom) {
                $key = $drug.'|'.$symptom;
                $groups[$key] ??= [
                    'drug' => $drug, 'symptom' => $symptom,
                    'reports' => 0, 'patients' => [], 'maxSeverity' => 0,
                ];
                $groups[$key]['reports']++;
                $groups[$key]['patients'][$report->patient_id] = true;
                $groups[$key]['maxSeverity'] = max($groups[$key]['maxSeverity'], $report->severity);
            }
        }

        $rows = [];
        foreach ($groups as $g) {
            $exposed = count($exposure[$g['drug']] ?? []) ?: 1;
            $affected = count($g['patients']);
            $rows[] = [
                'drug' => $g['drug'], 'symptom' => $g['symptom'],
                'reports' => $g['reports'], 'affected' => $affected,
                'exposed' => $exposed, 'rate' => (int) round($affected / $exposed * 100),
                'maxSeverity' => $g['maxSeverity'],
            ];
        }

        usort($rows, fn ($a, $b) => [$b['affected'], $b['reports']] <=> [$a['affected'], $a['reports']]);

        return $rows;
    }

    /** Is any of this working? Read with the caveat the page renders above it. */
    public function outcomes(): array
    {
        $cohort = $this->cohort();

        $reportCounts = PatientReport::selectRaw('patient_id, count(*) as n')
            ->groupBy('patient_id')->pluck('n', 'patient_id');

        // Documented assumption, not a measurement. Nobody was tracking these
        // patients before they enrolled.
        $baseline = 52;

        $byCondition = [];
        $byChannel = [];
        $byLang = [];

        foreach ($cohort as $p) {
            $byCondition[$p->condition] ??= ['condition' => $p->condition, 'patients' => 0, 'sum' => 0];
            $byCondition[$p->condition]['patients']++;
            $byCondition[$p->condition]['sum'] += $p->adherence30;

            $byChannel['mail'] ??= ['channel' => 'mail', 'patients' => 0, 'sum' => 0];
            $byChannel['mail']['patients']++;
            $byChannel['mail']['sum'] += $p->adherence7;

            $byLang[$p->lang] ??= ['lang' => $p->lang, 'name' => $p->langName(), 'patients' => 0, 'sum' => 0, 'reports' => 0];
            $byLang[$p->lang]['patients']++;
            $byLang[$p->lang]['sum'] += $p->adherence7;
            $byLang[$p->lang]['reports'] += $reportCounts[$p->id] ?? 0;
        }

        $avg = fn (array $rows, string $key) => collect($rows)->map(fn ($r) => [
            ...$r, 'adherence' => $r['patients'] ? (int) round($r['sum'] / $r['patients']) : 0,
        ])->sortByDesc('adherence')->values()->all();

        $now = $cohort->count() ? (int) round($cohort->sum('adherence30') / $cohort->count()) : 0;

        return [
            'baseline'   => $baseline,
            'cohortNow'  => $now,
            'lift'       => $now - $baseline,
            'conditions' => $avg($byCondition, 'condition'),
            'channels'   => $avg($byChannel, 'channel'),
            'languages'  => $avg($byLang, 'lang'),
        ];
    }

    /** Reminder delivery funnel. Response rate is the only number that matters. */
    public function deliveryFunnel(int $days = 7): array
    {
        $since = CarbonImmutable::today()->subDays($days);

        $sent      = Reminder::whereNotNull('sent_at')->where('sent_at', '>=', $since)->count();
        $opened    = Reminder::whereNotNull('opened_at')->where('sent_at', '>=', $since)->count();
        $responded = Reminder::whereNotNull('responded_at')->where('sent_at', '>=', $since)->count();

        return [
            ['label' => 'Sent',      'value' => $sent,             'tone' => 'neutral'],
            ['label' => 'Delivered', 'value' => $sent,             'tone' => 'info'],
            ['label' => 'Opened',    'value' => $opened,           'tone' => 'caution'],
            ['label' => 'Responded', 'value' => $responded,        'tone' => 'safe'],
        ];
    }

    public function cohortStats(?Collection $cohort = null): array
    {
        $cohort ??= $this->cohort();
        $funnel = $this->deliveryFunnel();
        $sent = $funnel[0]['value'] ?: 1;

        return [
            'enrolled'       => $cohort->count(),
            'adherence7'     => $cohort->count() ? (int) round($cohort->sum('adherence7') / $cohort->count()) : 0,
            'reportsToday'   => PatientReport::whereDate('created_at', today())->count(),
            'needsAttention' => $cohort->filter(fn ($p) => $p->attention['level'] !== 'ok')->count(),
            'respondRate'    => (int) round($funnel[3]['value'] / $sent * 100),
            'complaintsOpen' => PatientReport::whereNull('reviewed_at')->get()->filter->isComplaint()->count(),
        ];
    }
}
