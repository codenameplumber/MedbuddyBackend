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

    // Added for the Laravel patient app. No yo/ha/ig yet: Laravel falls back
    // to English, which is honest. A machine guess here would not be.
    'history' => [
        'title'        => 'My history',
        'link'         => 'See my history',
        'week'         => 'over the last 7 days',
        'month'        => 'over the last 30 days',
        'legendTaken'  => 'taken',
        'legendMissed' => 'missed',
        'each'         => 'Each column is one day. Each dot is one dose.',
        'reports'      => 'What you have told us',
        'none'         => 'Nothing recorded yet.',
    ],

    'report' => [
        'title'    => 'How are you feeling?',
        'sent'     => 'Thank you',
        'sentBody' => 'Your clinic can see this now. You do not need to do anything else.',
        'again'    => 'Tell us something else',
    ],

    'profile' => [
        'caregiverHint' => 'Your clinic can send this person a summary of how you are doing. They ask you first.',
        'consent'        => 'What you agree to',
        'reminders'      => 'Send me dose reminders by email',
        'remindersHint'  => 'A reminder names the time of day and nothing else — never your medicine or your condition.',
        'research'       => 'Use my reports to improve treatment',
        'researchHint'   => 'Your name is never attached. You can turn this off at any time.',
        'clinic'         => 'My clinic',
        'caregiver'      => 'My caregiver',
        'medicines'      => 'medicines',
        'signout'        => 'Sign out',
        'signin'         => 'Open MedBuddy',
        'signinLead'     => 'Type the email address your clinic has for you. We will send you a link that opens your medicines. There is no password to remember.',
        'signinSent'     => 'Check your email. If that address is registered with your clinic, a link is on its way. It works once and expires in 30 minutes.',
        'signinCta'      => 'Send me a link',
    ],
    'app' => [
        'name' => 'MedBuddy',
    ],

    'nav' => [
        'today'    => 'Today',
        'meds'     => 'Medicines',
        'explain'  => 'Scan',
        'ask'      => 'Ask',
        'settings' => 'Settings',
    ],

    'today' => [
        'doseTime'   => ':time',
        'title'      => 'Today',
        'none'       => 'No medicines scheduled yet.',
        'addFirst'   => 'Add your first medicine',
        'allDone'    => 'All doses taken today. Well done.',
        'streak'     => 'day streak',
        'streakNone' => 'Take today’s doses to start a streak.',
        'markTaken'  => 'Mark as taken',
        'taken'      => 'Taken',
        'remaining'  => 'left today',
    ],

    'meds' => [
        'selfAdded'   => 'added by you',
        'clinicKeeps' => 'Your clinic keeps this list. You can add anything else you take yourself.',
        'notSure'     => 'Not sure what a tablet is?',
        "ownTitle"    => "This one is yours, not the clinic's",
        'ownBody'     => 'Anything you add here is marked as added by you. Your clinic can see it, but only they can change a medicine they prescribed.',
        'title'               => 'My medicines',
        'add'                 => 'Add a medicine',
        'none'                => 'You have not added any medicines yet.',
        'name'                => 'Medicine name',
        'namePlaceholder'     => 'e.g. Amlodipine',
        'generic'             => 'Other name (optional)',
        'strength'            => 'Strength',
        'strengthPlaceholder' => 'e.g. 5 mg',
        'form'                => 'Form',
        'purpose'             => 'What is it for?',
        'purposePlaceholder'  => 'e.g. blood pressure',
        'times'               => 'Times of day',
        'addTime'             => 'Add another time',
        'save'                => 'Save medicine',
        'delete'              => 'Remove medicine',
        'deleteConfirm'       => 'Remove this medicine and its history?',
        'doseCount'           => 'times a day',
    ],

    'explain' => [
        'title'           => 'Explain a medicine',
        'lead'            => 'Type what the label says, or take a photo of it.',
        'textLabel'       => 'What does the label or note say?',
        'textPlaceholder' => 'Type or paste the label text here…',
        'photo'           => 'Take or choose a photo',
        'run'             => 'Explain this',
        'working'         => 'Reading…',
        'needInput'       => 'Add some text or a photo first.',
        'purpose'         => 'What it is for',
        'dosing'          => 'How to take it',
        'sideEffects'     => 'Possible side effects',
        'storage'         => 'How to store it',
        'warnings'        => 'Be careful about',
        'saveAsMed'       => 'Add to my medicines',
    ],

    'ask' => [
        'title'       => 'Ask about your medicines',
        'lead'        => 'Ask a question in your own language.',
        'placeholder' => 'Type your question…',
        'send'        => 'Send',
        'empty'       => 'No questions yet. Ask anything about your medicines.',
    ],

    'settings' => [
        'title'            => 'Settings',
        'language'         => 'Language',
        'textSize'         => 'Text size',
        'textNormal'       => 'Normal',
        'textLarge'        => 'Large',
        'textHuge'         => 'Largest',
        'voice'            => 'Read answers aloud',
        'voiceEnglishOnly' => 'Voice is available in English only for now.',
        'ai'               => 'AI connection',
        'aiMock'           => 'Demo (offline examples)',
        'aiDirect'         => 'Live (uses your API key)',
        'apiKey'           => 'Anthropic API key',
        'apiKeyHint'       => 'Stored only on this device. Leave empty to stay in demo mode.',
        'emergencyNumber'  => 'Emergency number',
        'reset'            => 'Erase all my data',
        'resetConfirm'     => 'Erase everything on this device? This cannot be undone.',
    ],

    'sos' => [
        'title'      => 'Get help now',
        'lead'       => 'If this is a medical emergency, call for help straight away.',
        'call'       => 'Call',
        'noContacts' => 'No emergency contact saved yet.',
        'back'       => 'Go back',
    ],

    'crisis' => [
        'title' => 'This may be an emergency',
        'lead'  => 'What you described can be serious. Do not wait for an answer from this app — get help now.',
    ],

    'common' => [
        'cancel'    => 'Cancel',
        'back'      => 'Back',
        'close'     => 'Close',
        'speak'     => 'Read aloud',
        'stop'      => 'Stop reading',
        'error'     => 'Something went wrong. Please try again.',
        'offline'   => 'You are offline. Your medicines and reminders still work.',
        'aiNote'    => 'Written by AI. Always check with your doctor or pharmacist.',
        'notAdvice' => 'This is general information, not medical advice.',
    ],

];
