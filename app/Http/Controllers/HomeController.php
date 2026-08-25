<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\AnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * The public front page.
 *
 * The charts on it are the console's own chart code over the real database
 * rather than screenshots — which is the claim the page makes, so it has to be
 * true. That fold is the same one the Overview runs, so the numbers are cached
 * for ten minutes: a marketing page must not cost four seconds of CPU to
 * anyone who happens to load it.
 */
class HomeController extends Controller
{
    public function __invoke(AnalyticsService $analytics)
    {
        $data = Cache::remember('home.figures', now()->addMinutes(10), function () use ($analytics) {
            $cohort = $analytics->cohort();

            return [
                'stats'  => $analytics->cohortStats($cohort),
                'bands'  => $analytics->adherenceBands($cohort),
                'trend'  => $analytics->adherenceTrend(30),
                'heat'   => $analytics->doseTimeHeat(30),
                'figures' => [
                    'patients' => Patient::count(),
                    'signals'  => count($analytics->signals()),
                    'pages'    => collect(Route::getRoutes())
                        ->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'clinic.'))
                        ->count(),
                ],
            ];
        });

        return view('home', $data);
    }
}
