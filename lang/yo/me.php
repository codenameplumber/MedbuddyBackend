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
        'today'    => 'Òní',
        'meds'     => 'Òògùn',
        'explain'  => 'Yàwòrán',
        'ask'      => 'Bèèrè',
        'settings' => 'Ètò',
    ],

    'today' => [
        'title'      => 'Òní',
        'none'       => 'Kò sí òògùn tí a ṣètò síbẹ̀.',
        'addFirst'   => 'Fi òògùn àkọ́kọ́ rẹ kún',
        'allDone'    => 'O ti mu gbogbo òògùn òní. Ó ṣeun.',
        'streak'     => 'ọjọ́ ní tẹ̀lé',
        'streakNone' => 'Mu òògùn òní láti bẹ̀rẹ̀.',
        'markTaken'  => 'Sàmì pé o ti mu',
        'taken'      => 'A ti mu',
        'remaining'  => 'ó kù lónìí',
    ],

    'meds' => [
        'title'     => 'Òògùn mi',
        'add'       => 'Fi òògùn kún',
        'none'      => 'O kò tíì fi òògùn kankan kún.',
        'name'      => 'Orúkọ òògùn',
        'strength'  => 'Ìwọ̀n',
        'form'      => 'Irú',
        'purpose'   => 'Kí ni ó jẹ́ fún?',
        'times'     => 'Àkókò ọjọ́',
        'addTime'   => 'Fi àkókò mìíràn kún',
        'save'      => 'Fi òògùn pamọ́',
        'delete'    => 'Yọ òògùn kúrò',
        'doseCount' => 'ìgbà lójúmọ́',
    ],

    'explain' => [
        'title'       => 'Ṣàlàyé òògùn',
        'lead'        => 'Kọ ohun tí ìwé náà sọ, tàbí ya fọ́tò rẹ̀.',
        'run'         => 'Ṣàlàyé èyí',
        'working'     => 'Ń kà…',
        'purpose'     => 'Ohun tí ó jẹ́ fún',
        'dosing'      => 'Bí a ṣe ń mu',
        'sideEffects' => 'Àwọn ìṣòro tí ó lè ṣẹlẹ̀',
        'storage'     => 'Bí a ṣe ń fi pamọ́',
        'warnings'    => 'Ṣọ́ra nípa',
    ],

    'ask' => [
        'title'       => 'Bèèrè nípa òògùn rẹ',
        'lead'        => 'Bèèrè ìbéèrè ní èdè rẹ.',
        'placeholder' => 'Kọ ìbéèrè rẹ…',
        'send'        => 'Fi ránṣẹ́',
    ],

    'settings' => [
        'title'    => 'Ètò',
        'language' => 'Èdè',
        'textSize' => 'Ìwọ̀n ìkọ̀wé',
        'voice'    => 'Ka àwọn ìdáhùn sókè',
    ],

    'sos' => [
        'title' => 'Wá ìrànlọ́wọ́ nísinsìnyí',
        'lead'  => 'Bí ó bá jẹ́ pàjáwìrì, pe ìrànlọ́wọ́ lẹ́sẹ̀kẹsẹ̀.',
        'call'  => 'Pè',
        'back'  => 'Padà sẹ́yìn',
    ],

    'crisis' => [
        'title' => 'Èyí lè jẹ́ pàjáwìrì',
        'lead'  => 'Ohun tí o sọ lè le. Má dúró de ìdáhùn — wá ìrànlọ́wọ́ nísinsìnyí.',
    ],

    'common' => [
        'cancel'    => 'Fagilé',
        'back'      => 'Padà',
        'speak'     => 'Ka sókè',
        'error'     => 'Àṣìṣe kan ṣẹlẹ̀. Jọ̀wọ́ tún gbìyànjú.',
        'aiNote'    => 'AI ló kọ ọ́. Bá dókítà tàbí oníṣègùn rẹ sọ̀rọ̀ nígbà gbogbo.',
        'notAdvice' => 'Ìsọfúnni gbogbogbò ni èyí, kì í ṣe ìmọ̀ràn ìṣègùn.',
    ],

];
