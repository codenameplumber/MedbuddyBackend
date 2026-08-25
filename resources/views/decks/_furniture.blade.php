@php use App\Support\Chart; @endphp

{{--
  Deck charts and slide furniture.

  The prototype's decks imported js/shared/charts.js and read the browser's
  localStorage seed, which is why they showed a cohort of 8 no matter what the
  platform actually held. These are the same charts rendered server-side from
  the real database — "a deck that drifts from the product is worse than no
  deck" was the note on the original script, and it is only true if the
  numbers are live.

  Injected into whichever containers the deck declares; a deck that has no
  #c-heat simply never gets one.
--}}

<script>
(function () {
  var charts = @json($charts);

  Object.keys(charts).forEach(function (id) {
    var host = document.querySelector('#' + id);
    if (host) host.insertAdjacentHTML('beforeend', charts[id]);
  });

  // Slide furniture, applied once rather than repeated on every slide.
  var slides = Array.prototype.slice.call(document.querySelectorAll('.slide'));
  slides.forEach(function (s, i) {
    s.insertAdjacentHTML('beforeend',
      '<div class="s-brand">MedBuddy</div>' +
      '<div class="s-num">' + (i + 1) + ' / ' + slides.length + '</div>');
  });

  var count = document.getElementById('count');
  if (count) count.textContent = slides.length + ' slides · 16:9';
})();
</script>
