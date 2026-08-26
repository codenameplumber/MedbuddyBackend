/* ═══════════════════════════════════════════════════════════════════
   Read aloud, for the patient app.

   It always reads. That is the product decision: this audience includes
   people who cannot comfortably read the screen at all, so silence is the
   one outcome that helps nobody.

   VOICE SELECTION, BEST TO WORST
     1. A voice for the patient's exact language (yo / ha / ig / en).
     2. A voice for a language with broadly similar vowels, which for the
        three Nigerian languages here means preferring Spanish, Italian or
        Portuguese over English — all three are five-vowel, syllable-timed
        and read `a e i o u` far closer to Yorùbá than an English voice does.
     3. The browser default.

   Browsers ship voices for English almost everywhere and for Yorùbá, Hausa
   and Igbo almost nowhere, so in practice step 2 is what usually runs. When
   it does, the button says so — the reading is approximate, and a patient is
   entitled to know that before trusting how a dose was pronounced.
   ═══════════════════════════════════════════════════════════════════ */
(function () {
  'use strict';

  var SUPPORTED = ('speechSynthesis' in window) && typeof SpeechSynthesisUtterance !== 'undefined';

  if (!SUPPORTED) {
    // The button ships visible so it cannot go missing when voices load late.
    // A browser with no speech at all is the one case where it should go.
    var hideAll = function () {
      document.querySelectorAll('[data-speak]').forEach(function (host) { host.hidden = true; });
    };
    document.addEventListener('DOMContentLoaded', hideAll);
    document.addEventListener('livewire:navigated', hideAll);
    document.addEventListener('livewire:updated', hideAll);
    hideAll();
    return;
  }

  /*
   * Fallback order per language. Five-vowel, syllable-timed languages first:
   * a Spanish voice reading "Mu tábìlẹ́tì kan lójoojúmọ́" lands far closer than
   * an English one, which stresses and reduces vowels that must not be.
   */
  var FALLBACKS = {
    yo: ['es', 'it', 'pt', 'sw', 'en'],
    ig: ['es', 'it', 'pt', 'sw', 'en'],
    ha: ['es', 'it', 'pt', 'sw', 'en'],
    en: ['en'],
  };

  var current = null;

  function voices() {
    return window.speechSynthesis.getVoices() || [];
  }

  function baseOf(tag) {
    return String(tag || '').toLowerCase().split(/[-_]/)[0];
  }

  function firstVoiceFor(base) {
    var all = voices();

    return all.find(function (v) { return baseOf(v.lang) === base && v.localService; })
        || all.find(function (v) { return baseOf(v.lang) === base; })
        || null;
  }

  /**
   * @returns {{voice: SpeechSynthesisVoice, exact: boolean}|null}
   */
  function pickVoice(lang) {
    var base = baseOf(lang) || 'en';

    var exact = firstVoiceFor(base);
    if (exact) return { voice: exact, exact: true };

    var chain = FALLBACKS[base] || ['en'];
    for (var i = 0; i < chain.length; i++) {
      var v = firstVoiceFor(chain[i]);
      if (v) return { voice: v, exact: false };
    }

    var any = voices()[0];
    return any ? { voice: any, exact: false } : null;
  }

  function setLabel(host, text) {
    var label = host.querySelector('[data-speak-label]');
    if (label) label.textContent = text;
  }

  function stop() {
    window.speechSynthesis.cancel();
    if (current) {
      setLabel(current, current.dataset.labelPlay);
      current.classList.remove('is-speaking');
      current = null;
    }
  }

  function speak(host) {
    var holder = host.querySelector('[data-speak-text]');
    var text = holder ? String(holder.textContent || '').trim() : '';
    if (!text) return;

    stop();

    /*
     * Voices arrive asynchronously, and on a cold page the first tap often
     * lands before the list exists. Speak anyway once it does — up to a
     * second — rather than making the patient tap twice for no visible
     * reason.
     */
    if (!voices().length) {
      host.classList.add('is-speaking');
      setLabel(host, host.dataset.labelStop);
      current = host;

      var waited = 0;
      var poll = setInterval(function () {
        waited += 100;
        if (voices().length) { clearInterval(poll); utter(host, text); return; }
        if (waited >= 1000) { clearInterval(poll); utter(host, text); }
      }, 100);

      return;
    }

    utter(host, text);
  }

  /** Build and start the utterance. Voices are known to be loaded by here. */
  function utter(host, text) {
    var picked = pickVoice(host.dataset.lang || 'en');

    if (!picked) {
      // No voices at all on this device after waiting. Put the control back.
      stop();
      return;
    }


    var utterance = new SpeechSynthesisUtterance(text);
    utterance.voice = picked.voice;
    utterance.lang = picked.voice.lang;
    // Slower than default, and slower again when the voice is a stand-in:
    // this is a dose instruction read to someone who may be anxious, not a
    // notification.
    utterance.rate = picked.exact ? 0.92 : 0.82;

    utterance.onend = stop;
    utterance.onerror = stop;

    current = host;
    host.classList.add('is-speaking');
    setLabel(host, host.dataset.labelStop);

    /*
     * Chrome stops speaking after ~15s unless it is nudged. Long explanations
     * are exactly the case this feature exists for, so the nudge is not
     * optional.
     */
    window.speechSynthesis.speak(utterance);
    var keepAlive = setInterval(function () {
      if (!window.speechSynthesis.speaking) { clearInterval(keepAlive); return; }
      window.speechSynthesis.pause();
      window.speechSynthesis.resume();
    }, 10000);
  }

  /**
   * Annotate only. The button is already visible — this just adds the note
   * when the voice we would use is a stand-in for the patient's language.
   */
  function setup(host) {
    if (host.dataset.speakReady === '1') return;
    if (!voices().length) return;   // onvoiceschanged will call back

    host.dataset.speakReady = '1';

    var note = host.querySelector('[data-speak-note]');
    var picked = pickVoice(host.dataset.lang || 'en');

    if (picked && !picked.exact && note && host.dataset.labelApprox) {
      note.textContent = host.dataset.labelApprox;
      note.hidden = false;
    }
  }

  function setupAll() {
    document.querySelectorAll('[data-speak]').forEach(setup);
  }

  document.addEventListener('click', function (e) {
    var button = e.target.closest('[data-speak-btn]');
    if (!button) return;

    var host = button.closest('[data-speak]');
    if (!host) return;

    if (current === host) { stop(); return; }
    speak(host);
  });

  // Voices load asynchronously in most browsers, and Livewire swaps the DOM
  // under us on every navigation and re-render.
  window.speechSynthesis.onvoiceschanged = setupAll;
  document.addEventListener('livewire:navigated', setupAll);
  document.addEventListener('livewire:updated', setupAll);
  document.addEventListener('DOMContentLoaded', setupAll);
  setupAll();

  // Never let a page leave with the phone still talking.
  window.addEventListener('pagehide', stop);
  document.addEventListener('livewire:navigating', stop);
})();
