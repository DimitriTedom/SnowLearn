(function () {
  const video   = document.getElementById('lesson-video');
  const track   = document.getElementById('vid-track');
  const pctSpan = document.getElementById('vid-pct');
  if (!video) return;

  let completed = false;
  const THRESHOLD = 0.90;
  let maxTimeWatched = 0;

  video.addEventListener('timeupdate', () => {
    if (!video.duration) return;

    if (video.currentTime > maxTimeWatched + 1.5) {
      video.currentTime = maxTimeWatched;
    } else {
      maxTimeWatched = Math.max(maxTimeWatched, video.currentTime);
    }

    const pct = video.currentTime / video.duration;
    const display = Math.round(pct * 100);
    if (track)   track.style.width = display + '%';
    if (pctSpan) pctSpan.textContent = display + '%';

    if (!completed && pct >= THRESHOLD) {
      completed = true;
      window.markLessonDone && window.markLessonDone();
    }
  });

  video.addEventListener('seeking', () => {
    if (video.currentTime > maxTimeWatched + 1.5) {
      video.currentTime = maxTimeWatched;
    }
  });

  video.addEventListener('ended', () => {
    if (!completed) {
      completed = true;
      window.markLessonDone && window.markLessonDone();
    }
  });
})();
