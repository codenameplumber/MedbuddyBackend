<?php

namespace Tests\Feature;

use App\Livewire\Me;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Parity with the static design in `MedBuddy/`.
 *
 * The platform was built screen by screen against that prototype, and the
 * things it quietly dropped were all of the same kind: a surface that existed
 * in the design and simply had no route, or a string the design had in four
 * languages that the port re-typed in English. Neither shows up as a failing
 * test unless someone writes one.
 */
class DesignParityTest extends TestCase
{
    use RefreshDatabase;

    private Patient $me;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->me = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Grace Okafor',
            'email' => 'grace@example.test', 'lang' => 'yo',
            'condition' => 'Hypertension', 'enrolled_at' => now()->subMonth(),
        ]);

        Consent::create([
            'patient_id' => $this->me->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);
    }

    private function signIn(): void
    {
        session(['patient_id' => $this->me->id]);
    }

    private function prescribe(): Prescription
    {
        return Prescription::create([
            'patient_id' => $this->me->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => ['08:00', '20:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);
    }

    // ── Surfaces that exist in the design ────────────────────────────

    /** Surfaces Laravel routes. */
    public static function designRoutes(): array
    {
        return [
            'front page'      => ['/'],
            'sign up'         => ['/register'],
            'clinic sign in'  => ['/login'],
            'patient sign in' => ['/me/login'],
            'pitch deck'      => ['/pitch'],
            'technical deck'  => ['/technical-deck'],
            'offline shell'   => ['/offline'],
        ];
    }

    #[DataProvider('designRoutes')]
    public function test_a_public_surface_from_the_design_is_reachable(string $path): void
    {
        $this->get($path)->assertOk();
    }

    /**
     * Surfaces the web server serves straight off disk.
     *
     * These never reach Laravel's router, so a request through the test kernel
     * 404s even when the file is there and a browser gets it. Assert on the
     * file instead — the thing that would actually be missing.
     */
    public static function designFiles(): array
    {
        return [
            'diacritic check' => ['tools/diacritics.html', 'Diacritic'],
            'web manifest'    => ['manifest.webmanifest', 'standalone'],
            'service worker'  => ['sw.js', 'medbuddy-v1'],
            'deck stylesheet' => ['styles/deck.css', '.slide'],
        ];
    }

    #[DataProvider('designFiles')]
    public function test_a_static_file_from_the_design_is_published(string $file, string $needle): void
    {
        $path = public_path($file);

        $this->assertFileExists($path, "public/{$file} is in the design but was never published.");
        $this->assertStringContainsString($needle, (string) file_get_contents($path));
    }

    public function test_the_patient_app_is_installable(): void
    {
        // The prototype was a PWA. Losing that was the biggest functional
        // regression from prototype to platform, and it is invisible from a
        // page that merely renders.
        $this->signIn();

        $this->get(route('me.today'))
            ->assertOk()
            ->assertSee('manifest.webmanifest')
            ->assertSee('sw.js');

        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/me', $manifest['start_url'], 'Installing must open the patient app, not the console.');
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_the_service_worker_never_caches_the_clinic_console(): void
    {
        // Staff read patient data at a desk. A cache they could read a stale
        // cohort out of is a clinical hazard, not a performance win.
        $sw = (string) file_get_contents(public_path('sw.js'));

        $this->assertMatchesRegularExpression(
            "/pathname\.indexOf\('\/clinic'\) === 0\)\s*return;/",
            $sw,
            'The service worker must bail out of anything under /clinic before it caches it.',
        );
    }

    public function test_the_offline_page_carries_the_emergency_number_in_every_language(): void
    {
        // It is cached before anyone signs in, so it cannot know which
        // language the reader wants — and the emergency number is the one
        // thing on it that has to work at zero bars.
        $this->get('/offline')
            ->assertOk()
            ->assertSee('tel:112', false)
            ->assertSee('Yorùbá')
            ->assertSee('Hausa')
            ->assertSee('Igbo');
    }

    // ── Strings the port re-typed in English ─────────────────────────

    public function test_the_today_screen_speaks_the_patients_language(): void
    {
        $this->signIn();
        $this->prescribe();

        Livewire::test(Me\Today::class)
            // "See my history", not the nav label for Today, which is what the
            // link used to render.
            ->assertSee(__('me.history.link', [], 'yo'))
            ->assertDontSee('Your clinic adds your medicines');
    }

    public function test_the_empty_today_screen_offers_the_action_the_design_offered(): void
    {
        // The design's empty state is a button to add your first medicine.
        // The port replaced it with English telephone advice, written before
        // the patient could add one at all.
        $this->signIn();

        Livewire::test(Me\Today::class)
            ->assertSee(__('me.today.addFirst', [], 'yo'))
            ->assertSee(route('me.medicine.add'));
    }

    public function test_dose_times_are_formatted_for_a_person_not_a_column(): void
    {
        $this->signIn();
        $this->prescribe();

        Livewire::test(Me\Today::class)
            ->assertSee('8:00 AM')
            ->assertSee('8:00 PM');
    }

    public function test_every_patient_screen_is_titled_with_its_own_string(): void
    {
        /*
         | Three screens were titled with a key borrowed from somewhere else,
         | so the browser tab and the header read "Today — 30" on the history
         | page and "Read aloud" on the report form. Cheap to do, invisible in
         | English, and confusing in every other language.
         */
        $this->signIn();
        $this->prescribe();

        // Through the router, not Livewire::test — the title is set on the
        // view and only the layout renders it.
        $expected = [
            'me.today'        => __('me.today.title', [], 'yo'),
            'me.medicines'    => __('me.meds.title', [], 'yo'),
            'me.medicine.add' => __('me.meds.add', [], 'yo'),
            'me.history'      => __('me.history.title', [], 'yo'),
            'me.report'       => __('me.report.title', [], 'yo'),
            'me.ask'          => __('me.ask.title', [], 'yo'),
            'me.scan'         => __('me.explain.title', [], 'yo'),
            'me.profile'      => __('me.settings.title', [], 'yo'),
        ];

        foreach ($expected as $route => $title) {
            $html = $this->get(route($route))->assertOk()->getContent();

            preg_match('/<title>(.*?)<\/title>/s', (string) $html, $m);

            $this->assertStringContainsString(
                $title,
                html_entity_decode($m[1] ?? ''),
                "{$route} is titled \"".trim($m[1] ?? '')."\", which is not its own string.",
            );
        }
    }

    public function test_the_scan_screen_lets_a_patient_take_or_choose_a_photo(): void
    {
        /*
         | It shipped as one file input carrying capture="environment", which
         | on a phone opens the camera and removes the gallery entirely — so
         | a patient who already had a photo of the label, or was sent one by
         | a relative, could not use it.
         */
        $this->signIn();

        $html = $this->get(route('me.scan'))->assertOk()->getContent();

        $this->assertStringContainsString(__('me.common.takePhoto'), $html);
        $this->assertStringContainsString(__('me.common.choosePhoto'), $html);

        // One input asks for the camera; the other must not, or the OS never
        // offers the library.
        preg_match_all('/<input[^>]*type="file"[^>]*>/', $html, $inputs);

        $this->assertGreaterThanOrEqual(2, count($inputs[0]));
        $this->assertTrue(
            collect($inputs[0])->contains(fn ($i) => str_contains($i, 'capture=')),
            'One input should open the camera.',
        );
        $this->assertTrue(
            collect($inputs[0])->contains(fn ($i) => ! str_contains($i, 'capture=')),
            'One input must NOT set capture, or choosing an existing photo is impossible.',
        );
    }

    public function test_an_ai_answer_can_be_read_aloud_in_the_patients_language(): void
    {
        // Half this audience finds listening easier than reading — which is
        // the same problem as the label they could not read.
        $this->signIn();
        $this->prescribe();

        Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send')
            ->assertSee('data-speak', false)
            ->assertSee('data-lang="yo"', false);
    }

    public function test_read_aloud_always_has_a_voice_to_fall_back_to(): void
    {
        /*
         | It must never refuse to read. Browsers ship voices for Yorùbá, Hausa
         | and Igbo almost nowhere, so the player falls back to the closest
         | sounding language it can find rather than staying silent — and the
         | fallback order prefers five-vowel, syllable-timed languages over
         | English, which reads these three badly.
         */
        $js = (string) file_get_contents(public_path('js/speak.js'));

        foreach (['yo', 'ig', 'ha'] as $lang) {
            $this->assertMatchesRegularExpression(
                "/{$lang}: \[[^\]]*'es'[^\]]*\]/",
                $js,
                "The {$lang} fallback chain should reach for a five-vowel voice before English.",
            );
        }

        // And the last resort is any voice at all, never nothing.
        $this->assertStringContainsString('var any = voices()[0];', $js);
        $this->assertStringNotContainsString('speakUnavailable', $js);
    }

    public function test_a_stand_in_voice_says_so(): void
    {
        // A patient is entitled to know before trusting how a dose was said.
        $this->signIn();
        $this->prescribe();

        $html = Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send')
            ->html();

        $this->assertStringContainsString('data-label-approx', $html);

        // Laravel interpolates :name, not {name}. Getting that wrong shipped
        // the literal placeholder to the patient.
        $this->assertStringContainsString('Yorùbá voice', $html);
        $this->assertStringNotContainsString('{lang}', $html);
    }

    public function test_the_read_aloud_button_is_never_hidden_behind_javascript(): void
    {
        /*
         | It first shipped `hidden`, revealed only once JS had confirmed a
         | voice. Browsers load their voice list asynchronously, so the common
         | outcome was no button appearing at all — the feature looked broken
         | rather than approximate. It now renders visible, and only a browser
         | with no speech support at all takes it away.
         */
        $this->signIn();
        $this->prescribe();

        $html = Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send')
            ->html();

        preg_match('/<button[^>]*data-speak-btn[^>]*>/', $html, $m);

        $this->assertNotEmpty($m, 'The read-aloud button must be rendered.');
        $this->assertStringNotContainsString('hidden', $m[0], 'It must not ship hidden.');

        // And the text it reads must survive Livewire's DOM morphing, which
        // is why it is a span rather than a <template>.
        $this->assertStringContainsString('data-speak-text', $html);
        $this->assertStringNotContainsString('<template data-speak-text', $html);
    }

    public function test_a_canned_answer_says_it_is_canned(): void
    {
        /*
         | With no API key the answers are canned English. On a Yorùbá screen
         | that reads as a bug unless the screen says why — so it does, rather
         | than leaving a patient to conclude the app is broken.
         */
        config(['services.ai.mode' => 'mock']);
        $this->signIn();
        $this->prescribe();

        Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send')
            ->assertSee(__('me.common.demoAnswer'));
    }

    public function test_no_patient_screen_hardcodes_an_english_sentence(): void
    {
        /*
         | A string typed straight into a Blade view is worse than an
         | untranslated key: `medbuddy:i18n` cannot count it and a reviewer
         | cannot find it, so it stays English forever. This catches the
         | pattern rather than any particular sentence.
         */
        $offenders = [];

        foreach (glob(resource_path('views/livewire/me/*.blade.php')) as $view) {
            $body = (string) file_get_contents($view);

            // Strip anything already inside a directive or expression.
            $stripped = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|\{\{--.*?--\}\}/s', '', $body);

            // A run of ordinary words sitting loose in the markup.
            if (preg_match_all('/^\s*([A-Z][a-z]+(?: [a-z\']{2,}){3,}[^<>\n]*)$/m', $stripped, $m)) {
                foreach ($m[1] as $hit) {
                    $offenders[] = basename($view).': '.trim($hit);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Patient-facing copy must live in lang/en so it can be translated:\n  ".implode("\n  ", $offenders),
        );
    }

    public function test_every_translated_string_the_design_had_survived_the_port(): void
    {
        /*
         | The port added English-only strings; it must never have *lost* one
         | that the design already had in four languages. That is the
         | regression this locks down — a reviewer's work being silently
         | discarded by a refactor.
         */
        $counts = [];

        foreach (['yo', 'ha', 'ig'] as $lang) {
            $counts[$lang] = count($this->flatten(require lang_path("{$lang}/me.php")));
        }

        foreach ($counts as $lang => $n) {
            $this->assertGreaterThanOrEqual(
                55,
                $n,
                "lang/{$lang}/me.php has {$n} strings. The design shipped 55; a port must not lose any.",
            );
        }
    }

    private function flatten(array $items, string $prefix = ''): array
    {
        $out = [];

        foreach ($items as $key => $value) {
            $dotted = $prefix ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $out += $this->flatten($value, $dotted);
            } else {
                $out[$dotted] = $value;
            }
        }

        return $out;
    }
}
