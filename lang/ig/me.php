<?php

/*
 | Patient-app interface strings, ported from js/core/i18n.js so the two
 | surfaces never drift into saying different things.
 |
 | NATIVE-SPEAKER REVIEW REQUIRED for yo / ha / ig. These are a working first
 | pass, not verified translations. A health interface is exactly where a
 | clumsy translation does real harm. Keys are stable; a reviewer edits values
 | only.
 |
 | Diacritics are load-bearing (Yoruba tone marks, Igbo dotted vowels, Hausa
 | hooked letters). Verify they render on the actual demo device.
 */
return [
    'nav' => [
        'today'    => 'Taa',
        'meds'     => 'Ọgwụ',
        'explain'  => 'Nyochaa',
        'ask'      => 'Jụọ',
        'settings' => 'Ntọala',
    ],

    'today' => [
        'title'      => 'Taa',
        'none'       => 'Enwebeghị ọgwụ ahaziri ahazi.',
        'addFirst'   => 'Tinye ọgwụ mbụ gị',
        'allDone'    => 'Ị ṅụọla ọgwụ niile taa. Ọ dị mma.',
        'streak'     => 'ụbọchị n’usoro',
        'streakNone' => 'Ṅụọ ọgwụ taa ka ị malite.',
        'markTaken'  => 'Kaa akara na ị ṅụọla',
        'taken'      => 'A ṅụọla',
        'remaining'  => 'fọdụrụ taa',
    ],

    'meds' => [
        'title'     => 'Ọgwụ m',
        'add'       => 'Tinye ọgwụ',
        'none'      => 'Ị tinyebeghị ọgwụ ọ bụla.',
        'name'      => 'Aha ọgwụ',
        'strength'  => 'Ike ya',
        'form'      => 'Ụdị',
        'purpose'   => 'Kedu ihe ọ na-eme?',
        'times'     => 'Oge ụbọchị',
        'addTime'   => 'Tinye oge ọzọ',
        'save'      => 'Chekwaa ọgwụ',
        'delete'    => 'Wepụ ọgwụ',
        'doseCount' => 'ugboro n’ụbọchị',
    ],

    'explain' => [
        'title'       => 'Kọwaa ọgwụ',
        'lead'        => 'Dee ihe akara ahụ kwuru, ma ọ bụ see foto ya.',
        'run'         => 'Kọwaa nke a',
        'working'     => 'Na-agụ…',
        'purpose'     => 'Ihe ọ bụ maka ya',
        'dosing'      => 'Otú e si aṅụ ya',
        'sideEffects' => 'Ihe nwere ike ime gị',
        'storage'     => 'Otú e si echekwa ya',
        'warnings'    => 'Kpachara anya maka',
    ],

    'ask' => [
        'title'       => 'Jụọ gbasara ọgwụ gị',
        'lead'        => 'Jụọ ajụjụ n’asụsụ gị.',
        'placeholder' => 'Dee ajụjụ gị…',
        'send'        => 'Zipu',
    ],

    'settings' => [
        'title'    => 'Ntọala',
        'language' => 'Asụsụ',
        'textSize' => 'Nha ederede',
        'voice'    => 'Gụpụta azịza n’olu',
    ],

    'sos' => [
        'title' => 'Chọọ enyemaka ugbu a',
        'lead'  => 'Ọ bụrụ na ọ bụ ihe mberede ahụike, kpọọ enyemaka ozugbo.',
        'call'  => 'Kpọọ',
        'back'  => 'Laghachi azụ',
    ],

    'crisis' => [
        'title' => 'Nke a nwere ike ịbụ ihe mberede',
        'lead'  => 'Ihe ị kọwara nwere ike ịdị njọ. Echela azịza — chọọ enyemaka ugbu a.',
    ],

    'common' => [
        'cancel'    => 'Kagbuo',
        'back'      => 'Laghachi',
        'speak'     => 'Gụpụta',
        'error'     => 'Ihe adịghị mma mere. Biko nwaa ọzọ.',
        'aiNote'    => 'AI dere ya. Gwa dọkịta ma ọ bụ onye na-ere ọgwụ mgbe niile.',
        'notAdvice' => 'Nke a bụ ozi izugbe, ọ bụghị ndụmọdụ ahụike.',
    ],

];
