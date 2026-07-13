<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireRole('student');

$lesson_id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$lesson_id) { header('Location: modules.php'); exit; }

$db         = getDB();
$student_id = currentUser()['id'];

// Verify lesson completed
$done = $db->prepare("SELECT 1 FROM student_lessons WHERE student_id=? AND lesson_id=? LIMIT 1");
$done->execute([$student_id, $lesson_id]);
if (!$done->fetch()) {
    header('Location: lesson.php?id=' . $lesson_id . '&notice=complete_first');
    exit;
}

// Fetch quiz
$quiz = $db->prepare("
    SELECT qz.*, l.titre AS lesson_titre, l.id AS lesson_id, c.module_id
    FROM quizzes qz
    JOIN lessons l ON l.id=qz.lesson_id
    JOIN courses c ON c.id=l.course_id
    WHERE qz.lesson_id=?
    LIMIT 1
");
$quiz->execute([$lesson_id]);
$quiz = $quiz->fetch();
if (!$quiz) { header('Location: lesson.php?id=' . $lesson_id); exit; }

// Already attempted?
$attempt = $db->prepare("SELECT * FROM results WHERE student_id=? AND quiz_id=? LIMIT 1");
$attempt->execute([$student_id, $quiz['id']]);
$attempt = $attempt->fetch();

// Questions
$questions = $db->prepare("SELECT * FROM quiz_questions WHERE quiz_id=? ORDER BY id ASC");
$questions->execute([$quiz['id']]);
$questions = $questions->fetchAll();

// Handle submit
$result_data = null;
if (!$attempt && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = count($questions);
    $answers = $_POST['answer'] ?? [];

    foreach ($questions as $q) {
        if (isset($answers[$q['id']]) && strtoupper($answers[$q['id']]) === $q['bonne_reponse']) {
            $score++;
        }
    }

    $pct    = $total > 0 ? round(($score / $total) * 100, 2) : 0;
    $passed = $pct >= $quiz['passing_score'];

    $db->prepare("
        INSERT INTO results (student_id, quiz_id, lesson_id, score, total, pourcentage, passed)
        VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE score=VALUES(score), total=VALUES(total), pourcentage=VALUES(pourcentage), passed=VALUES(passed), taken_at=NOW()
    ")->execute([$student_id, $quiz['id'], $lesson_id, $score, $total, $pct, $passed ? 1 : 0]);

    // Update module progress
    updateModuleProgress($db, $student_id, $quiz['module_id']);

    // Auto-deliver certificate if eligible
    $prog_check = $db->prepare("
        SELECT p.pourcentage, m.passing_threshold
        FROM progress p
        JOIN modules m ON m.id=p.module_id
        WHERE p.student_id=? AND p.module_id=?
        LIMIT 1
    ");
    $prog_check->execute([$student_id, $quiz['module_id']]);
    $pc = $prog_check->fetch();
    if ($pc && $pc['pourcentage'] >= $pc['passing_threshold']) {
        $db->prepare("INSERT IGNORE INTO certificates (student_id, module_id) VALUES (?,?)")
           ->execute([$student_id, $quiz['module_id']]);
    }

    header('Location: quizz.php?id=' . $lesson_id . '&done=1');
    exit;
}

// Reload attempt after submit redirect
if (!$attempt) {
    $attempt = $db->prepare("SELECT * FROM results WHERE student_id=? AND quiz_id=? LIMIT 1");
    $attempt->execute([$student_id, $quiz['id']]);
    $attempt = $attempt->fetch();
}

$show_results = ($attempt && isset($_GET['done'])) || $attempt;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SnowLearn — Quiz : <?= sanitize($quiz['titre']) ?></title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="shell">
  <?php include '../includes/sidebar_student.php'; ?>
  <div class="main-content">
    <header class="topbar">
      <button class="menu-toggle"><svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <span class="topbar-title">Quiz</span>
      <div class="topbar-right">
        <a href="lesson.php?id=<?= $lesson_id ?>" class="btn btn-ghost btn-sm">← Retour à la leçon</a>
      </div>
    </header>
    <main class="page-body" style="max-width:760px;">
      <div style="margin-bottom:24px;">
        <h2 style="font-size:1.4rem;"><?= sanitize($quiz['titre']) ?></h2>
        <p style="font-size:.875rem;color:var(--text-muted);">Leçon : <?= sanitize($quiz['lesson_titre']) ?> · <?= count($questions) ?> question(s) · Score requis : <?= $quiz['passing_score'] ?>%</p>
      </div>

      <?php if ($show_results && $attempt): ?>
      <!-- RESULTS -->
      <div class="card" style="margin-bottom:24px;background:<?= $attempt['passed'] ? 'rgba(16,185,129,.08)' : 'rgba(239,68,68,.08)' ?>;border-color:<?= $attempt['passed'] ? 'rgba(16,185,129,.25)' : 'rgba(239,68,68,.25)' ?>;">
        <div class="card-body" style="text-align:center;padding:32px;">
          <div style="width:72px;height:72px;border-radius:50%;background:<?= $attempt['passed'] ? 'var(--success-light)' : 'var(--danger-light)' ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <?php if ($attempt['passed']): ?>
            <svg viewBox="0 0 24 24" width="32" height="32" stroke="var(--success)" fill="none" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <?php else: ?>
            <svg viewBox="0 0 24 24" width="32" height="32" stroke="var(--danger)" fill="none" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            <?php endif; ?>
          </div>
          <div style="font-family:'Outfit',sans-serif;font-size:2.5rem;font-weight:800;color:<?= $attempt['passed'] ? 'var(--success)' : 'var(--danger)' ?>;"><?= round($attempt['pourcentage']) ?>%</div>
          <div style="font-size:1rem;font-weight:700;margin:8px 0 4px;"><?= $attempt['passed'] ? '🎉 Quiz validé !' : '❌ Quiz non validé' ?></div>
          <div style="font-size:.875rem;color:var(--text-muted);"><?= $attempt['score'] ?>/<?= $attempt['total'] ?> bonnes réponses</div>
          <?php if ($attempt['passed']): ?>
          <div style="margin-top:12px;">
            <a href="certificates.php" class="btn btn-success btn-sm">Voir mes certificats</a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Answer review -->
      <div style="display:flex;flex-direction:column;gap:14px;">
        <?php foreach ($questions as $idx => $q): ?>
        <div class="question-card">
          <div class="question-num">Question <?= $idx + 1 ?>/<?= count($questions) ?></div>
          <div class="question-text"><?= sanitize($q['question']) ?></div>
          <div class="options-grid">
            <?php foreach (['A','B','C','D'] as $opt): ?>
            <div class="option-label <?= $q['bonne_reponse'] === $opt ? 'correct' : '' ?>">
              <div class="option-dot"></div>
              <strong style="font-size:.8rem;"><?= $opt ?>.</strong>
              <?= sanitize($q['option_'.strtolower($opt)]) ?>
              <?php if ($q['bonne_reponse'] === $opt): ?>
              <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="2" style="margin-left:auto;"><polyline points="20 6 9 17 4 12"/></svg>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap;">
        <a href="lesson.php?id=<?= $lesson_id ?>" class="btn btn-ghost">← Retour à la leçon</a>
        <a href="modules.php" class="btn btn-primary">Voir mes modules</a>
      </div>

      <?php else: ?>
      <!-- QUIZ FORM -->
      <?php if (empty($questions)): ?>
      <div class="card"><div style="padding:40px;text-align:center;color:var(--text-muted);">Ce quiz n'a pas encore de questions.</div></div>
      <?php else: ?>

      <!-- Progress steps -->
      <div class="quiz-progress" style="margin-bottom:24px;">
        <?php for ($i = 0; $i < count($questions); $i++): ?>
        <div class="quiz-step" id="step-<?= $i ?>"></div>
        <?php endfor; ?>
      </div>

      <form method="POST" id="quiz-form">
        <input type="hidden" name="submit_quiz" value="1">
        <div id="questions-container" style="display:flex;flex-direction:column;gap:16px;">
          <?php foreach ($questions as $idx => $q): ?>
          <div class="question-card" id="qcard-<?= $idx ?>" style="<?= $idx > 0 ? 'display:none;' : '' ?>">
            <div class="question-num">Question <?= $idx + 1 ?> / <?= count($questions) ?></div>
            <div class="question-text"><?= sanitize($q['question']) ?></div>
            <div class="options-grid">
              <?php foreach (['A','B','C','D'] as $opt): ?>
              <label class="option-label" for="q<?= $q['id'] ?>_<?= $opt ?>">
                <input type="radio" id="q<?= $q['id'] ?>_<?= $opt ?>"
                  name="answer[<?= $q['id'] ?>]" value="<?= $opt ?>"
                  onchange="nextQuestion(<?= $idx ?>, <?= count($questions) ?>)">
                <div class="option-dot"></div>
                <strong style="font-size:.82rem;"><?= $opt ?>.</strong>
                <?= sanitize($q['option_' . strtolower($opt)]) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;justify-content:flex-end;" id="submit-wrap" style="display:none;">
          <a href="lesson.php?id=<?= $lesson_id ?>" class="btn btn-ghost">Annuler</a>
          <button type="submit" class="btn btn-primary btn-lg" id="btn-submit" style="display:none;">
            <svg viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
            Soumettre le quiz
          </button>
        </div>
      </form>
      <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="../assets/js/app.js"></script>
<script>
let currentQ = 0;
const total = <?= count($questions) ?>;

function nextQuestion(idx, total) {
  // Mark step done
  const step = document.getElementById('step-' + idx);
  if (step) { step.classList.add('done'); }

  setTimeout(() => {
    const next = idx + 1;
    if (next < total) {
      document.getElementById('qcard-' + idx).style.display = 'none';
      document.getElementById('qcard-' + next).style.display = 'block';
      document.getElementById('step-' + next).classList.add('active');
    } else {
      // Last question answered
      document.getElementById('btn-submit').style.display = 'inline-flex';
      document.getElementById('submit-wrap').style.removeProperty('display');
    }
  }, 400);
}

// Init first step
const s0 = document.getElementById('step-0');
if (s0) s0.classList.add('active');
</script>
</body>
</html>
