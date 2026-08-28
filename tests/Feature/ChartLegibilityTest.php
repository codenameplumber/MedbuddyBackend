<?php

namespace Tests\Feature;

use App\Support\Chart;
use Tests\TestCase;

/**
 * Charts that stay readable when the data grows.
 *
 * The bug these guard: every bar chart drew every category label
 * horizontally, centred on its bar, inside an SVG carrying
 * preserveAspectRatio="none". On the safety-signals page that is 39 labels
 * sharing 706 units — 18 units each, for drug names up to 37 characters — so
 * they overlapped into a smear, and the non-uniform scaling stretched the
 * glyphs on top of it.
 *
 * A chart nobody can read is not a chart, and this is a clinical dashboard.
 */
class ChartLegibilityTest extends TestCase
{
    /** @return list<array{label: string, value: int}> */
    private function rows(int $n, int $labelLength = 12): array
    {
        return collect(range(1, $n))
            ->map(fn ($i) => [
                'label' => str_pad("Drug {$i}", $labelLength, 'x'),
                'value' => 10 + $i,
            ])
            ->all();
    }

    public function test_text_bearing_charts_are_never_scaled_non_uniformly(): void
    {
        /*
         | preserveAspectRatio="none" stretches an SVG to its container on both
         | axes independently — which distorts every glyph inside it. It is
         | correct for a sparkline (a bare path) and wrong for anything with a
         | <text> node.
         */
        $charts = [
            'bars'        => Chart::bars($this->rows(6)),
            'stackedBars' => Chart::stackedBars([
                ['label' => 'Mon', 'parts' => [['value' => 3, 'tone' => 'safe'], ['value' => 1, 'tone' => 'alert']]],
                ['label' => 'Tue', 'parts' => [['value' => 4, 'tone' => 'safe']]],
            ]),
            'area'        => Chart::area([10, 20, 30], ['labels' => ['a', 'b', 'c']]),
        ];

        foreach ($charts as $name => $svg) {
            $this->assertStringContainsString('<text', $svg, "{$name} should carry labels.");
            $this->assertStringNotContainsString(
                'preserveAspectRatio="none"',
                $svg,
                "{$name} draws text, so it must not be stretched non-uniformly.",
            );
        }
    }

    public function test_a_sparkline_may_still_stretch(): void
    {
        // It has no text, and stretching to its container is the whole point.
        $svg = Chart::sparkline([1, 5, 3, 8]);

        $this->assertStringNotContainsString('<text', $svg);
        $this->assertStringContainsString('preserveAspectRatio="none"', $svg);
    }

    public function test_short_labels_with_room_are_left_flat(): void
    {
        // Nothing clever when nothing is needed.
        $svg = Chart::bars($this->rows(4, 6));

        $this->assertStringNotContainsString('rotate(-40', $svg);
        $this->assertStringContainsString('text-anchor="middle"', $svg);
    }

    public function test_crowded_labels_are_rotated_rather_than_overlapped(): void
    {
        $svg = Chart::bars($this->rows(14, 20));

        $this->assertStringContainsString('rotate(-40', $svg);
    }

    public function test_labels_thin_out_instead_of_colliding(): void
    {
        /*
         | At 16 bars there is not room for 16 rotated 20-character labels
         | either, so only every Nth is drawn. The full name stays on each
         | bar's <title>, so nothing is actually lost — it is one hover away.
         */
        $rows = $this->rows(16, 24);
        $svg = Chart::bars($rows);

        $labels = substr_count($svg, 'rotate(-40');

        $this->assertLessThan(count($rows), $labels, 'Labels must thin out when they cannot all fit.');
        $this->assertGreaterThan(0, $labels, 'But some must remain, or the axis means nothing.');

        foreach ($rows as $r) {
            $this->assertStringContainsString(
                '<title>'.$r['label'],
                $svg,
                'Every bar keeps its full name on hover, whether or not it is labelled.',
            );
        }
    }

    public function test_a_long_label_is_truncated_with_an_ellipsis_not_left_to_overflow(): void
    {
        $svg = Chart::bars([
            ['label' => 'Dolutegravir / Lamivudine / Tenofovir', 'value' => 10],
            ['label' => 'Hydrochlorothiazide', 'value' => 8],
            ['label' => 'Beclometasone inhaler', 'value' => 6],
            ['label' => 'Sodium valproate', 'value' => 4],
            ['label' => 'Carbamazepine', 'value' => 3],
            ['label' => 'Pyrazinamide', 'value' => 2],
        ]);

        $this->assertStringContainsString('…', $svg);
        // The untruncated name is still reachable.
        $this->assertStringContainsString('<title>Dolutegravir / Lamivudine / Tenofovir', $svg);
    }

    public function test_dropping_bars_is_stated_never_silent(): void
    {
        /*
         | Past a certain count the bars are thinner than the gaps between them
         | and the chart stops being one. Capping is fine; capping quietly is
         | not — a reader would take 16 bars for the whole dataset.
         */
        $svg = Chart::bars($this->rows(30), ['maxBars' => 12]);

        $this->assertStringContainsString('+18 more not shown', $svg);
    }

    public function test_the_chart_grows_taller_to_make_room_for_rotated_labels(): void
    {
        // Rotated labels need vertical space. Without this they were simply
        // clipped off the bottom edge.
        preg_match('/viewBox="0 0 \d+ ([\d.]+)"/', Chart::bars($this->rows(4, 6)), $flat);
        preg_match('/viewBox="0 0 \d+ ([\d.]+)"/', Chart::bars($this->rows(14, 22)), $rotated);

        $this->assertGreaterThan(
            (float) $flat[1],
            (float) $rotated[1],
            'A chart with rotated labels needs more height than one without.',
        );
    }

    public function test_an_empty_chart_says_so_rather_than_dividing_by_zero(): void
    {
        $this->assertStringContainsString('No data yet', Chart::bars([]));
        $this->assertStringContainsString('No data yet', Chart::stackedBars([]));
    }
}
