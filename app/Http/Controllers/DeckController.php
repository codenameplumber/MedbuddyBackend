<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Support\Chart;
use Illuminate\Support\Facades\Cache;

/**
 * The pitch and technical decks.
 *
 * They were written as static pages against the prototype's localStorage
 * seed, so every chart in them showed a cohort of eight regardless of what the
 * platform actually held. Serving them from here renders the same charts from
 * the real database, which is what the original script's own comment said the
 * point was.
 *
 * Cached for the same reason as the front page: a deck is opened by anyone
 * with the link and must not cost a cohort fold per view.
 */
class DeckController extends Controller
{
    public function pitch(AnalyticsService $analytics)
    {
        return view('decks.pitch', ['charts' => $this->charts($analytics)]);
    }

    public function technical(AnalyticsService $analytics)
    {
        return view('decks.technical', ['charts' => $this->charts($analytics)]);
    }

    /**
     * Rendered SVG keyed by the container id each deck declares. A deck that
     * has no #c-heat simply never receives one.
     *
     * @return array<string, string>
     */
    private function charts(AnalyticsService $analytics): array
    {
        return Cache::remember('decks.charts', now()->addMinutes(10), function () use ($analytics) {
            $cohort = $analytics->cohort();
            $trend  = $analytics->adherenceTrend(30);
            $bands  = $analytics->adherenceBands($cohort);
            $heat   = $analytics->doseTimeHeat(30);

            return [
                'c-area' => Chart::area($trend['values'], [
                    'labels' => $trend['labels'], 'min' => 40, 'max' => 100,
                    'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100], 'height' => 150,
                ]),

                'c-donut' => Chart::donut($bands, [
                    'size' => 130, 'thickness' => 18,
                    'centre' => ['value' => $cohort->count(), 'label' => 'patients'],
                ]).Chart::legend(collect($bands)->map(fn ($b) => [
                    'label' => $b['label'], 'tone' => $b['tone'], 'value' => $b['value'],
                ])->all()),

                'c-heat' => Chart::heatStrip(collect($heat)->map(fn ($h) => [
                    'label' => $h['label'].' · '.$h['pct'].'%', 'value' => $h['value'],
                ])->all(), ['tone' => 'safe']),

                'c-ring' => '<div style="display:flex;justify-content:center;padding:4mm 0">'
                    .Chart::ring(67, ['size' => 150, 'thickness' => 15, 'tone' => 'caution', 'label' => "Today's doses"])
                    .'</div><p class="s-kicker" style="text-align:center">2 of 3 taken · 12 day streak</p>',
            ];
        });
    }
}
