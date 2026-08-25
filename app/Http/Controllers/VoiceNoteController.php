<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\PatientReport;
use Illuminate\Support\Facades\Storage;

/**
 * Voice-note playback.
 *
 * Not a public path and not behind storage:link. Every play is checked against
 * the patient policy and written to the audit log, the same as reading the
 * record it belongs to — because that is what listening to it is.
 */
class VoiceNoteController extends Controller
{
    public function __invoke(PatientReport $report)
    {
        abort_unless($report->audio_path, 404);

        $this->authorize('view', $report->patient);

        $disk = Storage::disk('voice');
        abort_unless($disk->exists($report->audio_path), 404);

        Audit::record('report.voice_played', $report, ['patient_id' => $report->patient_id]);

        return $disk->response($report->audio_path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
