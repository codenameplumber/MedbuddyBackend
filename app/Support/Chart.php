<?php

namespace App\Support;

/**
 * Server-side SVG charts — the PHP mirror of the prototype's charts.js.
 *
 * Rendered on the server rather than in JS for one specific reason: Livewire
 * replaces DOM on every update, and a chart drawn by a script that only ran on
 * first paint would vanish on the next re-render. Emitting SVG from Blade means
 * charts survive every diff with no JS lifecycle to manage.
 *
 * All colour comes from CSS custom properties, so charts follow the theme
 * including clinic dark mode.
 */
class Chart
{
    private static int $seq = 0;

    private static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    private static function n(float $v, int $dp = 2): string
    {
        return rtrim(rtrim(number_format($v, $dp, '.', ''), '0'), '.') ?: '0';
    }

    /** Catmull-Rom to cubic bezier. Readable trend lines without lying about the data. */
    private static function smooth(array $pts, float $tension = 0.35): string
    {
        if (count($pts) < 2) {
            return '';
        }

        $d = 'M'.self::n($pts[0][0]).' '.self::n($pts[0][1]);

        for ($i = 0; $i < count($pts) - 1; $i++) {
            $p0 = $pts[$i - 1] ?? $pts[$i];
            $p1 = $pts[$i];
            $p2 = $pts[$i + 1];
            $p3 = $pts[$i + 2] ?? $p2;

            $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6 * $tension * 2, $p1[1] + ($p2[1] - $p0[1]) / 6 * $tension * 2];
            $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6 * $tension * 2, $p2[1] - ($p3[1] - $p1[1]) / 6 * $tension * 2];

            $d .= 'C'.self::n($c1[0]).' '.self::n($c1[1]).','.self::n($c2[0]).' '.self::n($c2[1])
                .','.self::n($p2[0]).' '.self::n($p2[1]);
        }

        return $d;
    }

    public static function area(array $values, array $o = []): string
    {
        if (empty($values)) {
            return '<p class="hint">No data yet.</p>';
        }

        $labels = $o['labels'] ?? [];
        $height = $o['height'] ?? 190;
        $tone   = $o['tone'] ?? 'accent';
        $band   = $o['band'] ?? null;
        $title  = $o['title'] ?? 'Trend';

        $W = 760; $H = $height; $padL = 42; $padR = 12; $padT = 14; $padB = 26;
        $min = $o['min'] ?? min($values);
        $max = $o['max'] ?? max($values);
        $span = ($max - $min) ?: 1;

        $count = count($values);
        $x = fn ($i) => $padL + ($i * ($W - $padL - $padR)) / max(1, $count - 1);
        $y = fn ($v) => $padT + ($H - $padT - $padB) * (1 - ($v - $min) / $span);

        $pts = [];
        foreach ($values as $i => $v) {
            $pts[] = [$x($i), $y($v)];
        }

        $line = self::smooth($pts);
        $fill = $line.'L'.self::n($x($count - 1)).' '.self::n($H - $padB)
                     .'L'.self::n($padL).' '.self::n($H - $padB).'Z';

        $gid = 'chg'.(++self::$seq);
        $grid = '';
        for ($i = 0; $i <= 4; $i++) {
            $v = $min + ($span * $i) / 4;
            $yy = self::n($y($v));
            $grid .= '<line class="ch-grid" x1="'.$padL.'" x2="'.($W - $padR).'" y1="'.$yy.'" y2="'.$yy.'"/>'
                   .'<text class="ch-tick" x="'.($padL - 8).'" y="'.self::n($y($v) + 4).'" text-anchor="end">'.round($v).'</text>';
        }

        $xLabels = '';
        if ($labels) {
            $every = max(1, (int) ceil(count($labels) / 7));
            foreach ($labels as $i => $l) {
                if ($i % $every === 0 || $i === count($labels) - 1) {
                    $xLabels .= '<text class="ch-tick" x="'.self::n($x($i)).'" y="'.($H - 6).'" text-anchor="middle">'.self::e($l).'</text>';
                }
            }
        }

        $bandRect = $band
            ? '<rect class="ch-band" x="'.$padL.'" y="'.self::n($y($band['to'])).'" width="'.($W - $padL - $padR)
              .'" height="'.self::n($y($band['from']) - $y($band['to'])).'"/>'
            : '';

        $last = $values[$count - 1];

        return '<svg class="ch ch-'.self::e($tone).'" viewBox="0 0 '.$W.' '.$H.'" role="img"'
            .' aria-label="'.self::e($title).': from '.self::e($values[0]).' to '.self::e($last).' over '.$count.' points">'
            .'<defs><linearGradient id="'.$gid.'" x1="0" y1="0" x2="0" y2="1">'
            .'<stop offset="0%" class="ch-stop-a"/><stop offset="100%" class="ch-stop-b"/></linearGradient></defs>'
            .$grid.$bandRect
            .'<path class="ch-fill" d="'.$fill.'" fill="url(#'.$gid.')"/>'
            .'<path class="ch-line" d="'.$line.'"/>'
            .'<circle class="ch-dot" cx="'.self::n($x($count - 1)).'" cy="'.self::n($y($last)).'" r="4.5"/>'
            .$xLabels.'</svg>';
    }

    /** @param array<int, array{label:string, value:int|float, tone?:string}> $rows */
    /**
     * How to draw a category axis without the labels colliding.
     *
     * The old charts drew every label horizontally, centred on its bar, inside
     * an SVG stretched by preserveAspectRatio="none". On the safety-signals
     * page that is 39 labels sharing 706 units — 18 units each, for names up
     * to 37 characters — so they overlapped into a grey smear, and the stretch
     * distorted the glyphs on top of it.
     *
     * This decides three things instead: rotate or not, label every Nth bar,
     * and how many characters actually fit. Anything trimmed is still on the
     * bar's <title>, so hovering gives the full name.
     *
     * @param  list<string>  $labels
     * @return array{rotate: bool, step: int, chars: int, pad: int}
     */
    private static function labelPlan(array $labels, float $bw): array
    {
        // IBM Plex Mono at 11px, measured: ~6.6 units per character.
        $charW = 6.6;
        $longest = max(1, max(array_map('mb_strlen', $labels ?: [''])));
        $fitsFlat = (int) floor($bw / $charW);

        // Everything fits lying flat. Nothing to be clever about.
        if ($fitsFlat >= 4 && $longest <= $fitsFlat) {
            return ['rotate' => false, 'step' => 1, 'chars' => $fitsFlat, 'pad' => 34];
        }

        // Rotated 40°, a character advances ~cos(40°) horizontally and
        // ~sin(40°) vertically, so a diagonal label needs less width and more
        // height than a flat one.
        $advance = $charW * 0.766;
        $rise    = $charW * 0.643;

        $step  = max(1, (int) ceil(14 * $advance / max($bw, 1)));
        $chars = max(6, min(20, (int) floor($step * $bw / $advance)));
        $chars = min($chars, $longest);

        return [
            'rotate' => true,
            'step'   => $step,
            'chars'  => $chars,
            'pad'    => 30 + (int) ceil($chars * $rise),
        ];
    }

    /** One category label, flat or on the diagonal. */
    private static function categoryLabel(string $text, float $x, float $baseline, array $plan): string
    {
        $shown = mb_strlen($text) > $plan['chars']
            ? mb_substr($text, 0, max(1, $plan['chars'] - 1)).'…'
            : $text;

        if (! $plan['rotate']) {
            return '<text class="ch-tick" x="'.self::n($x).'" y="'.self::n($baseline)
                .'" text-anchor="middle">'.self::e($shown).'</text>';
        }

        return '<text class="ch-tick" x="'.self::n($x).'" y="'.self::n($baseline)
            .'" text-anchor="end" transform="rotate(-40 '.self::n($x).' '.self::n($baseline).')">'
            .self::e($shown).'</text>';
    }

    public static function bars(array $rows, array $o = []): string
    {
        if (empty($rows)) {
            return '<p class="hint">No data yet.</p>';
        }

        // A hard cap, stated rather than silent. Beyond this the bars are
        // thinner than the gap between them and the chart stops being one.
        $cap = (int) ($o['maxBars'] ?? 16);
        $dropped = max(0, count($rows) - $cap);
        if ($dropped > 0) {
            $rows = array_slice($rows, 0, $cap);
        }

        $W = 760; $padL = 42; $padR = 12; $padT = 14;
        $unit = $o['unit'] ?? '';
        $max = $o['max'] ?? max(array_map(fn ($r) => $r['value'], $rows)) ?: 1;
        $bw = ($W - $padL - $padR) / count($rows);

        $plan = self::labelPlan(array_map(fn ($r) => (string) $r['label'], $rows), $bw);
        $padB = $plan['pad'];
        $H = ($o['height'] ?? 200) + max(0, $padB - 34);

        $y = fn ($v) => $padT + ($H - $padT - $padB) * (1 - $v / $max);

        $grid = '';
        for ($i = 0; $i <= 3; $i++) {
            $v = ($max * $i) / 3;
            $yy = self::n($y($v));
            $grid .= '<line class="ch-grid" x1="'.$padL.'" x2="'.($W - $padR).'" y1="'.$yy.'" y2="'.$yy.'"/>'
                   .'<text class="ch-tick" x="'.($padL - 8).'" y="'.self::n($y($v) + 4).'" text-anchor="end">'.round($v).'</text>';
        }

        $body = '';
        $aria = [];
        foreach ($rows as $i => $r) {
            $bx = $padL + $i * $bw + $bw * 0.18;
            $w = $bw * 0.64;
            $h = max(2, $H - $padB - $y($r['value']));
            $tone = isset($r['tone']) ? ' is-'.self::e($r['tone']) : '';

            $body .= '<rect class="ch-bar'.$tone.'" x="'.self::n($bx).'" y="'.self::n($y($r['value']))
                  .'" width="'.self::n($w).'" height="'.self::n($h).'" rx="3">'
                  .'<title>'.self::e($r['label']).': '.self::e($r['value']).self::e($unit).'</title></rect>';

            // Every Nth label when they will not all fit. The full name stays
            // on the <title> above, so nothing is actually lost.
            if ($i % $plan['step'] === 0) {
                $body .= self::categoryLabel((string) $r['label'], $bx + $w / 2, $H - $padB + 14, $plan);
            }

            $aria[] = $r['label'].' '.$r['value'].$unit;
        }

        if ($dropped > 0) {
            $body .= '<text class="ch-tick" x="'.($W - $padR).'" y="'.($padT + 2)
                  .'" text-anchor="end">+'.$dropped.' more not shown</text>';
        }

        return '<svg class="ch" viewBox="0 0 '.$W.' '.self::n($H).'" role="img" aria-label="'
            .self::e(($o['title'] ?? 'Distribution').': '.implode(', ', $aria)).'">'.$grid.$body.'</svg>';
    }

    public static function stackedBars(array $rows, array $o = []): string
    {
        if (empty($rows)) {
            return '<p class="hint">No data yet.</p>';
        }

        $cap = (int) ($o['maxBars'] ?? 16);
        $dropped = max(0, count($rows) - $cap);
        if ($dropped > 0) {
            $rows = array_slice($rows, 0, $cap);
        }

        $W = 760; $padL = 12; $padR = 12; $padT = 12;
        $max = max(array_map(fn ($r) => array_sum(array_column($r['parts'], 'value')), $rows)) ?: 1;
        $bw = ($W - $padL - $padR) / count($rows);

        $plan = self::labelPlan(array_map(fn ($r) => (string) $r['label'], $rows), $bw);
        $padB = $plan['pad'];
        $H = ($o['height'] ?? 200) + max(0, $padB - 30);

        $scale = fn ($v) => ($v / $max) * ($H - $padT - $padB);

        $body = '';
        foreach ($rows as $i => $r) {
            $bx = $padL + $i * $bw + $bw * 0.2;
            $w = $bw * 0.6;
            $acc = 0;
            foreach ($r['parts'] as $p) {
                $h = $scale($p['value']);
                if ($h < 0.5) continue;
                $yy = $H - $padB - $acc - $h;
                $acc += $h;
                $body .= '<rect class="ch-bar is-'.self::e($p['tone']).'" x="'.self::n($bx).'" y="'.self::n($yy)
                      .'" width="'.self::n($w).'" height="'.self::n($h).'" rx="2">'
                      .'<title>'.self::e($r['label']).': '.self::e($p['value']).'</title></rect>';
            }

            if ($i % $plan['step'] === 0) {
                $body .= self::categoryLabel((string) $r['label'], $bx + $w / 2, $H - $padB + 14, $plan);
            }
        }

        if ($dropped > 0) {
            $body .= '<text class="ch-tick" x="'.($W - $padR).'" y="'.($padT + 2)
                  .'" text-anchor="end">+'.$dropped.' more not shown</text>';
        }

        return '<svg class="ch" viewBox="0 0 '.$W.' '.self::n($H).'" role="img" aria-label="'
            .self::e($o['title'] ?? 'Breakdown').'">'.$body.'</svg>';
    }

    public static function donut(array $segments, array $o = []): string
    {
        $size = $o['size'] ?? 190;
        $thickness = $o['thickness'] ?? 26;
        $centre = $o['centre'] ?? null;

        $R = $size / 2;
        $r = $R - $thickness / 2;
        $total = array_sum(array_column($segments, 'value')) ?: 1;
        $C = 2 * M_PI * $r;

        $offset = 0;
        $rings = '';
        $aria = [];
        foreach ($segments as $s) {
            $len = ($s['value'] / $total) * $C;
            $rings .= '<circle class="ch-arc is-'.self::e($s['tone']).'" cx="'.$R.'" cy="'.$R.'" r="'.self::n($r)
                   .'" stroke-dasharray="'.self::n($len).' '.self::n($C - $len).'" stroke-dashoffset="'.self::n(-$offset)
                   .'" stroke-width="'.$thickness.'"><title>'.self::e($s['label']).': '.self::e($s['value']).'</title></circle>';
            $offset += $len;
            $aria[] = $s['label'].' '.$s['value'];
        }

        $label = $centre
            ? '<text class="ch-centre" x="'.$R.'" y="'.($R - 2).'" text-anchor="middle">'.self::e($centre['value']).'</text>'
              .'<text class="ch-centre-sub" x="'.$R.'" y="'.($R + 17).'" text-anchor="middle">'.self::e($centre['label']).'</text>'
            : '';

        return '<svg class="ch ch-donut" viewBox="0 0 '.$size.' '.$size.'" role="img" aria-label="'
            .self::e(($o['title'] ?? 'Composition').': '.implode(', ', $aria)).'">'
            .'<g transform="rotate(-90 '.$R.' '.$R.')">'
            .'<circle class="ch-track" cx="'.$R.'" cy="'.$R.'" r="'.self::n($r).'" stroke-width="'.$thickness.'"/>'
            .$rings.'</g>'.$label.'</svg>';
    }

    public static function ring(float $pct, array $o = []): string
    {
        $size = $o['size'] ?? 120;
        $thickness = $o['thickness'] ?? 12;
        $tone = $o['tone'] ?? 'safe';
        $label = $o['label'] ?? 'Progress';

        $R = $size / 2;
        $r = $R - $thickness / 2;
        $C = 2 * M_PI * $r;
        $len = (max(0, min(100, $pct)) / 100) * $C;

        return '<svg class="ch ch-donut" viewBox="0 0 '.$size.' '.$size.'" role="img" aria-label="'
            .self::e($label).': '.round($pct).'%">'
            .'<g transform="rotate(-90 '.$R.' '.$R.')">'
            .'<circle class="ch-track" cx="'.$R.'" cy="'.$R.'" r="'.self::n($r).'" stroke-width="'.$thickness.'"/>'
            .'<circle class="ch-arc is-'.self::e($tone).'" cx="'.$R.'" cy="'.$R.'" r="'.self::n($r)
            .'" stroke-width="'.$thickness.'" stroke-dasharray="'.self::n($len).' '.self::n($C - $len).'" stroke-linecap="round"/>'
            .'</g><text class="ch-centre" x="'.$R.'" y="'.($R + 6).'" text-anchor="middle">'.round($pct).'%</text></svg>';
    }

    public static function sparkline(array $values, array $o = []): string
    {
        if (count($values) < 2) {
            return '';
        }

        $width = $o['width'] ?? 120;
        $height = $o['height'] ?? 30;
        $tone = $o['tone'] ?? 'accent';

        $min = min($values); $max = max($values);
        $span = ($max - $min) ?: 1;
        $x = fn ($i) => ($i * $width) / max(1, count($values) - 1);
        $y = fn ($v) => $height - 3 - ($height - 6) * (($v - $min) / $span);

        $pts = [];
        foreach ($values as $i => $v) {
            $pts[] = [$x($i), $y($v)];
        }

        return '<svg class="ch ch-spark ch-'.self::e($tone).'" viewBox="0 0 '.$width.' '.$height
            .'" preserveAspectRatio="none" role="img" aria-label="'.self::e($o['label'] ?? 'Trend').'">'
            .'<path class="ch-line" d="'.self::smooth($pts).'"/>'
            .'<circle class="ch-dot" cx="'.self::n($x(count($values) - 1)).'" cy="'.self::n($y(end($values))).'" r="2.5"/></svg>';
    }

    /** @param array<int, array{label:string, value:float}> $cells value 0..1 */
    public static function heatStrip(array $cells, array $o = []): string
    {
        $tone = $o['tone'] ?? 'safe';
        $aria = [];
        $html = '';

        foreach ($cells as $c) {
            $v = max(0.04, min(1, $c['value']));
            $html .= '<div class="heat-cell"><span class="heat-box is-'.self::e($tone).'" style="--v:'.self::n($v, 3)
                  .'" title="'.self::e($c['label']).'"></span><span class="heat-label">'.self::e($c['label']).'</span></div>';
            $aria[] = $c['label'];
        }

        return '<div class="heat" role="img" aria-label="'.self::e(($o['title'] ?? 'Intensity').': '.implode(', ', $aria)).'">'
            .$html.'</div>';
    }

    public static function legend(array $items): string
    {
        $html = '';
        foreach ($items as $i) {
            $html .= '<span class="ch-key"><span class="ch-swatch is-'.self::e($i['tone'] ?? 'neutral').'"></span>'
                  .self::e($i['label'])
                  .(isset($i['value']) ? ' <strong>'.self::e($i['value']).'</strong>' : '').'</span>';
        }

        return '<div class="ch-legend">'.$html.'</div>';
    }

    /** Inline adherence bar for dense tables. */
    public static function meter(int $pct): string
    {
        $tone = $pct < 60 ? 'meter-alert' : ($pct < 80 ? 'meter-caution' : '');
        $w = max(2, min(100, $pct));

        return '<span class="row" style="gap:var(--s2)"><span class="meter '.$tone
            .'" role="img" aria-label="'.$pct.' percent adherence"><span style="width:'.$w.'%"></span></span>'
            .'<span class="mono" style="font-size:var(--step-s)">'.$pct.'%</span></span>';
    }
}
