// tracking/core.js — AJAX lesson completion
(function () {
  const LESSON_ID   = window.LESSON_ID;
  const HAS_QUIZ    = window.HAS_QUIZ;
  const QUIZ_URL    = window.QUIZ_URL;
  const IS_DONE     = window.IS_ALREADY_DONE;

  if (IS_DONE) return;

  window.markLessonDone = async function () {
    try {
      const res = await fetch('../api/mark_lesson_done.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ lesson_id: LESSON_ID }),
      });
      const data = await res.json();
      if (data.success) {
        // Update gate UI
        const gate = document.getElementById('lesson-gate');
        if (gate) {
          gate.className = 'lesson-gate state-done';
          gate.innerHTML = `
            <div class="gate-icon">
              <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"></path></svg>
            </div>
            <div class="gate-text">
              <div class="gate-title">Leçon terminée !</div>
              <div class="gate-sub">Vous pouvez maintenant passer l'évaluation.</div>
            </div>
            ${HAS_QUIZ ? `<a href="${QUIZ_URL}" class="btn btn-primary">Passer le quiz</a>` : ''}
          `;
        }
        Toast.success('Leçon marquée comme terminée !');
        // fetch update progress in background
        fetch('../api/update_progress.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ lesson_id: LESSON_ID }),
        });
      }
    } catch (e) {
      console.error('markLessonDone error', e);
    }
  };
})();
