(function () {
  let completed = false;

  window.addEventListener('message', (e) => {
    if (completed) return;
    if (e.data && (e.data.type === 'pdf-reached-end' || e.data === 'pdf-end')) {
      completed = true;
      window.markLessonDone && window.markLessonDone();
    }
  });

  const btn = document.getElementById('btn-mark-pdf-done');
  if (btn) {
    btn.addEventListener('click', () => {
      if (!completed) {
        completed = true;
        window.markLessonDone && window.markLessonDone();
      }
    });

    setTimeout(() => {
      btn.style.display = 'inline-flex';
    }, 30000);
  }
})();
