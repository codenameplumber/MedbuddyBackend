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
        'today'    => 'Yau',
        'meds'     => 'Magunguna',
        'explain'  => 'Duba',
        'ask'      => 'Tambaya',
        'settings' => 'Saituna',
    ],

    'today' => [
        'title'      => 'Yau',
        'none'       => 'Babu maganin da aka tsara tukuna.',
        'addFirst'   => 'Ƙara maganinka na farko',
        'allDone'    => 'An sha dukkan magungunan yau. Madalla.',
        'streak'     => 'kwanaki a jere',
        'streakNone' => 'Sha magungunan yau don farawa.',
        'markTaken'  => 'Yi alama an sha',
        'taken'      => 'An sha',
        'remaining'  => 'ya rage yau',
    ],

    'meds' => [
        'title'     => 'Magungunana',
        'add'       => 'Ƙara magani',
        'none'      => 'Ba ka ƙara wani magani ba tukuna.',
        'name'      => 'Sunan magani',
        'strength'  => 'Ƙarfi',
        'form'      => 'Nau’i',
        'purpose'   => 'Don me ake amfani da shi?',
        'times'     => 'Lokutan rana',
        'addTime'   => 'Ƙara wani lokaci',
        'save'      => 'Ajiye magani',
        'delete'    => 'Cire magani',
        'doseCount' => 'sau a rana',
    ],

    'explain' => [
        'title'       => 'Bayyana magani',
        'lead'        => 'Rubuta abin da lakabin ya ce, ko ɗauki hoto.',
        'run'         => 'Bayyana wannan',
        'working'     => 'Ana karantawa…',
        'purpose'     => 'Don me yake',
        'dosing'      => 'Yadda ake sha',
        'sideEffects' => 'Illolin da za su iya faruwa',
        'storage'     => 'Yadda ake ajiyewa',
        'warnings'    => 'Yi hankali da',
    ],

    'ask' => [
        'title'       => 'Tambaya game da magungunanka',
        'lead'        => 'Yi tambaya da harshenka.',
        'placeholder' => 'Rubuta tambayarka…',
        'send'        => 'Aika',
    ],

    'settings' => [
        'title'    => 'Saituna',
        'language' => 'Harshe',
        'textSize' => 'Girman rubutu',
        'voice'    => 'Karanta amsoshi da murya',
    ],

    'sos' => [
        'title' => 'Nemi taimako yanzu',
        'lead'  => 'Idan wannan gaggawa ce ta lafiya, ka kira taimako nan take.',
        'call'  => 'Kira',
        'back'  => 'Koma baya',
    ],

    'crisis' => [
        'title' => 'Wannan na iya zama gaggawa',
        'lead'  => 'Abin da ka bayyana na iya zama mai tsanani. Kada ka jira amsa — nemi taimako yanzu.',
    ],

    'common' => [
        'cancel'    => 'Soke',
        'back'      => 'Koma',
        'speak'     => 'Karanta da murya',
        'error'     => 'An samu matsala. Ka sake gwadawa.',
        'aiNote'    => 'AI ne ya rubuta. Koyaushe ka tuntuɓi likitanka ko masanin magani.',
        'notAdvice' => 'Wannan bayani ne na gaba ɗaya, ba shawarar likita ba.',
    ],

];
