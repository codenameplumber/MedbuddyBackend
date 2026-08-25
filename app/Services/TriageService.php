<?php

namespace App\Services;

/**
 * Emergency detection — the server-side mirror of js/core/triage.js.
 *
 * The patient app keeps its own copy in JavaScript and that one stays: it
 * runs on-device, synchronously, at zero latency and zero bars, before any
 * network call. This PHP copy exists for text that reaches us through other
 * doors — a note typed into a reminder response, a caregiver email, an
 * inbound SMS — where the browser gate never ran.
 *
 * Deliberately tuned to over-trigger. A false alarm costs one extra screen.
 * A miss costs much more.
 *
 * NATIVE-SPEAKER + CLINICIAN REVIEW REQUIRED on the yo/ha/ig term lists.
 */
class TriageService
{
    /** @var array<string, array<string, string[]>> */
    private const TERMS = [
        'en' => [
            'cardiac'   => ['chest pain', 'chest is tight', 'pain in my chest', 'crushing chest', 'heart attack'],
            'breathing' => ['cannot breathe', 'can not breathe', 'cant breathe', 'trouble breathing', 'gasping', 'choking', 'struggling to breathe'],
            'stroke'    => ['face is drooping', 'cannot speak', 'slurred speech', 'one side is numb', 'cannot move my arm', 'stroke'],
            'bleeding'  => ['bleeding a lot', 'heavy bleeding', 'will not stop bleeding', 'blood in my vomit', 'vomiting blood', 'coughing blood'],
            'allergy'   => ['throat is closing', 'face is swelling', 'lips are swelling', 'tongue is swelling', 'severe rash all over', 'anaphylaxis'],
            'overdose'  => ['took too many', 'took the whole bottle', 'overdose', 'double dose by mistake', 'swallowed all the tablets'],
            'selfharm'  => ['kill myself', 'end my life', 'want to die', 'suicide', 'harm myself', 'no reason to live'],
            'collapse'  => ['unconscious', 'passed out', 'not waking up', 'having a seizure', 'convulsing', 'fitting'],
        ],
        'yo' => [
            'cardiac'   => ['irora aya', 'aya mi n dun', 'aya mi ro'],
            'breathing' => ['mi o le mi', 'emi ko ba mi', 'emi mi ko to'],
            'stroke'    => ['mi o le soro', 'oju mi wo', 'apa mi ku', 'egbe kan ku'],
            'bleeding'  => ['eje n jade pupo', 'eje ko duro', 'mo n po eje'],
            'allergy'   => ['ofun mi ti di', 'oju mi wu', 'ete mi wu', 'ahon mi wu'],
            'overdose'  => ['mo mu pupo ju', 'mo mu gbogbo re', 'mo mu oogun pupo'],
            'selfharm'  => ['mo fe ku', 'mo fe pa ara mi', 'ko si idi ti n o fi wa laaye'],
            'collapse'  => ['o subu', 'ko ji', 'o n gbon', 'giri'],
        ],
        'ha' => [
            'cardiac'   => ['ciwon kirji', 'kirjina yana ciwo', 'zafi a kirji'],
            'breathing' => ['ba zan iya numfashi', 'numfashi ya yi mini wuya', 'shakewa'],
            'stroke'    => ['ba zan iya magana ba', 'fuskata ta karkace', 'hannuna ya mutu', 'shanyewar barin jiki'],
            'bleeding'  => ['jini yana zuba sosai', 'jini bai tsaya ba', 'ina amai da jini'],
            'allergy'   => ['makogwarona ya rufe', 'fuskata ta kumbura', 'lebbana sun kumbura', 'harshena ya kumbura'],
            'overdose'  => ['na sha da yawa', 'na sha duka', 'na sha magani fiye da kima'],
            'selfharm'  => ['ina son mutuwa', 'zan kashe kaina', 'babu dalilin rayuwa'],
            'collapse'  => ['ya suma', 'ba ya farkawa', 'yana girgiza', 'farfadiya'],
        ],
        'ig' => [
            'cardiac'   => ['obi na-egbu mgbu', 'mgbu n obi', 'obi m na-afu ufu'],
            'breathing' => ['enweghi m ike iku ume', 'iku ume siiri m ike', 'ume adighi'],
            'stroke'    => ['enweghi m ike ikwu okwu', 'ihu m dara', 'aka m nwuru', 'otu akuku nwuru'],
            'bleeding'  => ['obara na-agba nke ukwuu', 'obara akwusighi', 'ana m agbo obara'],
            'allergy'   => ['akpiri m mechiri', 'ihu m zara', 'egbugbere onu m zara', 'ire m zara'],
            'overdose'  => ['a nuru m nke ukwuu', 'a nuru m ya nile', 'e riri m ogwu buru ibu'],
            'selfharm'  => ['achoro m inwu', 'aga m egbu onwe m', 'o dighi ihe m ji adi ndu'],
            'collapse'  => ['o dara', 'o naghi eteta', 'ahu na-ama ya jijiji', 'oria ahu ojoo'],
        ],
    ];

    public const GUIDANCE = [
        'cardiac'   => 'Sit down and stay still. Call for help now. Do not drive yourself.',
        'breathing' => 'Sit upright. Loosen tight clothing. Call for help now.',
        'stroke'    => 'Note the time symptoms started and call for help now. Every minute matters.',
        'bleeding'  => 'Press firmly on the wound with a clean cloth and call for help now.',
        'allergy'   => 'If an adrenaline pen was prescribed, use it. Call for help now.',
        'overdose'  => 'Bring the medicine packet with you. Call for help now.',
        'selfharm'  => 'You are not alone. Please speak to someone you trust right now, and call for help.',
        'collapse'  => 'Lay the person on their side. Do not put anything in their mouth. Call for help now.',
    ];

    /**
     * @return array{crisis: bool, category?: string, matched?: string}
     */
    public function check(?string $text, string $lang = 'en'): array
    {
        if (blank($text)) {
            return ['crisis' => false];
        }

        $hay = $this->normalize($text);

        // Scan the patient's own language first, then every other one. People
        // code-switch constantly: a Yoruba speaker may well type "chest pain",
        // so scanning only the selected language would miss it.
        $order = array_unique(array_merge([$lang], array_keys(self::TERMS)));

        foreach ($order as $code) {
            foreach (self::TERMS[$code] ?? [] as $category => $phrases) {
                foreach ($phrases as $phrase) {
                    if (str_contains($hay, $phrase)) {
                        return ['crisis' => true, 'category' => $category, 'matched' => $phrase];
                    }
                }
            }
        }

        return ['crisis' => false];
    }

    public function guidance(?string $category): ?string
    {
        return $category ? (self::GUIDANCE[$category] ?? null) : null;
    }

    /** Lowercase and strip diacritics so "ofun" and "ofun" both match. */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_D) ?: $text;
        }

        // Combining marks (Yoruba tone, Igbo dots) and Hausa hooked letters.
        $text = preg_replace('/\p{Mn}+/u', '', $text) ?? $text;
        $text = strtr($text, ['b' => 'b', 'd' => 'd', 'k' => 'k']);
        $text = str_replace(["\u{0253}", "\u{0257}", "\u{0199}", "\u{01b4}"], ['b', 'd', 'k', 'y'], $text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
