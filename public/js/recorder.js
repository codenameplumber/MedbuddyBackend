// ── Hold-to-record ───────────────────────────────────────────────────
//
// A voice note is the lowest-literacy input there is: someone who cannot read
// the symptom labels can still hold a button and talk. It is an addition to
// the tap grid, never a replacement — the grid is what produces data a cohort
// view can count.
//
// Two consumers, one implementation:
//   data-recorder="form"     drops the blob into a file input and posts normally
//   data-recorder="livewire" hands the blob to @this.upload()
//
// Everything degrades: no MediaRecorder, no microphone, or a denied permission
// all end with a visible message and a form that still submits.
(function () {
  function attach(root) {
    var btn   = root.querySelector('[data-rec]');
    var label = root.querySelector('[data-rec-label]');
    var play  = root.querySelector('[data-rec-play]');
    var input = root.querySelector('[data-rec-input]');
    if (!btn || btn.dataset.bound) return;
    btn.dataset.bound = '1';

    var strings = {
      idle:   btn.dataset.idle   || 'Hold to speak',
      rec:    btn.dataset.rec    || 'Recording… let go to stop',
      done:   btn.dataset.done   || 'Recorded. Hold again to redo.',
      noMic:  btn.dataset.nomic  || 'Microphone not available — you can still send without it.',
    };

    if (!window.MediaRecorder || !navigator.mediaDevices) {
      if (label) label.textContent = strings.noMic;
      btn.disabled = true;
      return;
    }

    var recorder = null, chunks = [], url = null;

    function start(e) {
      e.preventDefault();
      if (recorder) return;

      navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
        chunks = [];
        recorder = new MediaRecorder(stream);
        recorder.ondataavailable = function (ev) { if (ev.data.size) chunks.push(ev.data); };
        recorder.onstop = function () {
          stream.getTracks().forEach(function (tr) { tr.stop(); });
          var type = recorder.mimeType || 'audio/webm';
          var blob = new Blob(chunks, { type: type });
          recorder = null;

          if (url) URL.revokeObjectURL(url);
          url = URL.createObjectURL(blob);
          if (play) { play.src = url; play.hidden = false; }
          if (label) label.textContent = strings.done;

          var ext = type.indexOf('ogg') > -1 ? 'ogg' : (type.indexOf('mp4') > -1 ? 'mp4' : 'webm');
          var file = new File([blob], 'note.' + ext, { type: type });

          if (root.dataset.recorder === 'livewire' && window.Livewire) {
            var component = window.Livewire.find(root.closest('[wire\\:id]').getAttribute('wire:id'));
            component.upload(root.dataset.recProp || 'audio', file);
          } else if (input) {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
          }
        };
        recorder.start();
        btn.classList.add('is-recording');
        if (label) label.textContent = strings.rec;
      }).catch(function (err) {
        // Denied permission, or an insecure context. Say so rather than
        // looking broken.
        if (label) label.textContent = strings.noMic;
        console.info('[recorder] mic unavailable:', err && err.name);
      });
    }

    function stop() {
      if (recorder && recorder.state === 'recording') recorder.stop();
      btn.classList.remove('is-recording');
    }

    btn.addEventListener('pointerdown', start);
    btn.addEventListener('pointerup', stop);
    btn.addEventListener('pointerleave', stop);
    btn.addEventListener('pointercancel', stop);
  }

  function scan() { document.querySelectorAll('[data-recorder]').forEach(attach); }

  document.addEventListener('DOMContentLoaded', scan);
  document.addEventListener('livewire:navigated', scan);
  document.addEventListener('livewire:update', scan);
  scan();
})();
