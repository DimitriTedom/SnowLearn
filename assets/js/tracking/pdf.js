// tracking/pdf.js — PDF scroll-to-end tracking via postMessage
(function () {
  let completed = false;

  // Strategy 1: listen for postMessage from PDF viewer (works with some browsers)
  window.addEventListener('message', (e) => {
    if (completed) return;
    if (e.data && (e.data.type === 'pdf-reached-end' || e.data === 'pdf-end')) {
      completed = true;
      window.markLessonDone && window.markLessonDone();
    }
  });

  // Strategy 2: manual "J'ai lu le PDF" button after 30s
  const btn = document.getElementById('btn-mark-pdf-done');
  if (btn) {
    btn.addEventListener('click', () => {
      if (!completed) {
        completed = true;
        window.markLessonDone && window.markLessonDone();
      }
    });

    // Show button after 30 seconds automatically
    setTimeout(() => {
      btn.style.display = 'inline-flex';
    }, 30000);
  }
})();
